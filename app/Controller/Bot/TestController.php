<?php
namespace App\Controller\Bot;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InputMedia\InputMediaPhoto;
use Longman\TelegramBot\Entities\InputMedia\InputMediaDocument;

use Goat1000\SVGGraph\SVGGraph;

use App\Core\RequestData;
use App\Core\TelegramText;
use App\Core\CallbackData;
use App\Core\TelegramTextSplitter;
use App\Controller\BotController;
use App\Controller\Bot\AdminController;
use App\Controller\Bot\UserController;
use App\ApiRequest\NewosaseApi;

use App\Model\TelegramAdmin;
use App\Model\TelegramUser;
use App\Model\TelegramPersonalUser;
use App\Model\Registration;
use App\Model\Regional;
use App\Model\Witel;
use App\Model\RtuLocation;
use App\Model\AlertUsers;

class TestController extends BotController
{
    public static $callbacks = [
        'test.inkeyboard_encode' => 'onSelectInKeyboard',
    ];

    public static function run()
    {
        $message = TestController::$command->getMessage();
        $messageText = strtolower(trim($message->getText(true)));
        $chatId = $message->getChat()->getId();
        $params = explode(' ', $messageText);
        $moduleKey = array_shift($params);

        $modulesDevOnly = [
            'inkeyboard' => 'inKeyboard',
            'adminregistapproval' => 'adminRegistApproval',
            'errorlog' => 'errorLog',
            'errorresponse' => 'throwErrorResponse',
            'cmdexec' => 'executeCommandLine',
            'chart' => 'createChart',
            'registapproved' => 'whenRegistApproved',
            'userapprovedtext' => 'userApprovedText',
            'whenregistuser' => 'whenRegistUser',
            'whenregistpic' => 'whenRegistPic',
            'dynamictest' => 'dynamicTest',
        ];

        $modulesPublic = [
            'mychat' => 'getMyChat',
        ];

        if(in_array($moduleKey, array_keys($modulesDevOnly))) {

            if($chatId != \App\Config\AppConfig::$DEV_CHAT_ID) {
                return Request::sendMessage([
                    'chat_id' => $chatId,
                    'parse_mode' => 'markdown',
                    'text' => 'TEST command'.PHP_EOL.'___- Developer only___'
                ]);
            }

            $handler = $modulesDevOnly[$moduleKey];
            return static::$handler(...$params);

        }

        if(in_array($moduleKey, array_keys($modulesPublic))) {

            $handler = $modulesPublic[$moduleKey];
            return static::$handler(...$params);

        }

        if(!$moduleKey) {
            return static::$command->replyToChat('This is TEST Command.');
        }
        if($chatId == \App\Config\AppConfig::$DEV_CHAT_ID) {
            return static::$command->replyToChat('Test command\'s handler not found.');
        }
        return Request::emptyResponse();
    }

    public static function inKeyboard()
    {
        $message = TestController::$command->getMessage();
        $fromId = $message->getFrom()->getId();
        
        $reqData = new RequestData();
        $reqData->chatId = $message->getChat()->getId();
        $reqData->text = 'Test Inline Keyboard data.';

        $callbackData1 = 'satu';
        $callbackData2 = [ 13, 4, 5 ];
        $callbackData3 = [ 'id' => 3, 'n' => '3' ];

        $callbackData = new CallbackData('test.inkeyboard_encode');
        $callbackData->limitAccess($fromId);

        $reqData->replyMarkup = new InlineKeyboard([
            ['text' => 'Callback 1', 'callback_data' => $callbackData->createEncodedData($callbackData1)],
            ['text' => 'Callback 2', 'callback_data' => $callbackData->createEncodedData($callbackData2)],
            ['text' => 'Callback 3', 'callback_data' => $callbackData->createEncodedData($callbackData3)],
        ]);

        return Request::sendMessage($reqData->build());
    }

