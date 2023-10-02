<?php
namespace App\ApiRequest;

use App\Core\RestClient;
use App\Core\DB;

class NewosaseApi extends RestClient
{
    private $useAuth = false;

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
        $this->useAuth = true;
        $auth = $this->getToken();
        if(!$auth) {
            $token = $this->generateToken();
            $auth = $this->createToken($token);
        }
        $this->request['headers']['token'] = $auth['generated_token'];
    }

    public function updateAuth()
    {
        $newGeneratedToken = $this->generateToken();
        $token = $this->getToken();
        $newToken = $this->updateToken($token['id'], $newGeneratedToken);
        $this->request['headers']['token'] = $newToken['generated_token'];
    }

    public function generateToken()
    {
        global $appConfig;
        $newosaseApi = new NewosaseApi();
        $newosaseApi->request['json'] = [
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

    public function updateToken($id, $token)
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

    public function sendRequest(string $httpMethod, string $pathUrl, $associative = null)
    {
        $data = parent::sendRequest($httpMethod, $pathUrl, $associative);
        if($this->useAuth && !$data) {
            $fetchErr = $this->getErrorMessages()->response;
            if(is_array($fetchErr)) {
                $isTokenExpired = isset($fetchErr['result']['isTokenExpired']) ? $fetchErr['result']['isTokenExpired'] : null;
                $message = isset($fetchErr['message']) ? $fetchErr['message'] : null;
            } else {
                $isTokenExpired = isset($fetchErr->result->isTokenExpired) ? $fetchErr->result->isTokenExpired : null;
                $message = isset($fetchErr->message) ? $fetchErr->message : null;
            }

            if($isTokenExpired === true || $message == 'Unauthorized') {
                $this->updateAuth();
                $data = parent::sendRequest($httpMethod, $pathUrl, $associative);
            }
        }

        return $data;
    }
}