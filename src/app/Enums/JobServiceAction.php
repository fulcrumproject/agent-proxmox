<?php

namespace App\Enums;

enum JobServiceAction: string {
case ServiceCreate = 'ServiceCreate';
case ServiceDelete = 'ServiceDelete';
case ServiceUpdate = 'ServiceUpdate';
case ServiceStart = 'ServiceStart';
case ServiceStop = 'ServiceStop';
}
