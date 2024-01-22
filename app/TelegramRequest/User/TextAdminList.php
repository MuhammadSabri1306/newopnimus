<?php
namespace App\TelegramRequest\User;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use App\Core\TelegramRequest;
use App\Core\TelegramText;
use App\Core\TelegramRequest\TextList;
use App\Core\TelegramRequest\PortFormat;
use App\Helper\ArrayHelper;

class TextAdminList extends TelegramRequest
{
    use TextList, PortFormat;

    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'markdown';
        $this->params->text = $this->getText()->get();
    }

    public function getText()
    {
        $nasionalAdmins = $this->getData('nasional_admins', []);
        $regionalAdmins = $this->getData('regional_admins', []);
        $witelAdmins = $this->getData('witel_admins', []);

        $text = TelegramText::create()
            ->addBold('List Admin terdaftar')->newLine(2)
            ->addText('🏢 ')->addBold('NASIONAL');
        if(count($nasionalAdmins) < 1) {
            $text->newLine()->addItalic('- Belum ada admin Nasional');
        } else {
            foreach($nasionalAdmins as $admin) {
                $adminName = implode(' ', array_filter([ $admin['first_name'], $admin['last_name'] ]));
                $text->newLine()
                    ->addText(' - ')
                    ->addMentionByName($admin['chat_id'], $adminName);
            }
        }

        if(count($regionalAdmins) > 0) {
            foreach($regionalAdmins as $regional) {
                $text->newLine(2)
                    ->addText('🏤 ')->addBold($regional['regional_name']);
                foreach($regional['admins'] as $admin) {
                    $adminName = implode(' ', array_filter([ $admin['first_name'], $admin['last_name'] ]));
                    $text->newLine()
                        ->addText(' - ')
                        ->addMentionByName($admin['chat_id'], $adminName);
                }
            }
        }

        if(count($witelAdmins) > 0) {
            foreach($witelAdmins as $witel) {
                $text->newLine(2)
                    ->addText('🏬 ')->addBold($witel['witel_name']);
                foreach($witel['admins'] as $admin) {
                    $adminName = implode(' ', array_filter([ $admin['first_name'], $admin['last_name'] ]));
                    $text->newLine()
                        ->addText(' - ')
                        ->addMentionByName($admin['chat_id'], $adminName);
                }
            }
        }

        return $text;
    }

    public function setAdmins($admins)
    {
        if(is_array($admins)) {
            
            $nasionalAdmins = [];
            $regionalAdmins = [];
            $witelAdmins = [];
            foreach($admins as $admin) {
                if($admin['level'] == 'witel') {

                    $witelId = $admin['witel_id'];
                    $index = ArrayHelper::findIndex($witelAdmins, fn($item) => $item['witel_id'] == $witelId);
                    if($index < 0) {
                        array_push($witelAdmins, [
                            'witel_id' => $witelId,
                            'witel_name' => $admin['witel_name'],
                            'admins' => [ $admin ]
                        ]);
                    } else {
                        array_push($witelAdmins[$index]['admins'], $admin);
                    }
                    
                } elseif($admin['level'] == 'regional') {

                    $regionalId = $admin['regional_id'];
                    $index = ArrayHelper::findIndex($regionalAdmins, fn($item) => $item['regional_id'] == $regionalId);
                    if($index < 0) {
                        array_push($regionalAdmins, [
                            'regional_id' => $regionalId,
                            'regional_name' => $admin['regional_name'],
                            'admins' => [ $admin ]
                        ]);
                    } else {
                        array_push($regionalAdmins[$index]['admins'], $admin);
                    }
                    
                } else {
                    array_push($nasionalAdmins, $admin);
                }
            }

            $this->setData('nasional_admins', $nasionalAdmins);
            $this->setData('regional_admins', $regionalAdmins);
            $this->setData('witel_admins', $witelAdmins);
            $this->params->text = $this->getText()->get();

        }
    }

    public function send(): ServerResponse
    {
        $text = $this->params->text;
        $messageTextList = $this->splitText($text, 50);

        if(count($messageTextList) < 2) {
            return Request::sendMessage($this->params->build());
        }
        return $this->sendList($messageTextList);
    }
}