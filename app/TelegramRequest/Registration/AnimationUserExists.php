<?php
namespace App\TelegramRequest\Registration;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use App\Core\TelegramRequest;
use App\Core\TelegramText;
use App\Core\Exception\TelegramResponseException;

class AnimationUserExists extends TelegramRequest
{
    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'markdown';
        $this->params->animation = 'https://media1.giphy.com/media/v1.'.
            'Y2lkPTc5MGI3NjExcXVmeGxnY21sMGQ5ZG94ZDA2emNiZzZodWk0NW9pamRjejNtYmdoZCZlcD12MV'.
            '9pbnRlcm5hbF9naWZfYnlfaWQmY3Q9Zw/Bf3Anv7HuOPHEPkiOx/giphy.gif';
    }

    public function getCaption()
    {
        $name = $this->getData('name', '');
        return TelegramText::create()
            ->addBold("$name sudah terdaftar dalam OPNIMUS:")->newLine(2)
            ->addText('Silahkan pilih /help untuk petunjuk lebih lanjut.');
    }

    public function setName($name)
    {
        $this->setData('name', $name);
        $this->params->caption = $this->getCaption()->get();
    }

    public function send($throwErr = true): ServerResponse
    {
        $response = Request::sendAnimation($this->params->build());
        if($throwErr && !$response->isOk()) {
            throw new TelegramResponseException($response);
        }
        return $response;
    }
}