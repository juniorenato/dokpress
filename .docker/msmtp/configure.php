<?php

declare(strict_types=1);

/**
 * Writes `/etc/msmtprc` from the `SMTP_*` environment variables.
 *
 * Exits without a config file when SMTP is disabled or `SMTP_HOST` is empty.
 * The generated file and its log are owned by uid/gid 1000. Run this script as root.
 */

/**
 * Returns an environment value with `${VAR}` placeholders expanded.
 *
 * The result is not trimmed, so a password can keep surrounding spaces.
 *
 * @param string $name Environment variable name.
 *
 * @return string Expanded value, or an empty string when the variable is unset.
 */
function raw(string $name): string
{
    $value = getenv($name);

    return $value === false ? '' : expand($value);
}

/**
 * Returns a trimmed environment value with `${VAR}` placeholders expanded.
 *
 * @param string $name Environment variable name.
 *
 * @return string Trimmed expanded value, or an empty string when unset.
 */
function env(string $name): string
{
    return trim(raw($name));
}

/**
 * Replaces `${VAR}` placeholders using the process environment.
 *
 * Unset or empty variables are left unchanged. Expansion stops after five
 * passes so a cycle cannot recurse forever.
 *
 * @param string $value Text that may contain placeholders.
 * @param int    $depth Current expansion pass. Callers should omit this.
 *
 * @return string Text after placeholder expansion.
 */
function expand(string $value, int $depth = 0): string
{
    if ($depth > 5 || !str_contains($value, '${')) {
        return $value;
    }

    $expanded = preg_replace_callback(
        '/\$\{([A-Za-z_][A-Za-z0-9_]*)\}/',
        static function (array $match) use ($depth): string {
            $raw = getenv($match[1]);
            if ($raw === false || $raw === '') {
                return $match[0];
            }

            return expand(trim($raw), $depth + 1);
        },
        $value
    );

    return $expanded ?? $value;
}

/**
 * Reads an on/off environment flag for msmtp.
 *
 * Empty, `off`, `false`, `0` and `no` become `off`.
 * `on`, `true`, `1` and `yes` become `on`. Any other value stops the script.
 *
 * @param string $name Environment variable name.
 *
 * @return string `on` or `off`.
 */
function flag(string $name): string
{
    $value = strtolower(env($name));

    if ($value === '' || in_array($value, ['off', 'false', '0', 'no'], true)) {
        return 'off';
    }

    if (in_array($value, ['on', 'true', '1', 'yes'], true)) {
        return 'on';
    }

    fwrite(STDERR, "{$name} inválido: use on ou off.\n");
    exit(1);
}

/**
 * Quotes a value for an msmtp configuration line.
 *
 * @param string $value Raw value.
 *
 * @return string Value wrapped in double quotes, with quotes and backslashes escaped.
 */
function quote(string $value): string
{
    return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $value) . '"';
}

$enabled = strtolower(env('SMTP_ENABLED'));
if (in_array($enabled, ['off', 'false', '0', 'no'], true)) {
    if (is_file('/etc/msmtprc') && !unlink('/etc/msmtprc')) {
        fwrite(STDERR, "Não foi possível remover /etc/msmtprc.\n");
        exit(1);
    }

    fwrite(STDOUT, "SMTP desligado (SMTP_ENABLED).\n");
    exit(0);
}

if ($enabled !== '' && !in_array($enabled, ['on', 'true', '1', 'yes'], true)) {
    fwrite(STDERR, "SMTP_ENABLED inválido: use true ou false.\n");
    exit(1);
}

$host = env('SMTP_HOST');
if ($host === '') {
    fwrite(STDERR, "SMTP_HOST vazio; msmtp não configurado.\n");
    exit(0);
}

$port = env('SMTP_PORT');
if ($port === '') {
    $port = '587';
}
if (!preg_match('/^\d+$/', $port)) {
    fwrite(STDERR, "SMTP_PORT inválida.\n");
    exit(1);
}

$auth = flag('SMTP_AUTH');
$tls = flag('SMTP_TLS');
$starttls = flag('SMTP_TLS_STARTTLS');
$from = env('SMTP_FROM');

$lines = [
    'defaults',
    'auth ' . $auth,
    'tls ' . $tls,
    'tls_starttls ' . $starttls,
    'tls_trust_file /etc/ssl/certs/ca-certificates.crt',
    'logfile /var/log/php/msmtp.log',
    '',
    'account default',
    'host ' . quote($host),
    'port ' . $port,
];

if ($from !== '') {
    $lines[] = 'from ' . quote($from);
}

if ($auth === 'on') {
    $lines[] = 'user ' . quote(raw('SMTP_USER'));
    $lines[] = 'password ' . quote(raw('SMTP_PASSWORD'));
}

$lines[] = '';

$path = '/etc/msmtprc';
if (file_put_contents($path, implode("\n", $lines)) === false) {
    fwrite(STDERR, "Não foi possível gravar {$path}.\n");
    exit(1);
}

chmod($path, 0600);
if (!chown($path, 1000) || !chgrp($path, 1000)) {
    fwrite(STDERR, "Não foi possível ajustar o dono de {$path}. O configure precisa rodar como root.\n");
    exit(1);
}

$logDir = '/var/log/php';
if (!is_dir($logDir) && !mkdir($logDir, 0755, true) && !is_dir($logDir)) {
    fwrite(STDERR, "Não foi possível criar {$logDir}.\n");
    exit(1);
}

$log = $logDir . '/msmtp.log';
if (!file_exists($log) && !touch($log)) {
    fwrite(STDERR, "Não foi possível criar {$log}.\n");
    exit(1);
}

chown($log, 1000);
chgrp($log, 1000);
chmod($log, 0644);

fwrite(STDOUT, "msmtp configurado para {$host}:{$port}.\n");
