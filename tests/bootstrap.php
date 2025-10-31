<?php

if (!file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
    exit('You need to set up the project dependencies using Composer:' . PHP_EOL .
        'composer install' . PHP_EOL);
}

require_once dirname(__DIR__) . '/vendor/autoload.php';
