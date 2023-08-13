<?php

function findArrayIndex(array $data, callable $checker) {
    for($i=0; $i<count($data); $i++) {
        if($checker($data[$i]) === true) {
            $index = $i;
            $i = count($data);
            return $index;
        }
    }
    return -1;
}

function groupPortData(array $ports, array $groupKeys = ['regional', 'witel', 'datel', 'location', 'rtu']) {
    $result = [];
    $levels = [
        [ 'key' => 'regional', 'data_key' => [ 'regional_id', 'regional_name', 'regional_code' ] ],
        [ 'key' => 'witel', 'data_key' => [ 'witel_id', 'witel_name', 'witel_code' ] ],
        [ 'key' => 'datel', 'data_key' => [ 'datel_id', 'datel_name' ] ],
        [ 'key' => 'location', 'data_key' => [ 'location_id', 'location_name', 'location_code' ] ],
        [ 'key' => 'rtu', 'data_key' => [ 'rtu_id', 'rtu_name', 'rtu_code' ] ],
        [ 'key' => 'port', 'data_key' => '*' ],
    ];

    foreach ($ports as $port) {
        $currentGroup = &$result;

        foreach ($groupKeys as $key) {
            $groupValue = $port[$key . '_id'];
            $groupIndex = findArrayIndex($currentGroup, fn($groupItem) => $groupItem[$key . '_id'] == $groupValue);

            $nextGroupIndex = findArrayIndex($groupKeys, fn($groupKeysItem) => $groupKeysItem == $key) + 1;
            if(isset($groupKeys[$nextGroupIndex])) {
                $newGroupKey = $groupKeys[$nextGroupIndex] . 's';
            } else {
                $newGroupKey = 'ports';
            }

            if ($groupIndex < 0) {
                // Simpan pengecekan grup disini untuk struktur tiap grup
                $currentLevelIndex = findArrayIndex($levels, fn($level) => $level['key'] == $key);
                $newGroup = [];
                if($currentLevelIndex == 0) {
                    foreach($levels[$currentLevelIndex]['data_key'] as $dataKey) {
                        $newGroup[$dataKey] = $port[$dataKey];
                    }
                } elseif($currentLevelIndex > 0) {
                    foreach($levels[$currentLevelIndex]['data_key'] as $dataKey) {
                        $newGroup[$dataKey] = $port[$dataKey];
                    }
                    $higherLevelKey = $levels[$currentLevelIndex - 1]['key'];
                    $higherLevelDataKey = $levels[$currentLevelIndex - 1]['data_key'];
                    $newGroup[$higherLevelKey] = [];
                    foreach($higherLevelDataKey as $dataKey) {
                        $newGroup[$higherLevelKey][$dataKey] = $port[$dataKey];
                    }
                }
                
                if (!isset($newGroup[$newGroupKey])) {
                    $newGroup[$newGroupKey] = [];
                }

                array_push($currentGroup, $newGroup);
                $groupIndex = count($currentGroup) - 1;
            }

            $currentGroup = &$currentGroup[$groupIndex][$newGroupKey];
        }

        array_push($currentGroup, $port);
    }

    return $result;
}