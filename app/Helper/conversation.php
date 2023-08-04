<?php

function getConversationState($conversation) {
    $notes = $conversation->notes;
    !is_array($notes) && $notes = [];
    return $notes['state'] ?? 0;
}