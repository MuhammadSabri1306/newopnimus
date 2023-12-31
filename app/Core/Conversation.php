<?php
namespace App\Core;

use App\Config\AppConfig;

class Conversation
{
    private $db;
    private $tableName = 'conversation';

    private $name;
    private $userId;
    private $chatId;
    
    private $id;
    private $step;
    private $state = [];
    private $status;

    private $exists = false;
    
    public function __construct($name, $userId, $chatId, array $config = [])
    {
        $this->name = $name;
        $this->userId = $userId;
        $this->chatId = $chatId;

        $dbConfig = AppConfig::$DATABASE->default;
        $this->db = new \MeekroDB($dbConfig->host, $dbConfig->username, $dbConfig->password, $dbConfig->name);

        $defaultCall = function($db, $params) {
            return $db->queryFirstRow(
                "SELECT * FROM $this->tableName WHERE status='active' AND name=%s_name AND chat_id=%i_chatid",
                [ 'name' => $params['name'], 'chatid' => $params['chatId'] ]
            );
        };

        $callParams = [ 'name' => $this->name, 'userId' => $this->userId, 'chatId' => $this->chatId ];
        $conversation = isset($config['call']) ? $config['call']($this->db, $callParams)
            : $defaultCall($this->db, $callParams);

        if($conversation) {
            $this->id = (int) $conversation['id'];
            $this->step = (int) $conversation['step'];
            $this->status = $conversation['status'];
            $this->state = json_decode($conversation['state'], true);
            $this->exists = true;
        }
    }

    public function toJson()
    {
        $data = [
            'tableName' => $this->tableName,
            'name' => $this->name,
            'userId' => $this->userId,
            'chatId' => $this->chatId,
            'id' => $this->id,
            'step' => $this->step,
            'state' => $this->state,
            'status' => $this->status,
            'exists' => $this->exists
        ];
        return json_encode($data);
    }

    public function isExists()
    {
        return $this->exists;
    }

    public function setStep($step)
    {
        $this->step = $step;
    }

    public function getStep()
    {
        return $this->step;
    }

    public function nextStep()
    {
        if($this->isExists()) $this->step++;
    }

    public function setUserId($userId)
    {
        $conversation = $this->db->update($this->tableName, [ 'user_id' => $userId ], "id=%i", $this->id);
        $this->userId = $userId;
    }

    public function getId()
    {
        return $this->id;
    }

    public function create()
    {
        $currDatetime = date('Y-m-d H:i:s');
        $conversation = $this->db->insert($this->tableName, [
            'user_id' => $this->userId,
            'chat_id' => $this->chatId,
            'name' => $this->name,
            'status' => 'active',
            'state' => '{}',
            'created_at' => $currDatetime,
            'updated_at' => $currDatetime
        ]);

        $this->id = $this->db->insertId();
        $this->step = 0;
        $this->status = 'active';
        $this->exists = true;
    }

    public function commit()
    {
        if(!$this->isExists()) {
            return null;
        }

        $this->db->update($this->tableName, [
            'step' => $this->step,
            'state' => json_encode($this->state),
            'updated_at' => date('Y-m-d H:i:s')
        ], "id=%i", $this->id);
    }

    public function cancel()
    {
        if(!$this->isExists()) {
            return null;
        }

        $this->db->update($this->tableName, [
            'step' => $this->step,
            'state' => json_encode($this->state),
            'status' => 'cancel',
            'updated_at' => date('Y-m-d H:i:s')
        ], "id=%i", $this->id);
    }

    public function done()
    {
        if(!$this->isExists()) {
            return null;
        }

        $this->db->update($this->tableName, [
            'step' => $this->step,
            'state' => json_encode($this->state),
            'status' => 'done',
            'updated_at' => date('Y-m-d H:i:s')
        ], "id=%i", $this->id);
    }

    public function __get($key)
    {
        if(array_key_exists($key, $this->state)) {
            return $this->state[$key];
        }
        return null;
    }

    public function __set($key, $value)
    {
        $this->state[$key] = $value;
    }

    public function arrayPush($key, $value)
    {
        array_push($this->state[$key], $value);
    }

    public function getStateArray()
    {
        return $this->state;
    }

    public static function getOrCreate($name, $userId, $chatId, array $config = [])
    {
        $conversation = new Conversation($name, $userId, $chatId, $config);
        if(!$conversation->isExists()) {
            $conversation->create();
        }
        return $conversation;
    }
}