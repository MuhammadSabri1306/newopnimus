<?php

$textConfig = [
    'maxLength' => 4096
];

function splitMessage(string $messageText) {
    $resultTexts = [];
    global $textConfig;
    while(strlen($messageText) > 0) {

        $maxLength = min($textConfig['maxLength'], strlen($messageText));
        $partLength = $maxLength;
        preg_match('/```/', $messageText, $matchesCode, PREG_OFFSET_CAPTURE);
        
        if($matchesCode) {
            $matchesCode = array_filter($matchesCode, fn($mCode) => $mCode[1] <= $maxLength);
            
            if(count($matchesCode) % 2 === 0) {
                $partLength = ($matchesCode[count($matchesCode) - 2][1]) + 3;
            }
    
        }
    
        $partText = substr($messageText, 0, $partLength);
        array_push($resultTexts, $partText);
        $messageText = substr($messageText, $partLength, (strlen($messageText) - $partLength));
    
    }

    return $resultTexts;
}