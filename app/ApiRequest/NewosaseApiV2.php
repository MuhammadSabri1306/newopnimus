<?php
namespace App\ApiRequest;

use App\Libraries\HttpClient\RestClient;
use App\Libraries\HttpClient\Exceptions\ClientException;
use App\Libraries\HttpClient\Exceptions\RequestException;
use App\Libraries\HttpClient\ResponseDataValidation;
use App\Config\AppConfig;

class NewosaseApiV2 extends RestClient implements ResponseDataValidation
{
    private $useAuth = false;
    private $db;
    private $table;

    public function __construct()
    {
        parent::__construct();
        $this->setBaseUrl('https://newosase.telkom.co.id/api/v1');
        $this->request['verify'] = false;
        $this->request['headers'] = [
            'Accept' => 'application/json'
        ];

        $dbConfig = AppConfig::$DATABASE->default;
        $this->db = new \MeekroDB($dbConfig->host, $dbConfig->username, $dbConfig->password, $dbConfig->name);
        $this->table = AppConfig::$OSASEAPI_DBTABLE;
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
        $newosaseApi = new NewosaseApiV2();
        $newosaseApi->request['json'] = [
            'application' => \App\Config\AppConfig::$OSASEAPI_APP_ID,
            'token' => \App\Config\AppConfig::$OSASEAPI_TOKEN,
        ];

        $osaseData = $newosaseApi->sendRequest('POST', '/auth-service/apis/generate-jwt');
        $token = $osaseData->get('result.token');
        return $token;
    }

    public function getToken()
    {
        return $this->db->queryFirstRow("SELECT * FROM $this->table WHERE category=%s ORDER BY updated_at LIMIT 1", 'jwt_token');
    }

    public function createToken($token)
    {
        $currDateTime = date('Y-m-d H:i:s');
        $this->db->insert($this->table, [
            'category' => 'jwt_token',
            'generated_token' => $token,
            'created_at' => $currDateTime,
            'updated_at' => $currDateTime,
        ]);

        return $this->getToken();
    }

    public function updateToken($id, $token)
    {
        $data = [
            'generated_token' => $token,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $this->db->update($this->table, $data, "id=%i", $id);
        return $this->getToken();
    }

    public function sendRequest(string $httpMethod, string $pathUrl, $associative = null)
    {
        $data = parent::sendRequest($httpMethod, $pathUrl, $associative);
        if($this->useAuth) {

            $isTokenExpired = $data->get('result.isTokenExpired') ? true : false;
            $message = $data->get('message');
            
            if($isTokenExpired && $message == 'Unauthorized') {
                $this->updateAuth();
                $data = parent::sendRequest($httpMethod, $pathUrl, $associative);
            }

        }
        return $data;
    }

    public function getBuiltUrl()
    {
        $url = $this->getBaseUrl();
        if(!isset($this->request['query']) || empty($this->request['query'])) {
            return $url;
        }

        $urlParams = urldecode(http_build_query($data, '', '&'));
        return "$url?$urlParams";
    }
}