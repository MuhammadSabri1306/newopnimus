<?php
namespace App\Core\Model\Traits;

trait QueryPatternTraits
{
    protected static $activeQueryPattern = 'basic';

    protected static function getQueryPattern()
    {
        $patternKey = static::$activeQueryPattern;
        $patternNameArr = array_map(fn($section) => ucfirst($section), explode('_', $patternKey));
        $patternName = implode('', $patternNameArr);
        $patternCall = 'get' . $patternName . 'Pattern';
        $className = static::class;

        if(!method_exists($className, $patternCall)) {
            throw new \Exception("Getter of Query Pattern '$patternKey' not found in $className, getter method:$patternCall");
        }

        return $className::$patternCall();
    }

    public static function useBasicPattern()
    {
        static::$activeQueryPattern = 'basic';
    }
}