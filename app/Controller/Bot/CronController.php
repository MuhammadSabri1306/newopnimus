<?php
namespace App\Controller\Bot;

use MeekroDB;
use App\Config\AppConfig;
use App\Core\CallbackData;
use App\Libraries\HttpClient\RestClient;
use App\Libraries\HttpClient\ResponseData as RestClientData;
use App\Libraries\HttpClient\Exceptions\ClientException;
use App\Libraries\HttpClient\Exceptions\DataNotFoundException;
use App\Controller\BotController;
use App\Model\TelegramAdmin;

class CronController extends BotController
{
    public static $callbacks = [
        'cron.sts' => 'onSelectStatusView',
    ];

    protected static function getSuperAdmin()
    {
        $chatId = static::getMessage()->getChat()->getId();
        $admin = TelegramAdmin::findByChatId($chatId);
        return ($admin && $admin['is_super_admin']) ? $admin : null;
    }

    public static function isSuperAdmin()
    {
        return static::getSuperAdmin() ? true : false;
    }

    public static function cronStatus()
    {
        if(!static::isSuperAdmin()) {
            return static::sendEmptyResponse();
        }

        $request = static::request('SelectDefault');
        $request->setTarget( static::getRequestTarget() );
        $request->setText('Silahkan pilih View.');

        $callbackData = new CallbackData('cron.sts');
        // 📈🧮💡💼💾🗄
        $request->setInKeyboard([
            [[ 'text' => '💡 Status NODE CRON', 'callback_data' => $callbackData->createEncodedData('node') ]],
            [[ 'text' => '📈 CPU Usages', 'callback_data' => $callbackData->createEncodedData('cpu') ]],
            [[ 'text' => '💾 Status MySQL', 'callback_data' => $callbackData->createEncodedData('mysql') ]],
        ]);

        return $request->send();
    }

    public static function onSelectStatusView($view)
    {
        if(static::isSuperAdmin()) {
            if($view == 'node') return static::showNodeCronStatus();
            if($view == 'cpu') return static::showCpuUsage();
            if($view == 'mysql') return static::showMySqlStatus();
        }
        return static::sendEmptyResponse();
    }

    public static function showNodeCronStatus()
    {
        $restClient = new RestClient();
        $restClient->request['headers'] = [ 'token' => AppConfig::$DENSUS_HOST_CLIENT_CREDENTIAL ];
        $restClient->request['query'] = [ 'view' => 'cronstatus' ];
        $apiUrl = AppConfig::$DENSUS_HOST_STATUS_URL;

        $request = static::request('Action/Typing');
        $request->setTarget( static::getRequestTarget() );
        $request->send();

        $nodeCronKeys = [ 'watch-newosase-alarm', 'watch-alert' ];
        $nodeCronStatus = [];
        try {

            $apiData = $restClient->sendRequest('GET', $apiUrl);
            foreach($nodeCronKeys as $processKey) {
                $nodeCronStatus[$processKey] = $apiData->find("result.$processKey", RestClientData::EXPECT_BOOLEAN);
            }

        } catch(ClientException $err) {
            $request = static::request('Error/TextErrorServer');
            $request->setTarget( static::getRequestTarget() );
            return $request->send();
        } catch(DataNotFoundException $err) {
            $request = static::request('Error/TextErrorNotFound');
            $request->setTarget( static::getRequestTarget() );
            return $request->send();
        }

        $request = static::request('CronAlerting/TextNodeCronStatus');
        $request->setTarget( static::getRequestTarget() );
        $request->setStatus($nodeCronStatus);
        return $request->send();
    }

    public static function showCpuUsage()
    {
        $restClient = new RestClient();
        $restClient->request['headers'] = [ 'token' => AppConfig::$DENSUS_HOST_CLIENT_CREDENTIAL ];
        $restClient->request['query'] = [ 'view' => 'cpuusage' ];
        $apiUrl = AppConfig::$DENSUS_HOST_STATUS_URL;

        $request = static::request('Action/Typing');
        $request->setTarget( static::getRequestTarget() );
        $request->send();

        $cpuPercentNodeAll = null;
        $cpuPercentNodeCron = null;
        $cpuProcesses = [];
        try {

            $apiData = $restClient->sendRequest('GET', $apiUrl);
            $cpuPercentNodeAll = $apiData->find('result.cpu_usage.node_all');
            $cpuPercentNodeCron = $apiData->find('result.cpu_usage.node_cron');
            $cpuProcesses = $apiData->find('result.processes', RestClientData::EXPECT_ARRAY);

        } catch(ClientException $err) {
            $request = static::request('Error/TextErrorServer');
            $request->setTarget( static::getRequestTarget() );
            return $request->send();
        } catch(DataNotFoundException $err) {
            $request = static::request('Error/TextErrorNotFound');
            $request->setTarget( static::getRequestTarget() );
            return $request->send();
        }

        $request = static::request('CronAlerting/TextCpuUsage');
        $request->setTarget( static::getRequestTarget() );
        $request->setCpuProcesses($cpuProcesses);
        $request->setCpuUsage($cpuPercentNodeAll, $cpuPercentNodeCron);
        return $request->send();
    }

    public static function showMySqlStatus()
    {
        $dbConfig = AppConfig::$DATABASE->default;
        $db = new MeekroDB($dbConfig->host, $dbConfig->username, $dbConfig->password, $dbConfig->name);

        $poolCount = $db->queryFirstField('SELECT COUNT(*) FROM information_schema.PROCESSLIST');
        
        $request = static::request('TextDefault');
        $request->setTarget( static::getRequestTarget() );
        $request->setText(function($text) use ($dbConfig, $poolCount) {
            return $text->addBold('Status MySQL OPNIMUS')->newLine()
                ->startCode()
                ->addText("Host      : $dbConfig->host")->newLine()
                ->addText("Database  : $dbConfig->name")->newLine()
                ->addText('Status    : Connected')->newLine()
                ->addText("Processes : $poolCount")
                ->endCode();
        });
        return $request->send();
    }
}