<?php
namespace App\TelegramRequest;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use App\Core\TelegramRequest;
use App\Core\TelegramText;

class TextAnswerSelect extends TelegramRequest
{
    public function __construct($questionText = '', $answerText = '')
    {
        parent::__construct();
        $this->setData('question', $questionText);
        $this->setData('answer', $answerText);
        $this->params->parseMode = 'markdown';
        $this->params->text = $this->getText()->get();
    }

    public function getText()
    {
        $questionText = $this->getData('question', '');
        $answerText = $this->getData('answer', '');
        return TelegramText::create($questionText)->newLine(2)
            ->addBold('=> ')->addText($answerText);
    }

    public function send(): ServerResponse
    {
        return Request::editMessageText($this->params->build());
    }
}