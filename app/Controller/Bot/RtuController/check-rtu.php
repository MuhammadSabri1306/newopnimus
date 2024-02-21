<?php

use App\Core\CallbackData;
use App\Model\Regional;
use App\Model\Witel;
use App\Model\RtuLocation;

$message = static::getMessage();
$fromId = static::getFrom()->getId();
$messageText = trim($message->getText(true));

$telgUser = static::getUser();
if(!$telgUser) {
    $request = static::request('Error/TextUserUnidentified');
    $request->setTarget( static::getRequestTarget() );
    return $request->send();
}

$messageTextArr = explode(' ', $messageText);
if(!empty($messageTextArr[0])) {
    $rtuSname = strtoupper($messageTextArr[0]);
}

if(isset($rtuSname)) {

    return static::showRtuDetail([ 'sname' => $rtuSname ]);

}

if($telgUser['level'] == 'nasional') {

    $request = static::request('Area/SelectRegional');
    $request->setTarget( static::getRequestTarget() );
    $request->setRegionals( Regional::getSnameOrdered() );

    $request->params->text = $request->getText()->newLine()
        ->addItalic('* Anda juga dapat memilih RTU dengan mengetikkan perintah')
        ->addItalic(' /cekrtu [Kode RTU], e.g: /cekrtu RTU00-D7-BAL')
        ->get();

    $callbackData = new CallbackData('rtu.cekreg');
    $callbackData->limitAccess($fromId);
    $request->setInKeyboard(function($inKeyboardItem, $witel) use ($callbackData) {
        $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($witel['id']);
        return $inKeyboardItem;
    });

    return $request->send();
    
}

if($telgUser['level'] == 'regional') {

    $request = static::request('Area/SelectWitel');
    $request->setTarget( static::getRequestTarget() );
    $request->setWitels( Witel::getNameOrdered($telgUser['regional_id']) );

    $request->params->text = $request->getText()->newLine()
        ->addItalic('* Anda juga dapat memilih RTU dengan mengetikkan perintah')
        ->addItalic(' /cekrtu [Kode RTU], e.g: /cekrtu RTU00-D7-BAL')
        ->get();

    $callbackData = new CallbackData('rtu.cekwit');
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

$request->params->text = $request->getText()->newLine()
        ->addItalic('* Anda juga dapat memilih RTU dengan mengetikkan perintah')
        ->addItalic(' /cekrtu [Kode RTU], e.g: /cekrtu RTU00-D7-BAL')
        ->get();

$callbackData = new CallbackData('rtu.cekloc');
$callbackData->limitAccess($fromId);
$request->setInKeyboard(function($inKeyboardItem, $loc) use ($callbackData) {
    $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($loc['id']);
    return $inKeyboardItem;
});

return $request->send();