    public static function onSelectInKeyboard($callbackData, $callbackQuery)
    {
        $message = $callbackQuery->getMessage();
        $chatId = $message->getChat()->getId();

        $response = static::$command->replyToChat('Running.');
        BotController::sendDebugMessage([
            'chat_id' => $chatId,
            'data' => $callbackData
        ]);
        return $response;
    }

    public static function adminRegistApproval($type, $registId = null)
    {
        $message = TestController::$command->getMessage();
        
        $reqData = new RequestData();
        $reqData->chatId = $message->getChat()->getId();
        $reqData->parseMode = 'markdown';
        
        if(is_null($registId) || !in_array($type, [ 'user', 'pic', 'excalert' ])) {
            $reqData->text = TelegramText::create('Format:')
                ->addCode('/test adminregistapproval [type] [registration_id]')
                ->get();
            return Request::sendMessage($reqData->build());
        }

        $reqData->text = 'Test Regist Approval Admin, registId:'.$registId;
        $response = Request::sendMessage($reqData->build());

        if($type == 'user') {
            AdminController::whenRegistUser($registId);
        } elseif($type == 'pic') {
            AdminController::whenRegistPic($registId);
        } elseif($type == 'excalert') {
            AdminController::whenRequestAlertExclusion($registId);
        }
        return $response;
    }

    public static function errorLog()
    {
        $testClosure = function(array $data) {
            $result = $data['saya'];
            return $result;
        };

        $testClosure($data); // test warning error
        // $testClosure([]); // test warning error
        // $data = new UndefinedClass(); // test fatal error

        return Request::emptyResponse();
    }

    public static function throwErrorResponse()
    {
        $reqData = new RequestData();
        $reqData->chatId = '-40921168081231';
        $reqData->text = 'Test Throw Error Response';
        return Request::sendMessage($reqData->build());
    }

    public static function executeCommandLine($script)
    {
        
    }

