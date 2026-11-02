<?php
declare(strict_types=1);

// The integration secret is read only from an administrator-controlled file
// outside the public website; an optional path supports different host layouts.
function private_application_config(): ?array {
    $file = getenv('CESEX_MEMBERSHIP_CONFIG') ?: dirname(__DIR__, 2) . '/data/ces-membership/config.php';
    if (!is_string($file) || !str_starts_with($file, '/') || !is_file($file) || is_link($file) || is_link(dirname($file))) return null;
    $real = realpath($file);
    $root = realpath((string)($_SERVER['DOCUMENT_ROOT'] ?? ''));
    if ($real === false || ($root !== false && ($real === $root || str_starts_with($real, $root . DIRECTORY_SEPARATOR)))) return null;
    try { $config = require $real; } catch (Throwable $e) { return null; }
    if (!is_array($config) || ($config['application_mail_enabled'] ?? false) !== true) return null;
    if (!is_string($config['application_intake_secret'] ?? null) || !preg_match('/^[a-f0-9]{64}$/', $config['application_intake_secret'])) return null;
    $port = $config['application_intake_port'] ?? 443;
    if (!is_int($port) || $port < 1 || $port > 65535) return null;
    return $config;
}
