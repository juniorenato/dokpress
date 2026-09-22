<?php

/**
 * Loads Composer and the project environment file.
 *
 * The entry point (`console` or `app/wp.php`) must define `WD_BASE_PATH`
 * before including this file. A missing `.env` is ignored so the process
 * can rely on variables already present in the environment.
 */

use Symfony\Component\Dotenv\Dotenv;

require_once WD_BASE_PATH .'/vendor/autoload.php';

$dotenv = new Dotenv();
$envPath = WD_BASE_PATH .'/.env';

if(file_exists($envPath)) $dotenv->load($envPath);
