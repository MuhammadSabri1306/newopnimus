<?php
namespace App\Controller\Bot;

use App\Libraries\HttpClient\RestClient;
use App\Libraries\HttpClient\ResponseData as RestClientData;
use App\Libraries\HttpClient\Exceptions\ClientException;
use App\Libraries\HttpClient\Exceptions\DataNotFoundException;
use App\Controller\BotController;
use App\Model\TelegramAdmin;

class CronController extends BotController
{
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

    public static function nodeCronStatus()
    {
        $restClient = new RestClient();
        $restClient->request['headers'] = [ 'token' => \App\Config\AppConfig::$DENSUS_HOST_CLIENT_CREDENTIAL ];
        $apiUrl = 'https://densus.telkom.co.id/crons/node-crons/src/opnimus-alerting-port-v5/api/status.php';

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

        $request = static::request('TextDefault');
        $request->setTarget( static::getRequestTarget() );
        $request->setText(function($text) use ($nodeCronStatus) {
            $text->addBold('Status Node Cron Alerting OPNIMUS')->newLine()
                ->startCode()
                ->addText('opnimus-alerting-port-v5');
            foreach($nodeCronStatus as $moduleName => $isActive) {
                $statusIcon = $isActive ? '✅' : '⛔️';
                $text->newLine()->addText("  $statusIcon $moduleName");
            }
            $text->endCode();
            return $text;
        });
        return $request->send();
    }
}