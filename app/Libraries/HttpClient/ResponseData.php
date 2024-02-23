<?php
namespace App\Libraries\HttpClient;

use App\Libraries\HttpClient\ResponseDataValidation;
use App\Libraries\HttpClient\Exceptions\DataNotFoundException;

class ResponseData implements ResponseDataValidation
{
    protected $data;

    public function __construct($data = null)
    {
        $this->set($data);
    }

    public function set($data)
    {
        $this->data = $data;
    }

    public function get(string $keyNode = null, $defaultValue = null)
    {
        if(!$keyNode) {
            return $this->data;
        }

        if(!is_object($this->data) && !is_array($this->data)) {
            return null;
        }

        $keys = explode('.', $keyNode);
        $data = $this->data;
        for($i=0; $i<count($keys); $i++) {

            $key = $keys[$i];
            if(is_array($data) && isset($data[$key])) {
                $data = $data[$key];
            } elseif(is_object($data) && isset($data->$key)) {
                $data = $data->$key;
            } else {
                $data = $defaultValue;
                $i = count($keys);
            }

        }
        return $data;
    }

    public function find(string $keyNode = null, int $searchPattern = 1)
    {
        $data = $this->get($keyNode);

        if($searchPattern === self::EXPECT_NOT_EMPTY) {
            $emptyValues = [ null, [], '' ];
            foreach($emptyValues as $item) {
                if($data === $item) {
                    throw new DataNotFoundException('searched response data not found');
                }
            }
        }

        if($searchPattern === self::EXPECT_NOT_NULL) {
            if($data === null) {
                throw new DataNotFoundException('searched response data is null');
            }
        }

        if($searchPattern === self::EXPECT_ARRAY || $searchPattern === self::EXPECT_ARRAY_NOT_EMPTY) {
            if(!is_array($data)) {
                throw new DataNotFoundException('searched response data expected as array');
            }
            if($searchPattern === self::EXPECT_ARRAY_NOT_EMPTY && count($data) < 1) {
                throw new DataNotFoundException('searched response data is an empty array');
            }
        }

        if($searchPattern === self::EXPECT_STRING_NOT_EMPTY) {
            if(!is_string($data) || strlen($data) < 1) {
                throw new DataNotFoundException('searched response data is an empty string');
            }
        }

        if($searchPattern === self::EXPECT_BOOLEAN) {
            if(!is_bool($data) || ( $data !== '0' && $data !== '1' && $data !== 'false' && $data !== 'true' )) {
                throw new DataNotFoundException('searched response data expected as boolean');
            }
        }

        return $data;
    }
}