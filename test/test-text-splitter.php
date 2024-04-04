<?php
require __DIR__.'/../app/bootstrap.php';

use App\Libraries\TelegramText\MarkdownText;
use App\Controller\BotController;
use App\Model\Witel;
use App\Model\Datel;
use App\Model\TelegramUser;
use App\Model\TelegramPersonalUser;
use App\Model\PicLocation;
use App\Model\RtuLocation;

try {

    \App\Config\AppConfig::$MODE = 'development';
    
    $witelId = 131;

    $witel = Witel::find($witelId);
    $pics = RtuLocation::query(function($db, $table) use ($witelId) {
        $tableTelgUser = TelegramUser::$table;
        $tableTelgPersUser = TelegramPersonalUser::$table;
        $tablePic = PicLocation::$table;
        $tableDatel = Datel::$table;
        $query = 'SELECT user.user_id, user.username, user.first_name, user.last_name, pers.nama AS full_name, '.
            " loc.id AS location_id, loc.location_name, loc.location_sname FROM $table AS loc".
            " JOIN $tableDatel AS datel ON datel.id=loc.datel_id LEFT JOIN $tablePic AS pic ON pic.location_id=loc.id".
            " LEFT JOIN $tableTelgUser AS user ON user.id=pic.user_id LEFT JOIN $tableTelgPersUser AS pers".
            ' ON pers.user_id=user.id WHERE datel.witel_id=%i ORDER BY loc.location_sname, user.regist_id';
        return $db->query($query, $witelId) ?? [];
    });

    $request = BotController::request('Pic/TextListInWitel');
    $request->setWitel($witel);
    $request->setPics($pics);
    dd(
        $request->getText()->split()
    );

} catch(\Throwable $err) {
    debugError($err);
}