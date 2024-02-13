<?php
namespace App\TelegramRequest\Registration;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Entities\Keyboard;
use App\Core\TelegramRequest;
use App\Core\TelegramText;

class AnimationTou extends TelegramRequest
{
    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'markdown';
        $this->params->caption = $this->getCaption()->get();
        $this->params->animation = 'https://media1.giphy.com/media/v1.'.
            'Y2lkPTc5MGI3NjExcXVmeGxnY21sMGQ5ZG94ZDA2emNiZzZodWk0NW9pamRjejNtYmdoZCZlcD12MV'.
            '9pbnRlcm5hbF9naWZfYnlfaWQmY3Q9Zw/Bf3Anv7HuOPHEPkiOx/giphy.gif';
    }

    public function getCaption()
    {
        return TelegramText::create()
            ->addText('Hello! Welcome Heroes! selamat datang di ')
            ->startBold()->addText('OPNIMUS')->endBold()
            ->newLine()->newLine()
            ->startInlineCode()
            ->addText('OPNIMUS (Operational Network Infra Monitoring & Surveillance System)')
            ->addText(' adalah sebuah sistem monitoring kondisi kesehatan network')
            ->addText(' element secara realtime dan sistem early warning terhadap potensi gangguan')
            ->addText(' karena permasalahan fisik (catuan, suhu, arus listrik).')
            ->endInlineCode();
    }

    public function send(): ServerResponse
    {
        return Request::sendAnimation($this->params->build());
    }
}