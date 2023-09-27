<?php
namespace App\ApiRequest;

use App\Core\RestClient;
use App\Core\DB;

class NewosaseApi extends RestClient
{
    public function __construct()
    {
        $this->setBaseUrl('https://newosase.telkom.co.id/api/v1');
        $this->request['verify'] = false;
        $this->request['headers'] = [
            'Accept' => 'application/json'
        ];
    }

    public function setupAuth()
    {
        $auth = $this->getToken();
        if(!$auth) {
            $token = $this->generateToken();
            $auth = $this->createToken($token);
        }
        $this->request['headers']['token'] = $auth['generated_token'];
    }

    public function updateAuth()
    {
        $token = $this->generateToken();
        $auth = $this->getToken();
        $newAuth = $this->updateToken($token, $auth['id']);
        $this->request['headers']['token'] = $newAuth['generated_token'];
    }

    public function generateToken()
    {
        global $appConfig;
        $newosaseApi = new NewosaseApi();
        $newosaseApi->request['body'] = [
            'application' => $appConfig->newosase_auth->application,
            'token' => $appConfig->newosase_auth->token,
        ];

        $fetchResponse = $newosaseApi->sendRequest('POST', '/auth-service/apis/generate-jwt');
        $token = $fetchResponse && isset($fetchResponse->result->token) ? $fetchResponse->result->token : null;
        return $token;
    }

    public function getToken()
    {
        global $appConfig;
        $db = new DB();
        $tableName = $appConfig->newosase_auth->db_table;
        return $db->queryFirstRow("SELECT * FROM $tableName WHERE category=%s ORDER BY updated_at LIMIT 1", 'jwt_token');
    }

    public function createToken($token)
    {
        global $appConfig;
        $db = new DB();
        $tableName = $appConfig->newosase_auth->db_table;
        $currDateTime = date('Y-m-d H:i:s');
        $db->insert($tableName, [
            'category' => 'jwt_token',
            'generated_token' => $token,
            'created_at' => $currDateTime,
            'updated_at' => $currDateTime,
        ]);

        return $this->getToken();
    }

    public function updateToken($token, $id)
    {
        global $appConfig;
        $tableName = $appConfig->newosase_auth->db_table;
        $data = [
            'generated_token' => $token,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $db = new DB();
        $db->update($tableName, $data, "id=%i", $id);
        return $this->getToken();
    }
}