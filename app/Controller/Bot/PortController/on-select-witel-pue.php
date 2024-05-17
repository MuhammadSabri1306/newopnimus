<?php

use App\Core\CallbackData;
use App\Model\Witel;

$message = static::getMessage();
$fromId = static::getFrom()->getId();
$chatId = $message->getChat()->getId();
$messageId = $message->getMessageId();

static::request('Action/DeleteMessage', [ $messageId, $chatId ])->send();

if(is_string($witelId) && substr($witelId, 0, 1) == 'r') {

    $regionalId = substr($witelId, 1);
    return static::showTextPortPue($regionalId);

}

$witel = Witel::find($witelId);
return static::showTextPortPue($witel['regional_id'], $witelId);