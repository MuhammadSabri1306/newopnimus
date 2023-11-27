<?php
error_reporting(E_ALL);
require __DIR__.'/../app/bootstrap.php';

use MeekroDB;

class Database extends MeekroDB
{
    public function __construct()
    {
        $host = '10.60.165.99';
        $user = 'gepee';
        $password = 'Juw00ss!!';
        $dbName = 'amcdb';
        parent::__construct($host, $user, $password, $dbName);
    }
}

$db = new Database();
$workList = [
/* ---------------------------------------------------------------------------------------------------------------- */
    /* SHOW ALL TABLES */
    // [
    //     'query' => 'SHOW TABLES',
    //     'callback' => function($db, $query) {
    //         return $db->query($query);
    //     }
    // ],
/* ---------------------------------------------------------------------------------------------------------------- */
    /* SHOW ALL TABLES collumns */
    // [
    //     'query' => "SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE table_schema='amcdb' AND table_name='t_aset_gedung_lahan'",
    //     'callback' => function($db, $query) {
    //         $cols = $db->query($query);
    //         return array_column($cols, 'COLUMN_NAME');
    //     }
    // ],
    // [
    //     'query' => "SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE table_schema='amcdb' AND table_name='t_pln_indoor'",
    //     'callback' => function($db, $query) {
    //         $cols = $db->query($query);
    //         return array_column($cols, 'COLUMN_NAME');
    //     }
    // ],
    // [
    //     'query' => "SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE table_schema='amcdb' AND table_name='t_pln_rekap'",
    //     'callback' => function($db, $query) {
    //         $cols = $db->query($query);
    //         return array_column($cols, 'COLUMN_NAME');
    //     }
    // ],
    // [
    //     'query' => "SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE table_schema='amcdb' AND table_name='t_pln_transaksi'",
    //     'callback' => function($db, $query) {
    //         $cols = $db->query($query);
    //         return array_column($cols, 'COLUMN_NAME');
    //     }
    // ],
    // [
    //     'query' => "SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE table_schema='amcdb' AND table_name='xt_gepee'",
    //     'callback' => function($db, $query) {
    //         $cols = $db->query($query);
    //         return array_column($cols, 'COLUMN_NAME');
    //     }
    // ],
/* ---------------------------------------------------------------------------------------------------------------- */
    /* SHOW DATA */
    // [
    //     'query' => 'SELECT * FROM t_pln_transaksi WHERE tahun=2023',
    //     'callback' => function($db, $query) {
    //         return $db->query($query);
    //     }
    // ],
    // [
    //     'query' => 'SELECT * FROM t_aset_gedung_lahan',
    //     'callback' => function($db, $query) {
    //         return $db->query($query);
    //     }
    // ],
    // [
    //     'query' => 'SELECT * FROM t_pln_indoor',
    //     'callback' => function($db, $query) {
    //         return $db->query($query);
    //     }
    // ],
    [
        'query' => 'SELECT * FROM t_pln_indoor GROUP BY id_pelanggan',
        'callback' => function($db, $query) {
            return $db->query($query);
        }
    ],
    // [
    //     'query' => 'SELECT DISTINCT kode_regional, kode_witel, witel FROM t_pln_indoor',
    //     'callback' => function($db, $query) {
    //         return $db->query($query);
    //     }
    // ],
];

/* ======================================================================================================= */



$data = [];
foreach($workList as $workItem) {
    
    $result = $workItem['callback']($db, $workItem['query']);

    array_push($data, [
        'query' => $workItem['query'],
        'result_count' => count($result),
        'result' => $result
    ]);
}

dd_json($data);
// dd($data);