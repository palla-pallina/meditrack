<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Kreait\Firebase\Factory;

$factory = (new Factory)
    ->withServiceAccount(__DIR__.'/serviceAccount.json')
    ->withDatabaseUri('https://meditrack-59cca-default-rtdb.asia-southeast1.firebasedatabase.app/');

$auth = $factory->createAuth();
$database = $factory->createDatabase();