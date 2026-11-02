<?php
declare(strict_types=1);
// Work in progress: application data is sent via authenticated HTTPS POST.
date_default_timezone_set('Europe/Prague');
require_once __DIR__ . '/application-validation.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
function respond(int $status, array $body): never {
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}
if (($_SERVER['CONTENT_LENGTH'] ?? 0) > 16384) {
    respond(413, ['error' => 'Přihláška je příliš dlouhá.']);
}
$method = $_SERVER['REQUEST_METHOD'] ?? '';
if (!in_array($method, ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    respond(405, ['error' => 'Nepodporovaný požadavek.']);
}
session_name('CESMEMBERSHIP');
session_set_cookie_params(['httponly' => true, 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', 'samesite' => 'Strict', 'path' => '/']);
if (!session_start()) {
    respond(503, ['error' => 'Odesílání je nyní nedostupné. Napište prosím sekretariátu.']);
}
if ($method === 'GET') {
    if (!empty($_SESSION['accepted_fingerprint'])) unset($_SESSION['accepted_fingerprint'], $_SESSION['reference'], $_SESSION['confirmation'], $_SESSION['request_id'], $_SESSION['token']);
    $_SESSION['request_id'] ??= bin2hex(random_bytes(32));
    if (empty($_SESSION['token']) || time() - ($_SESSION['issued'] ?? 0) > 3600) {
        $_SESSION['token'] = bin2hex(random_bytes(32));
        $_SESSION['issued'] = time();
    }
    respond(200, ['service' => 'ces-membership-v1', 'token' => $_SESSION['token']]);
}
if (!str_starts_with(strtolower($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json')) {
    respond(415, ['error' => 'Nepodporovaný formát přihlášky.']);
}
try {
    $data = json_decode(file_get_contents('php://input', false, null, 0, 16385), true, 32, JSON_THROW_ON_ERROR);
} catch (JsonException $e) {
    respond(400, ['error' => 'Přihlášku se nepodařilo načíst.']);
}
if (!is_array($data) || !is_string($data['token'] ?? null) || empty($_SESSION['token']) || !hash_equals($_SESSION['token'], $data['token']) || time() - ($_SESSION['issued'] ?? 0) > 3600) {
    respond(403, ['error' => 'Platnost formuláře vypršela. Obnovte stránku a zkuste to znovu.']);
}
if (time() - ($_SESSION['issued'] ?? time()) < 3 || !empty($data['website'])) {
    respond(422, ['error' => 'Odeslání se nezdařilo. Zkuste to prosím znovu.']);
}
try {
    $clean = application_input($data, new DateTimeImmutable('today'));
} catch (ApplicationInputError $e) {
    respond(422, ['error' => $e->getMessage()]);
}
$fingerprint = hash('sha256', json_encode($clean));
if (($_SESSION['accepted_fingerprint'] ?? '') === $fingerprint) {
    respond(200, ['ok' => true, 'reference' => $_SESSION['reference'], 'confirmation' => $_SESSION['confirmation']]);
}
// One locked, private temporary file bounds volume globally and per IP without storing form data.
$ratePath = sys_get_temp_dir() . '/ces-membership-' . substr(hash('sha256', __DIR__), 0, 20) . '.json';
$rate = @fopen($ratePath, 'c+');
if (!$rate || !flock($rate, LOCK_EX)) respond(503, ['error' => 'Odesílání je nyní nedostupné. Napište prosím sekretariátu.']);
@chmod($ratePath, 0600);
$records = json_decode(stream_get_contents($rate), true) ?: [];
$now = time();
$records = array_filter($records, fn($row) => is_array($row) && ($row['at'] ?? 0) > $now - 3600);
$ip = hash('sha256', __DIR__ . ($now - $now % 86400) . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
$perIp = count(array_filter($records, fn($row) => ($row['ip'] ?? '') === $ip));
if ($perIp >= 5 || count($records) >= 60) {
    flock($rate, LOCK_UN); fclose($rate);
    header('Retry-After: 3600');
    respond(429, ['error' => 'Byl překročen počet pokusů. Zkuste to později nebo napište sekretariátu.']);
}
$records[] = ['ip' => $ip, 'at' => $now];
ftruncate($rate, 0); rewind($rate); fwrite($rate, json_encode(array_values($records))); fflush($rate);
flock($rate, LOCK_UN); fclose($rate);
// Credentials are provisioned separately, outside the public web root.
$privateConfig = dirname(__DIR__, 2) . '/data/ces-membership/config.php';
if (!is_file($privateConfig) || !function_exists('curl_init')) respond(503, ['error'=>'Odesílání je nyní nedostupné. Napište prosím sekretariátu.']);
$service = require $privateConfig;
$key = is_array($service) ? ($service['application_intake_secret'] ?? '') : '';
if (!is_string($key) || !preg_match('/^[a-f0-9]{64}$/', $key)) respond(503, ['error'=>'Odesílání je nyní nedostupné. Napište prosím sekretariátu.']);
$payload = $clean;
$payload['birth_date'] = $clean['birth_date'] ?? '';
$payload['conditions'] = true;
$requestId = $_SESSION['request_id'] ?? '';
if (!preg_match('/^[a-f0-9]{64}$/', $requestId)) respond(503, ['error'=>'Platnost formuláře vypršela. Obnovte stránku a zkuste to znovu.']);
$ch = curl_init('https://cesex.endodont.cz/api/application-intake');
curl_setopt_array($ch, [
    CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_CONNECTTIMEOUT => 8, CURLOPT_TIMEOUT => 30, CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2, CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json', 'X-CESEX-INTAKE-KEY: ' . $key],
    CURLOPT_POSTFIELDS => json_encode(['request_id'=>$requestId, 'application'=>$payload], JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),
]);
$response = curl_exec($ch); $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE); curl_close($ch);
if (!is_string($response) || strlen($response)>16384) respond(502, ['error'=>'Výsledek se nepodařilo ověřit. Zkuste odeslat znovu beze změny údajů, nebo kontaktujte sekretariát.']);
$result = json_decode($response, true);
if ($status !== 200 || !is_array($result) || ($result['ok'] ?? false) !== true || !is_string($result['reference'] ?? null)) {
    respond(502, ['error'=>'Uložení se nepodařilo ověřit. Zkuste to znovu beze změny údajů, nebo kontaktujte sekretariát.']);
}
$_SESSION['accepted_fingerprint'] = $fingerprint;
$_SESSION['reference'] = $result['reference'];
$_SESSION['confirmation'] = ($result['confirmation_accepted'] ?? false) === true;
respond(200, ['ok'=>true, 'reference'=>$_SESSION['reference'], 'confirmation'=>$_SESSION['confirmation']]);
