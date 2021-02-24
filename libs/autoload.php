<?php
declare(strict_types=1);

include_once __DIR__ . '/traits.php';

foreach (glob(__DIR__ . '/*.php') as $filename) {
    if (basename($filename) != 'autoload.php') {
        include_once $filename;
    }
}