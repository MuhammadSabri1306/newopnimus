<?php
namespace App\Core;

class TelegramText
{
    private $message;

    public function __construct(String $text = '') {
        $this->message = $text;
    }

    public static function create()
    {
        return new TelegramText();
    }

    public function get()
    {
        return $this->message;
    }

    public function addText(String $text)
    {
        $this->message .= $text;
        return $this;
    }

    public function newLine($count = 1)
    {
        for($i=1; $i<=$count; $i++) {
            $this->message .= PHP_EOL;
        }
        return $this;
    }

    public function startBold()
    {
        return $this->addText('*');
    }

    public function endBold()
    {
        return $this->addText('*');
    }

    public function startItalic()
    {
        return $this->addText('___');
    }

    public function endItalic()
    {
        return $this->addText('___');
    }

    public function startStrike()
    {
        return $this->addText('~~');
    }

    public function endStrike()
    {
        return $this->addText('~~');
    }

    public function startCode()
    {
        return $this->addText('```')->newLine();
    }

    public function endCode()
    {
        return $this->addText('```');
    }

    public function addTabspace()
    {
        return $this->addText('        ');
    }

    public function addMention($userId)
    {
        return $this->addText("[inline mention of a user](tg://user?id=$userId)");
    }
}