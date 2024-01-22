<?php
namespace App\Core;

class TelegramText
{
    private $message;
    private $maxLength = 4096;

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

    public function clear()
    {
        $this->message = '';
        return $this;
    }

    public function getSplittedByLine($maxLine = 10)
    {
        $blockMarks = ['```', '\\*', '___', '~~'];
        $blockMarksJoin = implode('|', $blockMarks);
        $regex = "/((?:(?:$blockMarksJoin)\\s*(?:.|\\s)*?\\s*(?:$blockMarksJoin))|\\R+)/";
        $parts = preg_split($regex, $this->message, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        $splittedText = [];
        $tempParts = [];
        $lineCount = 0;

        foreach($parts as $part) {
            $lineCount++;
            array_push($tempParts, $part);
            if($lineCount === $maxLine) {
                $joinedPart = implode('', $tempParts);
                array_push($splittedText, $joinedPart);
                $tempParts = [];
                $lineCount = 0;
            }
        }
        if(count($tempParts) > 0) {
            $joinedPart = implode('', $tempParts);
            array_push($splittedText, $joinedPart);
        }

        return $splittedText;
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

    public function addBold(string $text)
    {
        return $this->startBold()->addText($text)->endBold();
    }

    public function startItalic()
    {
        return $this->addText('___');
    }

    public function endItalic()
    {
        return $this->addText('___');
    }

    public function addItalic(string $text)
    {
        return $this->startItalic()->addText($text)->endItalic();
    }

    public function startStrike()
    {
        return $this->addText('~~');
    }

    public function endStrike()
    {
        return $this->addText('~~');
    }

    public function addStrike(string $text)
    {
        return $this->startStrike()->addText($text)->endStrike();
    }

    public function startCode()
    {
        return $this->addText('```')->newLine();
    }

    public function endCode()
    {
        return $this->addText('```');
    }

    public function addCode(string $text)
    {
        return $this->startCode()->addText($text)->endCode();
    }

    public function startInlineCode()
    {
        return $this->addText('`');
    }

    public function endInlineCode()
    {
        return $this->addText('`');
    }

    public function addInlineCode(string $text)
    {
        return $this->startInlineCode()->addText($text)->endInlineCode();
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
        return $this->addText("@$user");
    }

    public function addMentionByName($userId, $name)
    {
        return $this->addText("[$name](tg://user?id=$userId)");
    }

    public function addMentionByUsername($userId, $username)
    {
        return $this->addText("[$username](tg://user?id=$userId)");
    }
}