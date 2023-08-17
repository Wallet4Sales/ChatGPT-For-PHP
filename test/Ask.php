<?php

require '../vendor/autoload.php';

use ChatGPTPHP\ChatGPT;

$chatGPT = new ChatGPT('OPEN_AI_KEY');
$chatGPT->addMessage('You are a virtual assistant expert in PHP', 'system');

$answers = $chatGPT->ask('Give me 3 useful functions for arrays', null, true);// A Generator
foreach ($answers as $item) {
    echo $item['answer'];
}