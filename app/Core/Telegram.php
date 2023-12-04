<?php
namespace App\Core;

use Longman\TelegramBot\Entities\CallbackQuery;
use Longman\TelegramBot\Entities\Update;

class Telegram extends \Longman\TelegramBot\Telegram
{
    public function executeCommandFromCallbackquery (string $_command, CallbackQuery $_callback_query)
    {
        $updateData = [
            'update_id' => 0,
            'message'   => [
                'message_id' => 0,
                'from'       => $_callback_query->getFrom ()->getRawData (),
                'date'       => \time (),
                'text'       => '',
            ]
        ];

        if($_callback_query->getMessage ()) {
            $updateData['message']['chat'] = $_callback_query->getMessage ()->getChat ()->getRawData ();
        }

        $this->update = new Update ($updateData, $this->getBotUsername ());
        return $this->executeCommand ($_command);
    }
}