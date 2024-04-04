<?php
namespace App\Libraries\TelegramText\TextFormatter\Traits;

trait Splittable
{
    public static $DEFAULT_MAX_CHARS = 4096;

    public static $SEARCH_ALL_ENTITIES = 1;
    public static $SEARCH_PAIRED_ENTITIES = 2;
    public static $SEARCH_CLOSED_ENTITIES = 3;
    public static $SEARCH_UNCLOSED_ENTITIES = 4;

    public function isLengthExceeded(int $maxChars = null): bool
    {
        if($maxChars === null) $maxChars = static::$DEFAULT_MAX_CHARS;
        $charsLength = 0;

        for($i=0; $i<$this->lineLength; $i++) {
            $charsLength += mb_strlen($this->lineContents[$i]);
            if($charsLength > $maxChars) {
                return true;
            }
        }

        return false;
    }

    public function findStrEntities(string $text, int $mode = null): array
    {
        if($mode === null) $mode = static::$SEARCH_ALL_ENTITIES;
        $entities = [];
        foreach($this->entities as $entityName => $entity) {

            $isMatch = false;
            if($mode === static::$SEARCH_ALL_ENTITIES) {
                $isMatch = true;
            } elseif(
                $mode === static::$SEARCH_PAIRED_ENTITIES
                || $mode === static::$SEARCH_CLOSED_ENTITIES
                || $mode === static::$SEARCH_UNCLOSED_ENTITIES
            ) {
                $isMatch = $entity['isPaired'];
            }

            $isMatch = $isMatch && preg_match_all($entity['regexp'], $text, $entityMatches, PREG_OFFSET_CAPTURE) ? true : false;
            $isEven = count($entityMatches[0]) % 2 === 0;
            $isOdd = !$isEven;
            if($mode === static::$SEARCH_CLOSED_ENTITIES) {
                $isMatch = $isEven;
            } elseif($mode === static::$SEARCH_UNCLOSED_ENTITIES) {
                $isMatch = $isOdd;
            }

            if($isMatch) {
                foreach($entityMatches[0] as $index => $matches) {
                    $entity['name'] = $entityName;
                    $entity['post'] = $matches[1];
                    $entity['match'] = $matches[0];

                    if($mode === static::$SEARCH_CLOSED_ENTITIES) {
                        if($isEven) array_push($entities, $entity);
                    } elseif($mode === static::$SEARCH_UNCLOSED_ENTITIES) {
                        if($isOdd && $index === count($entityMatches[0]) - 1) array_push($entities, $entity);
                    } else {
                        array_push($entities, $entity);
                    }
                }
            }

        }

        if(empty($entities)) {
            return $entities;
        }

        usort($entities, function($a, $b) {
            if($a['post'] > $b['post']) return 1;
            if($a['post'] < $b['post']) return -1;
            return 0;
        });
        return $entities;
    }

    public function chunkStrByEntities(string $text): array
    {
        $entities = $this->findStrEntities($text);
        if(empty($entities)) return [ $text ];

        $entityValues = [];
        $openEntityName = null;
        for($i=0; $i<count($entities); $i++) {
            if(!$entities[$i]['isPaired']) {
                array_push($entityValues, $entities[$i]['match']);
            } elseif(!$openEntityName) {
                if($i + 1 < count($entities)) {
                    $openEntityName = $entities[$i]['name'];
                } else {
                    array_push($entityValues, substr($text, $entities[$i]['post'], mb_strlen($text) - $entities[$i]['post']));
                }
            } else {
                $chunkIndex = $entities[$i - 1]['post'];
                if($entities[$i]['name'] == $openEntityName) {
                    $chunkLength = $entities[$i]['post'] + $entities[$i]['length'] - $chunkIndex;
                    array_push($entityValues, substr($text, $chunkIndex, $chunkLength));
                } else {
                    $chunkLength = $entities[$i]['post'] - 1 - $chunkIndex;
                    $openEntityMark = $this->entities[$openEntityName]['mark'];
                    array_push($entityValues, substr($text, $chunkIndex, $chunkLength) . $openEntityMark);
                }
                $openEntityName = null;
            }
        }

        $separators = array_map('preg_quote', array_unique($entityValues));
        $chunkingPattern = '#(' . implode('|', $separators) . ')#';
        $chunks = preg_split($chunkingPattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        return $chunks;
    }

    public function split(int $maxChars = null)
    {
        if($maxChars === null) $maxChars = static::$DEFAULT_MAX_CHARS;

        $text = $this->get();
        if(!$this->isLengthExceeded($maxChars)) {
            return [ $text ];
        }

        $chunks = $this->chunkStrByEntities($text);
        $results = [''];
        $index = 0;
        foreach($chunks as $chunk) {
            if(mb_strlen($results[$index]) + mb_strlen($chunk) > $maxChars) {
                array_push($results, '');
                $index++;
            }
            $results[$index] .= $chunk;
        }
        
        return $results;
    }
}