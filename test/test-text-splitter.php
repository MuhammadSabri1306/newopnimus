<?php
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/test-long-message/message-2.php';

use App\Core\TelegramTextSplitter;

class TestSplitter
{
    use TelegramTextSplitter;

    public $text;

    public function split()
    {
        $text = $this->text;
        $textArr = $this->splitText($text, 30);
        return $textArr;
    }
}

$case = new TestSplitter();
$case->text = $messageText;
$result = $case->split();
dd($result);