<?php
namespace App\Core;

class TelegramText
{
    private $message;

    public function __construct(string $text = '') {
        $this->message = $text;
    }

    public static function create(string $text = '')
    {
        return new TelegramText($text);
    }

    public function get()
    {
        return $this->message;
    }

    public function addText(string $text)
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

    public function addSpace($count = 1)
    {
        for($i=0; $i<$count; $i++) {
            $this->addText(' ');
        }
        return $this;
    }

    public function addMention($user, $text = null)
    {
        // user id
        if($text) {
            return $this->addText("[$text](tg://user?id=$user)");
        }

        // username
        return $this->addText("@$username");
    }
}