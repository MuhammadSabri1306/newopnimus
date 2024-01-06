<?php
namespace App\Controller\Bot;

use App\Controller\BotController;
use App\Model\TelegramAdmin;
use App\Model\Regional;
use App\Model\Witel;
use App\Model\Datel;
use App\Model\RtuList;
use App\Model\RtuLocation;
use App\ApiRequest\NewosaseApi;
use App\Helper\ArrayHelper;

class SyncLocationController extends BotController
{
    public static function isSuperAdmin()
    {
        $message = static::$command->getMessage();
        $chatId = $message->getChat()->getId();
        $telgAdmin = TelegramAdmin::findByChatId($chatId);
        return $telgAdmin['is_super_admin'] == 1;
    }

    public static function sync()
    {
        $message = static::$command->getMessage();
        $chatId = $message->getChat()->getId();
        $messageText = strtolower(trim($message->getText(true)));
        
        if($messageText == 'rtu') return static::syncRtus();
        if($messageText == 'lokasi') return static::syncLocations();
        if($messageText == 'datel') return static::syncDatels();
        if($messageText == 'witel') return static::syncWitels();
        if($messageText == 'regional') return static::syncRegionals();

        $request = BotController::request('TextDefault');
        $request->params->chatId = $chatId;
        $request->setText(function($text) {
            return $text->addText('Format perintah tidak sesuai. Silahkan gunakan format berikut.')
                ->addItalic('/syncloc [REGIONAL/WITEL/DATEL/LOKASI/RTU]');
        });
        return $request->send();
    }

    protected static function syncRtus()
    {
        $message = static::$command->getMessage();
        $chatId = $message->getChat()->getId();

        $newosaseApi = new NewosaseApiV2();
        $newosaseApi->setupAuth();
        $osaseData = $newosaseApi->sendRequest('GET', '/admin-service/locations');
        $locationList = $osaseData->get('result.locations');
        if(!$locationList) {
            dd('$osaseData->result->locations is empty', $locationList);
        }

        $rtuData = [];
        $currDate = date('Y-m-d H:i:s');
        foreach($locationList as $loc) {
            foreach($loc->rtus as $rtu) {

                $newRtu = [
                    'uuid' => $rtu->id,
                    'id_m' => $rtu->id_m_location,
                    'name' => $rtu->name,
                    'sname' => $rtu->sname,
                    'location_id' => $loc->id,
                    'datel_id' => $loc->id_datel,
                    'witel_id' => $loc->id_witel,
                    'regional_id' => $loc->id_regional,
                    'timestamp' => $currDate
                ];

                array_push($rtuData, $newRtu);

            }
        }

        $rtuCount = count($rtuData);
        if($rtuCount > 0) {
            RtuList::query(function($db, $table) use ($rtuData) {
    
                $db->query("TRUNCATE TABLE $table");
                $db->insert($table, $rtuData);
    
            });
        }

        $request = BotController::request('TextDefault');
        $request->params->chatId = $chatId;
        $request->setText(function($text) use ($rtuCount) {
            if($rtuCount > 0) {
                return $text->addText('Data RTU telah disinkronisasi dengan API New Osase.')
                    ->newLine()->addSpace(4)->addItalic("Total RTU : $rtuCount");
            } else {
                return $text->addText('Gagal melakukan sinkronisasi RTU.');
            }
        });
        return $request->send();
    }

