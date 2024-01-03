<?php

use App\Model\TelegramUser;
use App\Model\AlertUsers;
use App\Model\Regional;
use App\Model\Witel;

$telegramUser = TelegramUser::findByRegistId($registId);
if(!$telegramUser) {
    return Request::emptyResponse();
}

$request = static::request('Registration/TextApproved');
$request->params->chatId = $telegramUser['chat_id'];

$isPivotGroup = false;
if(!$telegramUser['is_pic'] && $telegramUser['type'] != 'private') {
    if($telegramUser['level'] == 'witel') {
        $alertPivot = AlertUsers::findPivot($telegramUser['level'], $telegramUser['witel_id']);
        $witel = Witel::find($telegramUser['witel_id']);
        $request->setPivotArea($witel['witel_name']);
    } elseif($telegramUser['level'] == 'regional') {
        $alertPivot = AlertUsers::findPivot($telegramUser['level'], $telegramUser['regional_id']);
        $regional = Regional::find($telegramUser['regional_id']);
        $request->setPivotArea($regional['name']);
    } elseif($telegramUser['level'] == 'nasional') {
        $alertPivot = AlertUsers::findPivot($telegramUser['level']);
        $request->setPivotArea('Lokasi Nasional');
    }
    if($alertPivot) {
        if($alertPivot['telegram_user_id'] != $telegramUser['id']) {
            $pivotGroup = TelegramUser::find($alertPivot['id']);
            if($pivotGroup) {
                $request->setPivotGroup($pivotGroup['username']);
            }
        } else {
            $isPivotGroup = true;
        }
    }
}

if($telegramUser['level'] == 'witel') {
    $witel = Witel::find($telegramUser['witel_id']);
    $request->setPivotArea($witel['witel_name']);
} elseif($telegramUser['level'] == 'regional') {
    $regional = Regional::find($telegramUser['regional_id']);
    $request->setPivotArea($regional['name']);
} elseif($telegramUser['level'] == 'nasional') {
    $request->setPivotArea('Lokasi Nasional');
}

$request->setUser($telegramUser, $isPivotGroup);
return $request->send();