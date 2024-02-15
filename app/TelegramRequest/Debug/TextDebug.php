<?php
namespace App\TelegramRequest\Debug;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use App\Core\TelegramText;
use App\Core\TelegramRequest;
use App\Core\TelegramRequest\TextList;

class TextDebug extends TelegramRequest
{
    use TextList;

    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'markdown';
        $this->params->text = $this->getText()->get();
    }

    public function getText()
    {
        $data = $this->getData('data', null);
        $toJson = $this->getData('to_json', true);
        $toCode = $this->getData('to_Code', true);

        if($toJson) {
            $data = json_encode($data, JSON_INVALID_UTF8_IGNORE);
        }

        $text = new TelegramText();
        if($toCode) {
            $text->addCode($data);
        } else {
            $text->addText($data);
        }

        return $text;
    }

    public function setDebugData($data, $config = [])
    {
        $request->setData('data', $data);
        if(isset($config['toCode'])) $request->setData('to_code', $config['toCode']);
        if(isset($config['toJson'])) $request->setData('to_json', $config['toJson']);
        $this->params->text = $this->getText()->get();
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