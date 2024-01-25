<?php
namespace App\Core\TelegramRequest;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;

trait RtuFormat
{
    protected function isRtuStatusDown($rtuStatus)
    {
        $rtuStatusKey = strtolower($rtuStatus);
        if($rtuStatusKey == 'normal') return false;
        if($rtuStatusKey == 'alert') return false;
        return true;
    }

    protected function getRtuStatusIcon($rtuStatus)
    {
        $rtuStatusKey = strtolower($rtuStatus);
        if($rtuStatusKey == 'normal') return '✅';
        if($rtuStatusKey == 'alert') return '⚠️';
        if( $this->isRtuStatusDown($rtuStatus) ) return '❌';
        return '';
    }
}