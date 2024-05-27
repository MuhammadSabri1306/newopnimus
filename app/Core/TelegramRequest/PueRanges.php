<?php
namespace App\Core\TelegramRequest;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
// use App\Core\TelegramText;
use App\Helper\NumberHelper;

trait PueRanges
{
    protected function getPueCategory(mixed $pueValue): string
    {
        if($pueValue < 1) return 'invalid';
        if($pueValue <= 1.6) return 'optimum';
        if($pueValue <= 2) return 'efficient';
        if($pueValue <= 3) return 'average';
        if($pueValue > 3) return 'in-efficient';
    }

    protected function getPueIconByCategory(string $categoryKey): string
    {
        $icons = [
            'invalid' => '❌',
            'optimum' => '✅',
            'efficient' => '✅',
            'average' => '⚠️',
            'in-efficient' => '❗️',
        ];
        return $icons[$categoryKey] ?? '';
    }
}