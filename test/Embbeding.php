<?php

require '../vendor/autoload.php';

use ChatGPTPHP\ChatGPT;

$chatGPT = new ChatGPT('OPEN_AI_KEY');
$embbeding = $chatGPT->createEmbbeding("This is a example for embbeding");
print_r($embbeding);