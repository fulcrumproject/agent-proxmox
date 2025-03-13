<?php

use App\Database\Migrator;

require __DIR__ . '/vendor/autoload.php';

$options = getopt( '', ['rollback', 'refresh'] );

$migrator = new Migrator();

if ( isset( $options['rollback'] ) ) {
    $migrator->rollback();
} elseif ( isset( $options['refresh'] ) ) {
    $migrator->refresh();
} else {
    $migrator->migrate();
}
