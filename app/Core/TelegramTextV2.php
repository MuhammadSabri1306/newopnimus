<?php
namespace App\Core;

/*
 * For Markdown V2 use
 */
class TelegramTextV2
{
    private $message;
    private $maxLength = 4096;

    public function __construct(string $text = '') {
        $this->message = '';
        $this->addText($text);
    }

    public function escape(string $text)
    {
        $escapedChars = ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];
        foreach($escapedChars as $eChar) {
            $text = str_replace($eChar, "\\$eChar", $text);
        }
        return $text;
    }

    public static function create(string $text = '')
    {
        return new TelegramTextV2($text);
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

    public function addText(string $text, $escape = true)
    {
        if($escape) $text = $this->escape($text);
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
        return $this->addText('*', false);
    }

    public function endBold()
    {
        return $this->addText('*', false);
    }

    public function addBold(string $text)
    {
        return $this->startBold()->addText($text)->endBold();
    }

    public function startItalic()
    {
        return $this->addText('_', false);
    }

    public function endItalic()
    {
        return $this->addText('_', false);
    }

    public function addItalic(string $text)
    {
        return $this->startItalic()->addText($text)->endItalic();
    }

    public function startUnderline()
    {
        return $this->addText('__', false);
    }

    public function endUnderline()
    {
        return $this->addText('__', false);
    }

    public function addUnderline(string $text)
    {
        return $this->startUnderline()->addText($text)->endUnderline();
    }

    public function startStrike()
    {
        return $this->addText('~', false);
    }

    public function endStrike()
    {
        return $this->addText('~', false);
    }

    public function addStrike(string $text)
    {
        return $this->startStrike()->addText($text)->endStrike();
    }

    public function startCode()
    {
        return $this->addText('```', false)->newLine();
    }

    public function endCode()
    {
        return $this->addText('```', false);
    }

    public function addCode(string $text)
    {
        return $this->startCode()->addText($text)->endCode();
    }

    public function startQuote(string $text = '')
    {
        return $this->addText(">$text", false);
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

    public function addLink($url, $text)
    {
        return $this->addText('[', false)
            ->addText($text)
            ->addText(']', false)
            ->addText("($url)", false);
    }

    public function addMention($userId, $name)
    {
        return $this->addLink("tg://user?id=$userId", $name);
    }
}