    public static function createChart($portId)
    {
        $currDateTime = new \DateTime('now', new \DateTimeZone('Asia/Jakarta'));
        $currDateTime->setTime(0, 0, 0);
        $currTimestamp = $currDateTime->getTimestamp();
        $endTime = $currTimestamp * 1000;
        $startTime = ($currTimestamp - (24 * 3600)) * 1000;

        $newosaseApi = new NewosaseApi();
        $newosaseApi->setupAuth();
        $newosaseApi->request['query'] = [
            'start' => $startTime,
            'end' => $endTime,
            'timeframe' => 'hour',
            'is_formula' => 0
        ];

        $poolingData = $newosaseApi->sendRequest('GET', "/dashboard-service/operation/chart/pooling/$portId");
        if(!$poolingData) {
            $fetchErr = $newosaseApi->getErrorMessages()->response;
            $errMsg = isset($fetchErr->message) ? $fetchErr->message : '';
            $errCode = isset($fetchErr->code) ? $fetchErr->code : '';
            throw new \Error("Newosase API error with code:$errCode, message: $errMsg");
        }

        $applyTreshold = false;
        $newosaseApi->request['query'] = [];
        $tresholdData = $newosaseApi->sendRequest('GET', "/dashboard-service/operation/port/treshold/$portId");
        if(!$tresholdData) {
            $fetchErr = $newosaseApi->getErrorMessages()->response;
            $errMsg = isset($fetchErr->message) ? $fetchErr->message : '';
            $errCode = isset($fetchErr->code) ? $fetchErr->code : '';
            throw new \Error("Newosase API error with code:$errCode, message: $errMsg");
        }

        $tresholdTop = null;
        $tresholdBottom = null;

        if($tresholdData->result->rules && count($tresholdData->result->rules) > 0) {

            $treshold = $tresholdData->result->rules[0];
            $pattern = '/val\s*<\s*(\d+)\s*or\s*val\s*>\s*(\d+)/';

            if(preg_match($pattern, $treshold->rule, $tresholdMatches)) {
                $tresholdBottom = (int)$tresholdMatches[1];
                $tresholdTop = (int)$tresholdMatches[2];
                $applyTreshold = true;
            }
            
        }

        $chartData = [];
        $dataMax = [ 'title' => 'Maximum', 'color' => '#ff4560', 'dash' => null, 'data' => [] ];
        $dataAvg = [ 'title' => 'Average', 'color' => '#11d190', 'dash' => null, 'data' => [] ];
        $dataMin = [ 'title' => 'Minimum', 'color' => '#775dd0', 'dash' => null, 'data' => [] ];

        if($applyTreshold) {

            $chartStartDate = date('Y-m-d\TH:i', $startTime);
            $chartEndDate = date('Y-m-d\TH:i', $endTime);

            if($tresholdTop) {
                $tresholdTopData = [];
                $tresholdTopData[$chartStartDate] = $tresholdTop;
                $tresholdTopData[$chartEndDate] = $tresholdTop;

                array_push($chartData, [
                    'title' => 'Batas Atas',
                    'color' => '#008ffb',
                    'dash' => '5,3',
                    'data' => $tresholdTopData
                ]);
            }

            if($tresholdBottom) {
                $tresholdBottomData = [];
                $tresholdBottomData[$chartStartDate] = $tresholdBottom;
                $tresholdBottomData[$chartEndDate] = $tresholdBottom;

                array_push($chartData, [
                    'title' => 'Batas Bawah',
                    'color' => '#775dd0',
                    'dash' => '5,3',
                    'data' => $tresholdBottomData
                ]);
            }

        }

        foreach($poolingData->result as $item) {

            $itemDate = date('Y-m-d\TH:i', $item->timestamps / 1000);

            if(isset($item->value_max)) {
                $dataMax['data'][$itemDate] = $item->value_max;
            }

            if(isset($item->value_avg)) {
                $dataAvg['data'][$itemDate] = $item->value_avg;
            }

            if(isset($item->value_min)) {
                $dataMin['data'][$itemDate] = $item->value_min;
            }

        }

        array_push($chartData, $dataMax, $dataAvg, $dataMin);

        $imgWidth = 300;
        $imgHeight = 200;

        $settings = [
            'auto_fit' => true,
            'back_colour' => '#fff',
            'back_stroke_width' => 0,
            'back_stroke_colour' => '#eee',
        
            'axis_colour' => '#f4f4f4',
            'axis_text_colour' => '#373d3f',
            'axis_overlap' => 2,
            'grid_colour' => '#f4f4f4',
            'axis_font' => 'Arial',
            'axis_font_size' => 6,
            'axis_text_angle_h' => -45,
        
            'fill_under' => array_map(fn($item) => true, $chartData),
            'label_colour' => '#373d3f',
        
            'pad_right' => 10,
            'pad_left' => 10,
            'pad_top' => 10,
            'pad_bottom' => 40,
        
            'marker_type' => array_map(fn($item) => 'circle', $chartData),
            'marker_size' => 0,
            'marker_colour' => array_map(fn($item) => $item['color'], $chartData),
            'show_grid_h' => true,
            'show_grid_v' => false,
        
            'line_curve' => 0.4,
            'line_stroke_width' => 2,
            'line_dash' => array_map(fn($item) => $item['dash'], $chartData),
        
            'datetime_keys' => true,
            'datetime_text_format' => 'M-d H:i',
        
            'legend_columns' => 3,
            'legend_entry_height' => 10,
            'legend_text_side' => 'left',
            'legend_position' => 'outer bottom 40 -10',
            'legend_font_size' => 6,
            'legend_stroke_width' => 0,
            'legend_shadow_opacity' => 0,
            'legend_draggable' => false,
            'legend_back_colour' => '#fff',
            'legend_entries' => array_map(fn($item) => $item['title'], $chartData)
        ];
        
        $graph = new SVGGraph($imgWidth, $imgHeight, $settings);
        $graph->colours(array_map(function($item) {
            $color = $item['color'];
            return [ "$color:0", "$color:0" ];
        }, $chartData));
        
        $graph->values(array_map(fn($item) => $item['data'], $chartData));
        $svgImg = $graph->fetch('MultiLineGraph');

        // $reqData = new RequestData();
        // $reqData->chatId = $message->getChat()->getId();
        // $reqData->parseMode = 'markdown';

        $response = Request::sendMediaGroup([
            'chat_id' => 1931357638,
            'media' => [
                new InputMediaDocument([ 'media' => 'attach:file1.svg' ])
            ],
            'file1.svg' => $svgImg
            // 'file1.png' => new \CURLFile('https://juarayya.ap.ngrok.io/newopnimus/test/test-create-chart-2-2.png')
            // 'file1.webp' => new \CURLFile('https://densus.telkom.co.id/assets/img/Gepee-logo.webp')
        ]);
        BotController::sendDebugMessage($response);
        return $response;
    }

