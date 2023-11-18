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
use App\Controller\BotController;
use App\Controller\Bot\AdminController;
use App\ApiRequest\NewosaseApi;
use App\Model\TelegramUser;

class TestController extends BotController
{
    protected static $callbacks = [
        'test.inkeyboard_json' => 'onSelectInKeyboardJson',
    ];

    public static function run()
    {
        $message = TestController::$command->getMessage();
        $messageText = strtolower(trim($message->getText(true)));
        $params = explode(' ', $messageText);
        $modulKey = array_shift($params);

        switch($modulKey) {
            case 'inkeyboardjson': return TestController::inKeyboardJson(...$params); break;
            case 'adminregistapproval': return TestController::adminRegistApproval(...$params); break;
            case 'errorlog': return TestController::errorLog(...$params); break;
            case 'errorresponse': return TestController::throwErrorResponse(...$params); break;
            case 'cmdexec': return TestController::executeCommandLine(...$params); break;
            case 'chart': return TestController::createChart(...$params); break;
            case 'registapproved': return TestController::whenRegistApproved(...$params); break;
            default: return TestController::$command->replyToChat('This is TEST Command.');
        }
    }

    public static function inKeyboardJson()
    {
        $message = TestController::$command->getMessage();
        
        $reqData = new RequestData();
        $reqData->chatId = $message->getChat()->getId();
        $reqData->text = 'Test Inline Keyboard data berupa JSON.';

        function encodeKeyboardData($name, $data) {
            $dataJson = json_encode($data);
            return "$name.$dataJson";
        }

        $callbackData1 = [ 'id' => 1, 'name' => 'callback data 1' ];
        $callbackData2 = [ 'id' => 2, 'name' => 'callback data 2' ];
        $callbackData3 = [ 'id' => 3, 'name' => 'callback data 3' ];

        $reqData->replyMarkup = new InlineKeyboard([
            ['text' => 'Callback 1', 'callback_data' => encodeKeyboardData('test.inkeyboard_json', $callbackData1)],
            ['text' => 'Callback 2', 'callback_data' => encodeKeyboardData('test.inkeyboard_json', $callbackData2)],
            ['text' => 'Callback 3', 'callback_data' => encodeKeyboardData('test.inkeyboard_json', $callbackData3)],
        ]);

        return Request::sendMessage($reqData->build());
    }

    public static function adminRegistApproval($registId = null)
    {
        $message = TestController::$command->getMessage();
        
        $reqData = new RequestData();
        $reqData->chatId = $message->getChat()->getId();
        $reqData->parseMode = 'markdown';
        
        if(is_null($registId)) {
            $reqData->text = TelegramText::create('Format:')
                ->addCode('/test adminregistapproval [registration_id]')
                ->get();
            return Request::sendMessage($reqData->build());
        }

        $reqData->text = 'Test Regist Approval Admin, registId:'.$registId;
        $response = Request::sendMessage($reqData->build());

        AdminController::whenRegistUser($registId);
        return $response;
    }

    public static function onSelectInKeyboardJson($callbackData, $callbackQuery)
    {
        $message = $callbackQuery->getMessage();
        $callbackData = json_decode($callbackData);
        
        $reqData = new RequestData();
        $reqData->parseMode = 'markdown';
        $reqData->chatId = $message->getChat()->getId();
        $reqData->text = TelegramText::create()
            ->addBold('Data Callback')->newLine(1)
            ->startCode()
            ->addText("ID   : $callbackData->id")->newLine()
            ->addText("NAME : $callbackData->name")
            ->endCode()
            ->get();

        return Request::sendMessage($reqData->build());
    }

    public static function errorLog()
    {
        $test = testError();
        // try {


        // } catch(\Throwable $err) {
        //     $test = \MuhammadSabri1306\MyBotLogger\Entities\ErrorLogger::catch($err);
        //     BotController::sendDebugMessage($test, [ 'toJson' => false ]);
        // } finally {
        //     // return Request::emptyResponse();
        //     return TestController::$command->replyToChat('TEST Logger');
        // }
        // BotController::sendDebugMessage(\MuhammadSabri1306\MyBotLogger\Logger::$botUsername);
        // return TestController::$command->replyToChat('TEST Logger');
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
            // 'file1.png' => new \CURLFile('https://juarayya.ngrok.io/newopnimus/test/test-create-chart-2-2.png')
            // 'file1.webp' => new \CURLFile('https://densus.telkom.co.id/assets/img/Gepee-logo.webp')
        ]);
        BotController::sendDebugMessage($response);
        return $response;
    }

    public static function whenRegistApproved(string $chatType)
    {
        $telegramUserId = 26;
        $chatId = static::$command->getMessage()->getChat()->getId();
        $telegramUser = TelegramUser::find($telegramUserId);

        $request = BotController::request('Registration/TextApproved');
        $request->params->chatId = $chatId;
        $request->setIsPrivate($chatType == 'private', false);
        if($request->getData('is_private')) {
            $request->setAlertingGroup($telegramUser['username'], false);
        }
        $request->setApprovedAt($telegramUser['created_at']);

        $approvedAt = $request->getData('approved_at', null);
        $isPrivate = $request->getData('is_private', true);
        $groupTitle = $request->getData('group_title', null);

        $response = $request->send();
        if(!$response->isOk()) {
            static::sendErrorMessage();
        }
        return $response;
    }
}