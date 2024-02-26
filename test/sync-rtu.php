<?php
error_reporting(E_ALL);

require __DIR__.'/../app/bootstrap.php';

use App\ApiRequest\NewosaseApiV2;
use App\Model\RtuList;

try {

    $newosaseApi = new NewosaseApiV2();
    $newosaseApi->setupAuth();
    $osaseData = $newosaseApi->sendRequest('GET', '/admin-service/locations');
    $locationList = $osaseData->get('result.locations');
    if(!$locationList) {
        dd('$osaseData->result->locations is empty', $locationList);
    }

    $rtuData = [];
    $invalidRtu = [];
    $currDate = date('Y-m-d H:i:s');
    foreach($locationList as $loc) {
        foreach($loc->rtus as $rtu) {

            $newRtu = [
                'uuid' => $rtu->id,
                'id_m' => $rtu->id_m_location,
                'name' => $rtu->name,
                'sname' => $rtu->sname,
                'location_id' => $loc->id,
                'datel_id' => $loc->id_datel,
                'witel_id' => $loc->id_witel,
                'regional_id' => $loc->id_regional,
                'timestamp' => $currDate
            ];

            if(!is_numeric($newRtu['location_id'])) {
                array_push($invalidRtu, $newRtu);
            } else {
                array_push($rtuData, $newRtu);
            }

        }
    }

    $rtuCount = count($rtuData);
    // if($rtuCount > 0) {
    //     RtuList::query(function($db, $table) use ($rtuData) {

    //         $db->query("TRUNCATE TABLE $table");
    //         $db->insert($table, $rtuData);

    //     });
    // }

    dd_json([
        'success' => true,
        'invalid_rtu' => $invalidRtu
    ]);

} catch(\Throwable $err) {
    debugError($err);
}