    protected static function syncLocations()
    {
        $message = static::$command->getMessage();
        $chatId = $message->getChat()->getId();

        $newosaseApi = new NewosaseApi();
        $newosaseApi->setupAuth();
        $osaseData = $newosaseApi->sendRequest('GET', '/admin-service/locations');

        if(!$osaseData) {
            $request = BotController::request('Error/TextErrorServer');
            $request->params->chatId = $chatId;
            return $request->send();
        }

        if(!isset($osaseData->result->locations) || !is_array($osaseData->result->locations)) {
            $request = BotController::request('Error/TextErrorNotFound');
            $request->params->chatId = $chatId;
            return $request->send();
        }

        $locs = RtuLocation::getAll();
        $pushCount = 0;
        $updateCount = 0;
        foreach($osaseData->result->locations as $loc) {

            $locIdM = null;
            for($i=0; $i<count($loc->rtus); $i++) {
                if(isset($loc->rtus[$i]->id_m_location)) {
                    $locIdM = $loc->rtus[$i]->id_m_location;
                    if($locIdM) {
                        $i = count($loc->rtus);
                    }
                }
            }

            $matchedLoc = ArrayHelper::find($locs, fn($item) => $item['id'] == ($loc->id ?? null));
            if(!$matchedLoc) {

                RtuLocation::create([
                    'id' => $loc->id,
                    'id_m' => $locIdM,
                    'location_name' => $loc->name,
                    'location_sname' => $loc->sname,
                    'datel_id' => $loc->id_datel,
                ]);
                $pushCount++;

            } else {

                $locUpdate = [];
                if($matchedLoc['id_m'] != $locIdM) $locUpdate['id_m'] = $locIdM;
                if($matchedLoc['location_name'] != $loc->name) $locUpdate['location_name'] = $loc->name;
                if($matchedLoc['location_sname'] != $loc->sname) $locUpdate['location_sname'] = $loc->sname;
                if($matchedLoc['datel_id'] != $loc->id_datel) $locUpdate['datel_id'] = $loc->id_datel;

                if(count($locUpdate)) {
                    RtuLocation::update($matchedLoc['id'], $locUpdate);
                    $updateCount++;
                }

            }
        }

        $request = BotController::request('TextDefault');
        $request->params->chatId = $chatId;
        $request->setText(function($text) use ($pushCount, $updateCount) {
            return $text->addText('Data Lokasi telah disinkronisasi dengan API New Osase.')
                ->newLine()->addSpace(4)->addItalic("Total Lokasi baru     : $pushCount")
                ->newLine()->addSpace(4)->addItalic("Total Lokasi diupdate : $updateCount");
        });
        return $request->send();
    }

    protected static function syncDatels()
    {
        $message = static::$command->getMessage();
        $chatId = $message->getChat()->getId();

        $newosaseApi = new NewosaseApi();
        $newosaseApi->setupAuth();
        $newosaseApi->request['query'] = [ 'level' => 3 ];
        $osaseData = $newosaseApi->sendRequest('GET', '/parameter-service/other/org');

        if(!$osaseData) {
            $request = BotController::request('Error/TextErrorServer');
            $request->params->chatId = $chatId;
            return $request->send();
        }

        if(!isset($osaseData->result) || !is_array($osaseData->result)) {
            $request = BotController::request('Error/TextErrorNotFound');
            $request->params->chatId = $chatId;
            return $request->send();
        }

        $datels = Datel::getAll();
        $pushCount = 0;
        $updateCount = 0;
        foreach($osaseData->result as $datel) {

            $matchedDatel = ArrayHelper::find($datels, fn($item) => $item['id'] == ($datel->id ?? null));
            if(!$matchedDatel) {

                Datel::create([
                    'id' => $datel->id,
                    'datel_name' => $datel->name,
                    'witel_id' => $datel->parent_id,
                ]);
                $pushCount++;

            } else {

                $datelUpdate = [];
                if($matchedDatel['datel_name'] != $datel->name) $datelUpdate['datel_name'] = $datel->name;
                if($matchedDatel['witel_id'] != $datel->parent_id) $datelUpdate['witel_id'] = $datel->parent_id;
                if(count($datelUpdate)) {
                    Datel::update($matchedDatel['id'], $datelUpdate);
                    $updateCount++;
                }

            }
        }

        $request = BotController::request('TextDefault');
        $request->params->chatId = $chatId;
        $request->setText(function($text) use ($pushCount, $updateCount) {
            return $text->addText('Data Datel telah disinkronisasi dengan API New Osase.')
                ->newLine()->addSpace(4)->addItalic("Total Datel baru     : $pushCount")
                ->newLine()->addSpace(4)->addItalic("Total Datel diupdate : $updateCount");
        });
        return $request->send();
    }

