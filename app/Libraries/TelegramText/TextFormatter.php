<?php
namespace App\Libraries\TelegramText;

use App\Libraries\TelegramText\TextFormatter\Traits\Splittable;

class TextFormatter
{
    use Splittable;

    protected $lineContents = [];
    protected $lineIndex = -1;
    protected $lineLength = 0;
    protected $entities = [];

    public function __construct(string $text = '', bool $escapeText = false)
    {
        $this->registerEntity('new_line', [
            'mark' => PHP_EOL,
            'regexp' => '/\n/',
            'isPaired' => false,
            'isCallable' => false,
        ]);

        if($escapeText) $this->toEscapedText($text);
        array_push($this->lineContents, $text);
        $this->lineIndex++;
        $this->lineLength++;
    }

    protected function registerEntity(string $name, array $config)
    {
        $markValue = [];
        if(!isset($config['mark'])) $config['mark'] = '';
        if(!isset($config['isEscapable'])) $config['isEscapable'] = false;
        if(!isset($config['isCallable'])) $config['isCallable'] = false;

        if(!is_string($config['mark'])) throw new \Exception('config mark should be string');
        if(!isset($config['regexp']) || !is_string($config['regexp'])) throw new \Exception('config regexp (string) is required');
        if(!isset($config['isPaired']) || !is_bool($config['isPaired'])) throw new \Exception('config isPaired (bool) is required');
        if(!is_bool($config['isEscapable'])) throw new \Exception('config isEscapable should be bool');
        if(!is_bool($config['isCallable'])) throw new \Exception('config isCallable should be bool');

        $this->entities[$name] = [
            'mark' => $config['mark'],
            'length' => mb_strlen($config['mark']),
            'regexp' => $config['regexp'],
            'isPaired' => $config['isPaired'],
            'isEscapable' => $config['isEscapable'],
            'isCallable' => $config['isCallable']
        ];
    }

    public function toEscapedText(string $text): string
    {
        foreach($this->entities as $entity) {
            if($entity['isEscapable']) {
                $text = str_replace($entity['mark'], '\\' . $entity['mark'], $text);
            }
        }
        return $text;
    }

    public function addText(string $text = '', bool $escapeText = true)
    {
        if($escapeText) $this->toEscapedText($text);
        $this->lineContents[$this->lineIndex] .= $text;
        return $this;
    }

    public function get(): string
    {
        return implode($this->entities['new_line']['mark'], $this->lineContents);
    }

    public function newLine(int $line = 1)
    {
        for($i=0; $i<$line; $i++) {
            array_push($this->lineContents, '');
            $this->lineIndex++;
            $this->lineLength++;
        }
        return $this;
    }

    public function getLineLength(int $lineIndex = null): int
    {
        if(is_null($lineIndex)) $lineIndex = $this->lineIndex;
        $lineContent = $this->lineContents[$lineIndex];
        return mb_strlen($lineContent);
    }

    public function __call(string $key, array $args)
    {
        foreach($this->entities as $entityName => $entity) {

            if(!$entity['isCallable']) continue;
            $methodName = implode('', array_map(fn($piece) => ucfirst($piece), explode('_', $entityName)));
            
            if($entity['isPaired'] && ($key == 'start' . $methodName || $key == 'end' . $methodName)) {
                $this->addText($entity['mark'], false);
                return $this;
            }

            if($key == 'add' . $methodName) {
                $this->addText($entity['mark'], false)
                    ->addText($args[0], $args[1] ?? true)
                    ->addText($entity['mark'], false);
                return $this;
            }

        }
        throw new \BadMethodCallException("method '$key' not exists");
    }

    public static function __callStatic(string $key, array $args)
    {
        foreach($this->entities as $entityName => $entity) {

            if(!$entity['isCallable']) continue;
            $methodName = implode('', array_map(fn($piece) => ucfirst($piece), explode('_', $entityName)));
            if($key == 'create' . $methodName) {
                $this->addText($entity['mark'], false)
                    ->addText($args[0], $args[1] ?? true)
                    ->addText($entity['mark'], false);
                return $this;
            }

        }
        throw new \BadMethodCallException("static method '$key' not exists");
    }
}