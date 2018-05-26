<?php

use Infinity\EventProcessor;

include __DIR__ . "/vendor/autoload.php";

$eventProcessor = new EventProcessor("uploaded", "processed");
$eventProcessor->run();