    protected static function syncWitels()
    {
        $message = static::$command->getMessage();
        $chatId = $message->getChat()->getId();

        $newosaseApi = new NewosaseApi();
        $newosaseApi->setupAuth();
        $newosaseApi->request['query'] = [ 'level' => 2 ];
        $osaseData = $newosaseApi->sendRequest('GET', '/parameter-service/other/org');

        if(!$osaseData) {
            $request = BotController::request('Error/TextErrorServer');
            $request->params->chatId = $chatId;
            return $request->send();
        }

        if(!isset($osaseData->result) || !is_array($osaseData->result)) {
            $request = BotController::request('Error/TextErrorNotFound');
            $request->params->chatId = $chatId;
            return $request->send();
        }

        $witels = Witel::getAll();
        $pushCount = 0;
        $updateCount = 0;
        foreach($osaseData->result as $witel) {

            $matchedWitel = ArrayHelper::find($witels, fn($item) => $item['id'] == ($witel->id ?? null));
            if(!$matchedWitel) {

                Witel::create([
                    'id' => $witel->id,
                    'witel_name' => $witel->name,
                    'witel_code' => null,
                    'regional_id' => $witel->parent_id,
                ]);
                $pushCount++;

            } else {

                $witelUpdate = [];
                if($matchedWitel['witel_name'] != $witel->name) $witelUpdate['witel_name'] = $witel->name;
                if($matchedWitel['regional_id'] != $witel->parent_id) $witelUpdate['regional_id'] = $witel->parent_id;
                if(count($witelUpdate)) {
                    Witel::update($matchedWitel['id'], $witelUpdate);
                    $updateCount++;
                }

            }
        }

        $request = BotController::request('TextDefault');
        $request->params->chatId = $chatId;
        $request->setText(function($text) use ($pushCount, $updateCount) {
            return $text->addText('Data Witel telah disinkronisasi dengan API New Osase.')
                ->newLine()->addSpace(4)->addItalic("Total Witel baru     : $pushCount")
                ->newLine()->addSpace(4)->addItalic("Total Witel diupdate : $updateCount");
        });
        return $request->send();
    }

    protected static function syncRegionals()
    {
        $message = static::$command->getMessage();
        $chatId = $message->getChat()->getId();

        $newosaseApi = new NewosaseApi();
        $newosaseApi->setupAuth();
        $newosaseApi->request['query'] = [ 'level' => 1 ];
        $osaseData = $newosaseApi->sendRequest('GET', '/parameter-service/other/org');

        if(!$osaseData) {
            $request = BotController::request('Error/TextErrorServer');
            $request->params->chatId = $chatId;
            return $request->send();
        }

        if(!isset($osaseData->result) || !is_array($osaseData->result)) {
            $request = BotController::request('Error/TextErrorNotFound');
            $request->params->chatId = $chatId;
            return $request->send();
        }

        $regionals = Regional::getAll();
        $pushCount = 0;
        $updateCount = 0;
        foreach($osaseData->result as $treg) {

            $divreNumber = str_replace('r', '', strtolower($treg->sname));
            $divreCode = 'TLK-r'.$divreNumber.'000000';
            $matchedTreg = ArrayHelper::find($regionals, fn($item) => $item['id'] == ($treg->id ?? null));
            if(!$matchedTreg) {

                Regional::create([
                    'id' => $treg->id,
                    'name' => $treg->name,
                    'sname' => $treg->sname,
                    'divre_code' => $divreCode,
                ]);
                $pushCount++;

            } else {

                $tregUpdate = [];
                if($matchedTreg['name'] != $treg->name) $tregUpdate['name'] = $treg->name;
                if($matchedTreg['sname'] != $treg->sname) $tregUpdate['sname'] = $treg->sname;
                if($matchedTreg['divre_code'] != $divreCode) $tregUpdate['divre_code'] = $divreCode;
                if(count($tregUpdate)) {
                    Regional::update($matchedTreg['id'], $tregUpdate);
                    $updateCount++;
                }

            }
        }

        $request = BotController::request('TextDefault');
        $request->params->chatId = $chatId;
        $request->setText(function($text) use ($pushCount, $updateCount) {
            return $text->addText('Data Regional telah disinkronisasi dengan API New Osase.')
                ->newLine()->addSpace(4)->addItalic("Total Regional baru     : $pushCount")
                ->newLine()->addSpace(4)->addItalic("Total Regional diupdate : $updateCount");
        });
        return $request->send();
    }
}