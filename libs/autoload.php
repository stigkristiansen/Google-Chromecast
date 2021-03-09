<?php
declare(strict_types=1);

include_once __DIR__ . '/traits.php';

function CustomAutoload($ClassName) {
    //IPS_LogMessage('Autoload', 'Class: '.$ClassName);
    include(__DIR__ . "/" . $ClassName . ".php");
}

spl_autoload_register("CustomAutoload");

foreach (glob(__DIR__ . '/*.php') as $filename) {
    if (basename($filename) != 'autoload.php') {
        include_once $filename;
    }
}

