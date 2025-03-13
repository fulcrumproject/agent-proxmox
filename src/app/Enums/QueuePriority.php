<?php

namespace App\Enums;

enum QueuePriority: int {
case Low = 0;
case Medium = 5;
case High = 10;
case Critical = 100;
}