    public static function whenRegistApproved($telegramUserId = 70)
    {
        $chatId = static::$command->getMessage()->getChat()->getId();
        
        $telegramUser = TelegramUser::find($telegramUserId);
        BotController::sendDebugMessage([ 'telegram_user' => $telegramUser ]);

        $request = BotController::request('Registration/TextApproved');
        $request->params->chatId = $chatId;

        $request->setIsPrivate($telegramUser['type'] == 'private', false);

        if(!$telegramUser['is_pic'] && $telegramUser['level'] == 'witel') {
            $group = TelegramUser::findAlertWitelGroup($telegramUser['witel_id']);
        } elseif(!$telegramUser['is_pic'] && $telegramUser['level'] == 'regional') {
            $group = TelegramUser::findAlertRegionalGroup($telegramUser['regional_id']);
        }
        
        if(isset($group, $group['username']) && $telegramUser['id'] != $group['id']) {
            $request->setAlertingGroup($group['username'], false);
        }

        $request->setApprovedAt($telegramUser['created_at']);

        $response = $request->send();
        if(!$response->isOk()) {
            static::sendErrorMessage();
        }
        return $response;
    }

    public static function userApprovedText()
    {
        $response = UserController::whenRegistApproved(120);
        return $response;
    }

    public static function whenRegistUser($registId)
    {
        $registData = Registration::find($registId);

        $request = BotController::request('Registration/SelectAdminApproval');
        $request->setRegistrationData($registData);
        
        if(in_array($registData['data']['level'], [ 'regional', 'witel' ])) {
            $regional = Regional::find($registData['data']['regional_id']);
            $request->setRegional($regional);
        }

        if($registData['data']['level'] == 'witel') {
            $witel = Witel::find($registData['data']['witel_id']);
            $request->setWitel($witel);
        }

        $request->setInKeyboard(function($inlineKeyboardData) use ($registId) {
            $inlineKeyboardData['approve']['callback_data'] = 'admin.user_approval.approve:'.$registId;
            $inlineKeyboardData['reject']['callback_data'] = 'admin.user_approval.reject:'.$registId;
            return $inlineKeyboardData;
        });

        $request->params->chatId = \App\Config\AppConfig::$DEV_CHAT_ID;;
        return $request->send();
    }

