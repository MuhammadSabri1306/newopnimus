<?php

use App\Core\CallbackData;
use App\Model\Regional;
use App\Model\Witel;
use App\Model\RtuLocation;

if(!static::getUser()) {

    $request = static::request('Error/TextUserUnidentified');
    $request->setTarget( static::getRequestTarget() );
    return $request->send();

}

$messageText = trim( static::getMessage()->getText(true) );
if(!empty($messageText)) {
    $messageTextArr = explode(' ', $messageText);
    if(count($messageTextArr) > 0) $rtuSname = strtoupper($messageTextArr[0]);
    if(count($messageTextArr) > 1) $noPort = strtoupper($messageTextArr[1]);
}

if(isset($rtuSname, $noPort)) {
    if($noPort == 'ALL') {
        return static::showTextRtuPorts($rtuSname);
    }
    return static::showTextPort($rtuSname, [ 'port_no' => $noPort ]);
}

if(isset($rtuSname)) {
    return static::showSelectPort($rtuSname);
}

$fromId = static::getFrom()->getId();
$telgUser = static::getUser();

if($telgUser['level'] == 'nasional') {

    $request = static::request('Area/SelectRegional');
    $request->setTarget( static::getRequestTarget() );
    $request->setRegionals( Regional::getSnameOrdered() );
    $request->params->text = $request->getText()->newLine(2)
        ->addItalic('* Anda juga dapat memilih RTU dan Port dengan mengetikkan perintah /cekport [Kode RTU] [No. Port],')
        ->addItalic(' contoh: /cekport RTU00-D7-BAL A-12')
        ->get();

    $callbackData = new CallbackData('port.reg');
    $callbackData->limitAccess($fromId);
    $request->setInKeyboard(function($inKeyboardItem, $regional) use ($callbackData) {
        $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($regional['id']);
        return $inKeyboardItem;
    });

    return $request->send();

}

if($telgUser['level'] == 'regional') {

    $request = static::request('Area/SelectWitel');
    $request->setTarget( static::getRequestTarget() );
    $request->setWitels( Witel::getNameOrdered($telgUser['regional_id']) );
    $request->params->text = $request->getText()->newLine(2)
        ->addItalic('* Anda juga dapat memilih RTU dan Port dengan mengetikkan perintah /cekport [Kode RTU] [No. Port],')
        ->addItalic(' contoh: /cekport RTU00-D7-BAL A-12')
        ->get();

    $callbackData = new CallbackData('port.wit');
    $callbackData->limitAccess($fromId);
    $request->setInKeyboard(function($inKeyboardItem, $witel) use ($callbackData) {
        $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($witel['id']);
        return $inKeyboardItem;
    });

    return $request->send();

}

$request = static::request('Area/SelectLocation');
$request->setTarget( static::getRequestTarget() );
$request->setLocations( RtuLocation::getSnameOrderedByWitel($telgUser['witel_id']) );
$request->params->text = $request->getText()->newLine(2)
    ->addItalic('* Anda juga dapat memilih RTU dan Port dengan mengetikkan perintah /cekport [Kode RTU] [No. Port],')
    ->addItalic(' contoh: /cekport RTU00-D7-BAL A-12')
    ->get();

$callbackData = new CallbackData('port.loc');
$callbackData->limitAccess($fromId);
$request->setInKeyboard(function($inKeyboardItem, $loc) use ($callbackData) {
    $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($loc['id']);
    return $inKeyboardItem;
});

return $request->send();