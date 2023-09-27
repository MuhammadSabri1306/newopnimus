<?php

function encodeCallbackData(
    string $callbackKey,
    $optionTitle,
    $optionValue,
    bool $compress = false
) {
    if(!$optionTitle) {
        return "$callbackKey.[v=$optionValue]";
    }
    return "$callbackKey.[t=$optionTitle]&[v=$optionValue]";
}

function decodeCallbackData($callbackData) {
    $pattern = '/^([\w.]+)\.\[t=([^\]]*)\]&\[v=([^\]]*)\]$/';
    if(preg_match($pattern, $callbackData, $matches)) {

        $callbackKey = $matches[1];
        $optionTitle = $matches[2];
        $optionValue = $matches[3];

        return [
            'callbackKey' => $callbackKey,
            'optionTitle' => $optionTitle,
            'optionValue' => $optionValue,
        ];

    }

    $pattern = '/^([\w.]+)\.\[v=([^\]]*)\]$/';
    if(preg_match($pattern, $callbackData, $matches)) {

        $callbackKey = $matches[1];
        $optionValue = $matches[2];

        return [
            'callbackKey' => $callbackKey,
            'optionTitle' => '',
            'optionValue' => $optionValue,
        ];

    }
    
    return null;
}