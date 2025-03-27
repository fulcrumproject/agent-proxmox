<?php

namespace App\Enums;

enum JobServiceAction: string {
case ServiceCreate = 'ServiceCreate';
case ServiceDelete = 'ServiceDelete';
case ServiceUpdate = 'ServiceColdUpdate';
case ServiceStart = 'ServiceStart';
case ServiceStop = 'ServiceStop';
}
