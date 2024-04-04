<?php
namespace App\Libraries\TelegramText;

use App\Libraries\TelegramText\TextFormatter;

class MarkdownText extends TextFormatter
{
    public function __construct(string $text = '', bool $escapeText = false)
    {
        $this->registerEntity('code', [
            'mark' => '```',
            'regexp' => '/\`{3}/',
            'isPaired' => true,
            'isEscapable' => true,
            'isCallable' => true
        ]);

        $this->registerEntity('inline_code', [
            'mark' => '`',
            'regexp' => '/(?<!\`)\`(?!\`)/',
            'isPaired' => true,
            'isEscapable' => true,
            'isCallable' => true
        ]);

        $this->registerEntity('bold', [
            'mark' => '*',
            'regexp' => '/\*/',
            'isPaired' => true,
            'isEscapable' => true,
            'isCallable' => true
        ]);

        $this->registerEntity('italic', [
            'mark' => '_',
            'regexp' => '/\_/',
            'isPaired' => true,
            'isEscapable' => true,
            'isCallable' => true
        ]);

        $this->registerEntity('url', [
            'regexp' => '/\[[^\]]+\]\([^)]+\)/',
            'isPaired' => false,
            'isEscapable' => true,
            'isCallable' => false
        ]);

        parent::__construct($text, $escapeText);
    }

    public static function create(string $text = '', bool $escapeText = false)
    {
        return new MarkdownText($text, $escapeText);
    }

    public function addUrl(string $url, string $text, bool $escapeText = false)
    {
        if(!$escapeText) {
            return $this->addText("[$text]($url)");
        }
        $text = $this->toEscapedText($text);
        return $this->addText("[$text]($url)");
    }

    public static function createUrl(string $url, string $text, bool $escapeText = false): string
    {
        return MarkdownText::create()->addUrl($url, $text, $escapeText)->get();
    }

    public function addMention(string $userId, string $text, bool $escapeText = false)
    {
        return $this->addUrl("tg://user?id=$userId", $text, $escapeText);
    }

    public static function createMention(string $userId, string $text, bool $escapeText = false): string
    {
        return MarkdownText::create()->addMention($userId, $text, $escapeText)->get();
    }
}