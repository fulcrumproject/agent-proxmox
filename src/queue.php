<?php

require __DIR__ . '/vendor/autoload.php';

use App\Queue;

Queue::getInstance()->run();
