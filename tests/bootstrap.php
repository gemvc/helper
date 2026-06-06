<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

error_reporting(E_ALL);
ini_set('display_errors', '1');

if (!isset($_ENV['APP_ENV'])) {
    $_ENV['APP_ENV'] = 'testing';
}

if (!isset($_ENV['TOKEN_SECRET'])) {
    $_ENV['TOKEN_SECRET'] = 'test-secret-key-for-testing-only';
}
