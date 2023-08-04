<?php
namespace App\Core;

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
    
    public function __construct($name, $userId, $chatId)
    {
        $this->name = $name;
        $this->userId = $userId;
        $this->chatId = $chatId;

        $this->db = new DB();
        $conversation = $this->db
            ->queryFirstRow("SELECT * FROM $this->tableName WHERE status='active' AND name=%s_name AND user_id=%i_userid AND chat_id=%i_chatid", [
                'name' => $this->name,
                'userid' => $this->userId,
                'chatid' => $this->chatId,
            ]
        );

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
        // dd($this->state);
    }

    public function getStateArray()
    {
        return $this->state;
    }

    public static function getOrCreate($name, $userId, $chatId)
    {
        $conversation = new Conversation($name, $userId, $chatId);
        if(!$conversation->isExists()) {
            $conversation->create();
        }
        return $conversation;
    }
}