    public static function whenRegistPic($registId)
    {
        $regist = Registration::find($registId);
        if(!$regist) return null;

        $pic = $regist['data'];
        $pic['request_level'] = 'pic';
        if($pic['has_regist']) {

            $telgUser = TelegramUser::find($pic['telegram_user_id']);
            $telgPersUser = TelegramPersonalUser::findByUserId($pic['telegram_user_id']);

            $pic['full_name'] = $telgPersUser['nama'];
            $pic['telp'] = $telgPersUser['telp'];
            $pic['level'] = $telgUser['level'];
            $pic['nik'] = $telgPersUser['nik'];
            $pic['is_organik'] = $telgPersUser['is_organik'];
            $pic['instansi'] = $telgPersUser['instansi'];
            $pic['unit'] = $telgPersUser['unit'];
            $pic['regional_id'] = $telgUser['regional_id'];
            $pic['witel_id'] = $telgUser['witel_id'];

        }

        $admins = TelegramAdmin::getByUserArea($pic, 'request_level');
        if(count($admins) < 1) return;

        $request = static::request('Registration/SelectAdminPicApproval');
        $request->setRegistrationData($pic);

        $regional = Regional::find($pic['regional_id']);
        $request->setRegional($regional);

        $witel = Witel::find($pic['witel_id']);
        $request->setWitel($witel);

        $locations = RtuLocation::getByIds($pic['locations']);
        $request->setLocations($locations);

        $callbackData = new CallbackData('admin.picaprv');
        $request->setInKeyboard(function($inlineKeyboardData) use ($registId, $callbackData) {
            $inlineKeyboardData['approve']['callback_data'] = $callbackData->createEncodedData([
                'i' => $registId, 'a' => 1
            ]);
            $inlineKeyboardData['reject']['callback_data'] = $callbackData->createEncodedData([
                'i' => $registId, 'a' => 0
            ]);
            return $inlineKeyboardData;
        });

        foreach($admins as $admin) {
            if($admin['chat_id'] == \App\Config\AppConfig::$DEV_CHAT_ID) {
                $request->params->chatId = $admin['chat_id'];
                $request->send();
            }
        }

        return static::sendEmptyResponse();
    }

    public static function getMyChat()
    {
        $message = static::$command->getMessage();
        $chatId = $message->getChat()->getId();
        $chatType = $message->getChat()->getType();
        $messageThreadId = $message->getMessageThreadId();

        $data = json_encode($message, JSON_PRETTY_PRINT);

        $reqData = New RequestData();
        $reqData->parseMode = 'markdown';
        $reqData->chatId = $chatId;
        if($chatType == 'supergroup') $reqData->messageThreadId = $messageThreadId;
        $reqData->text = TelegramText::create('Chat data structure')->newLine()
            ->addCode($data)
            ->get();

        return Request::sendMessage($reqData->build());
    }

    public static function dynamicTest()
    {
        $message = UserController::$command->getMessage();
        static::setRequestTarget($message);

        $registId = 616;
        $regist = Registration::find($registId);
        if(!$regist) return null;

        $pic = $regist['data'];
        $pic['request_level'] = 'pic';
        if($pic['has_regist']) {

            $telgUser = TelegramUser::find($pic['telegram_user_id']);
            $telgPersUser = TelegramPersonalUser::findByUserId($pic['telegram_user_id']);

            $pic['full_name'] = $telgPersUser['nama'];
            $pic['username'] = $telgUser['username'];
            $pic['user_id'] = $telgUser['user_id'];
            $pic['telp'] = $telgPersUser['telp'];
            $pic['level'] = $telgUser['level'];
            $pic['nik'] = $telgPersUser['nik'];
            $pic['is_organik'] = $telgPersUser['is_organik'];
            $pic['instansi'] = $telgPersUser['instansi'];
            $pic['unit'] = $telgPersUser['unit'];
            $pic['regional_id'] = $telgUser['regional_id'];
            $pic['witel_id'] = $telgUser['witel_id'];

        }

        $request = static::request('Registration/SelectAdminPicApproval');
        $request->setRegistrationData($pic);

        $regional = Regional::find($pic['regional_id']);
        $request->setRegional($regional);

        $witel = Witel::find($pic['witel_id']);
        $request->setWitel($witel);

        $locations = RtuLocation::getByIds($pic['locations']);
        $request->setLocations($locations);

        $callbackData = new CallbackData('admin.picaprv');
        $request->setInKeyboard(function($inlineKeyboardData) use ($registId, $callbackData) {
            $inlineKeyboardData['approve']['callback_data'] = $callbackData->createEncodedData([
                'i' => $registId, 'a' => 1
            ]);
            $inlineKeyboardData['reject']['callback_data'] = $callbackData->createEncodedData([
                'i' => $registId, 'a' => 0
            ]);
            return $inlineKeyboardData;
        });

        $response = $request->send();
        static::sendDebugMessage($response);
        return $response;
    }
}