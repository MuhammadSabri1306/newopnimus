<?php
namespace App\Core;

use Longman\TelegramBot\Entities\CallbackQuery;

class CallbackAnswer
{
    private $text;
    private $showAlert;
    private $cacheTime;

    public function __construct(string $text = null, bool $showAlert = null, int $cacheTime = null)
    {
        $this->text = $text;
        $this->showAlert = $showAlert;
        $this->cacheTime = $cacheTime;
    }

    public function answer(CallbackQuery $callbackQuery)
    {
        if(is_string($this->text)) {
            return $callbackQuery->answer([
                'text' => $this->text,
                'show_alert' => $this->showAlert,
                'cache_time' => $this->cacheTime,
            ]);
        }
        return $callbackQuery->answer();
    }
}