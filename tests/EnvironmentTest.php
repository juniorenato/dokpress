<?php

/**
 * Checks {@see \App\Service\Environment} without PHPUnit.
 *
 * Run with `php tests/EnvironmentTest.php`. A failed assertion throws
 * and stops the script.
 */

namespace App\Tests;

use App\Service\Environment;

require_once __DIR__ . '/../app/Service/Environment.php';

/**
 * Stops the script when an assertion fails.
 *
 * @param bool   $condition Condition that must be true.
 * @param string $message   Text printed on success and included in the failure.
 *
 * @return void
 *
 * @throws \RuntimeException When `$condition` is false.
 */
function assert_true(bool $condition, string $message): void
{
    if (!$condition) {
        throw new \RuntimeException('FAIL: ' . $message);
    }

    echo 'PASS: ' . $message . PHP_EOL;
}

putenv('TEST_EMPTY_KEY=');
$_ENV['TEST_EMPTY_KEY'] = '';
$_SERVER['TEST_EMPTY_KEY'] = '';

assert_true(
    Environment::get('TEST_EMPTY_KEY', 'fallback') === 'fallback',
    'empty env value uses provided default'
);

assert_true(
    Environment::get('TEST_EMPTY_KEY', '') === '',
    'empty env with empty default returns empty string'
);

putenv('TEST_BOOL_TRUE=true');
$_ENV['TEST_BOOL_TRUE'] = 'true';
assert_true(
    Environment::get('TEST_BOOL_TRUE') === true,
    'string true becomes boolean true'
);

putenv('TEST_BOOL_FALSE=false');
$_ENV['TEST_BOOL_FALSE'] = 'false';
assert_true(
    Environment::get('TEST_BOOL_FALSE', true) === false,
    'string false becomes boolean false and does not use default'
);

putenv('TEST_MISSING_KEY');
unset($_ENV['TEST_MISSING_KEY'], $_SERVER['TEST_MISSING_KEY']);
assert_true(
    Environment::get('TEST_MISSING_KEY', 'missing-default') === 'missing-default',
    'missing key uses default'
);

putenv('APP_ENV=production');
$_ENV['APP_ENV'] = 'production';
assert_true(Environment::production() === true, 'production env is detected');
assert_true(Environment::local() === false, 'production is not local');

putenv('APP_ENV=development');
$_ENV['APP_ENV'] = 'development';
assert_true(Environment::production() === false, 'development is not production');
assert_true(Environment::local() === true, 'development is local');

echo PHP_EOL . 'All Environment tests passed.' . PHP_EOL;
