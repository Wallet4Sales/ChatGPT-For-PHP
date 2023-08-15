<?php

require '../vendor/autoload.php';

use ChatGPTPHP\ChatGPT;

$chatGPT = new ChatGPT('OPEN_AI_KEY');
$chatGPT->addMessage('Eres un asistente virtual experto en php', 'system');

$answers = $chatGPT->ask('Dame 3 funciones utiles para arrays', null, true);// A Generator
foreach ($answers as $item) {
    echo $item['answer'];
}