<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = new \Keperis\App();

function app($app = null)
{
    static $singleton;
    if (!$singleton) {
        $singleton = $app;
    }
    return $singleton;
}

app($app);