<?php
namespace MuhammadSabri1306\PhpChartSvg\Entities;

class Style
{
    protected $styles = [];

    public function getAll()
    {
        return $this->styles;
    }

    public function getName()
    {
        return static::class;
    }

    public static function checkRequiredStyles(string $styleName, array $styles, array $keys): bool
    {
        foreach($keys as $key) {
            if(!array_key_exists($key, $styles)) {
                throw new \Exception("$styleName should have default value for '$key'");
                return false;
            }
        }
        return true;
    }
}