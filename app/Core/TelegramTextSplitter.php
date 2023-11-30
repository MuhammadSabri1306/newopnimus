<?php
namespace App\Core;

use App\Core\TelegramText;

trait TelegramTextSplitter
{
    protected $textMarks = [ 'code' => '```', 'bold' => '\\*', 'italic' => '___', 'strike' => '~~'];

    private function joinTextArr(array $textArr, string $codeMark, int $startIndex, int $endIndex)
    {
        $result = '';
        $index = $startIndex;
        $endIndex = min([ count($textArr) - 1, $endIndex ]);

        while($index < $endIndex) {
            if($index == $startIndex) {
                $result .= $textArr[$index];
            } else {
                $result .= PHP_EOL.$textArr[$index];
            }
            $index++;
        }

        if($startIndex <= $endIndex && $textArr[$endIndex] != $codeMark) {
            $result .= PHP_EOL.$textArr[$index];
            $index++;
        }


        return [ 'result_text' => $result, 'next_index' => $index ];
    }

    private function mantainTextMarks(string $text, array $blockMarks)
    {
        $startMark = '';
        $endMark = '';

        foreach($blockMarks as $mark) {
            if(strpos($text, $mark) !== false) {
                $startMark = $mark;
                $endMark = $mark;
                break;
            }
        }

        if(!empty($startMark)) {
            $startPos = strpos($text, $startMark);
            $endPos = strrpos($text, $endMark);

            if($startPos !== false && $endPos !== false && $startPos !== $endPos) {

                $before = substr($text, 0, $startPos);
                $after = substr($text, $endPos + strlen($endMark));
                return $before . $startMark . substr($text, $startPos + strlen($startMark), $endPos - $startPos - strlen($startMark)) . $endMark . $after;

            } elseif($startPos !== false && $endPos === false) {

                return $text . $endMark;

            } elseif($startPos === false && $endPos !== false) {

                return $startMark . $text;

            }
        }

        return $text;
    }

    public function splitText(string $text, $maxLines = 30)
    {
        $blockMarks = $this->textMarks;
        $textArr = explode(PHP_EOL, $text);
        $textArrLength = count($textArr);
        $results = [];

        $startIndex = 0;
        $endIndex = $maxLines - 1;
        $loop = true;

        do {

            $joinText = $this->joinTextArr($textArr, $blockMarks['code'], $startIndex, $endIndex);
            if(!empty($joinText['result_text'])) {
                
                $result = $this->mantainTextMarks($joinText['result_text'], $blockMarks);
                array_push($results, $result);

                $startIndex = $joinText['next_index'];
                $endIndex = $maxLines + $joinText['next_index'];
                $loop = $endIndex > $startIndex;

            } else {
                $loop = false;
            }
            
        } while($loop);

        $isPrevHasCodeMark = false;
        foreach($results as &$item) {

            if($isPrevHasCodeMark) {
                $item = TelegramText::create()->startCode()->addText($item)->get();
            }
            
            $countCodeMark = substr_count($item, $blockMarks['code']);
            if($countCodeMark > 0 && $countCodeMark % 2 != 0) {

                $item = TelegramText::create($item)->endCode()->get();
                $isPrevHasCodeMark = true;

            } else {
                $isPrevHasCodeMark = false;
            }

        }

        return $results;
    }
}