<?php

use App\Model\TelegramUser;
use App\Model\AlertUsers;
use App\Model\Regional;
use App\Model\Witel;

$telgUser = TelegramUser::findByRegistId($registId);
if(!$telgUser) {
    return static::sendEmptyResponse();
}

$request = static::request('Registration/TextApproved');
$request->params->chatId = $telgUser['chat_id'];
if($telgUser['message_thread_id']) {
    $request->params->messageThreadId = $telgUser['message_thread_id'];
}

$isPivotGroup = false;
if(!$telgUser['is_pic'] && $telgUser['type'] != 'private') {
    if($telgUser['level'] == 'witel') {
        $alertPivot = AlertUsers::findPivot($telgUser['level'], $telgUser['witel_id']);
        $witel = Witel::find($telgUser['witel_id']);
        $request->setPivotArea($witel['witel_name']);
    } elseif($telgUser['level'] == 'regional') {
        $alertPivot = AlertUsers::findPivot($telgUser['level'], $telgUser['regional_id']);
        $regional = Regional::find($telgUser['regional_id']);
        $request->setPivotArea($regional['name']);
    } elseif($telgUser['level'] == 'nasional') {
        $alertPivot = AlertUsers::findPivot($telgUser['level']);
        $request->setPivotArea('Lokasi Nasional');
    }
    if($alertPivot) {
        if($alertPivot['id'] != $telgUser['id']) {
            $pivotGroup = TelegramUser::find($alertPivot['id']);
            if($pivotGroup) {
                $request->setPivotGroup($pivotGroup['username']);
            }
        } else {
            $isPivotGroup = true;
        }
    }
}

if($telgUser['level'] == 'witel') {
    $witel = Witel::find($telgUser['witel_id']);
    $request->setPivotArea($witel['witel_name']);
} elseif($telgUser['level'] == 'regional') {
    $regional = Regional::find($telgUser['regional_id']);
    $request->setPivotArea($regional['name']);
} elseif($telgUser['level'] == 'nasional') {
    $request->setPivotArea('Lokasi Nasional');
}

$request->setUser($telgUser, $isPivotGroup);
return $request->send();