<?php
declare(strict_types=1);
// PHP 8.2+ on the Czech hosting. No database, credentials or third-party form service.
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
$fields = ['title' => 80, 'name' => 200, 'email' => 254, 'phone' => 80, 'workplace' => 200, 'address' => 300, 'note' => 1500];
$clean = [];
foreach ($fields as $field => $limit) {
    $v = $data[$field] ?? '';
    if (!is_string($v) || preg_match('//u', $v) !== 1 || strlen($v) > $limit * 4 || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $v)) {
        respond(422, ['error' => 'Zkontrolujte vyplněné údaje.']);
    }
    if ($field !== 'note' && preg_match('/[\r\n]/', $v)) {
        respond(422, ['error' => 'Zkontrolujte vyplněné údaje.']);
    }
    $clean[$field] = trim($v);
}
foreach (['name', 'email', 'workplace', 'address'] as $field) {
    if ($clean[$field] === '') respond(422, ['error' => 'Vyplňte všechna povinná pole.']);
}
if (!filter_var($clean['email'], FILTER_VALIDATE_EMAIL) || strlen($clean['email']) > 254 || ($data['conditions'] ?? false) !== true) {
    respond(422, ['error' => 'Zkontrolujte e-mail a potvrzení podmínek členství.']);
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
$reference = 'CES-' . gmdate('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
$labels = ['title' => 'Titul', 'name' => 'Jméno a příjmení', 'email' => 'E-mail', 'phone' => 'Telefon', 'workplace' => 'Pracoviště', 'address' => 'Adresa pracoviště', 'note' => 'Poznámka'];
$body = "Přihláška do České endodontické společnosti\nEvidence: $reference\n\n";
foreach ($labels as $key => $label) $body .= $label . ': ' . $clean[$key] . "\n";
$body .= "\nZájemce potvrzuje splnění podmínek členství a seznámení se stanovami ČES.\n";
$headers = ['From' => 'CES sekretariat <sekretariat@endodont.cz>', 'Reply-To' => $clean['email'], 'MIME-Version' => '1.0', 'Content-Type' => 'text/plain; charset=UTF-8', 'Content-Transfer-Encoding' => 'base64'];
$subject = '=?UTF-8?B?' . base64_encode('Přihláška do ČES — ' . $reference) . '?=';
if (!mail('sekretariat@endodont.cz', $subject, chunk_split(base64_encode($body)), $headers)) {
    respond(502, ['error' => 'Přihlášku se nepodařilo předat k odeslání. Zkuste to později nebo napište sekretariátu.']);
}
$headers['Reply-To'] = 'sekretariat@endodont.cz';
$confirmation = "Dobrý den,\n\nvaše přihláška do České endodontické společnosti byla předána sekretariátu.\nEvidenční číslo: $reference\n\nSekretariát vás bude kontaktovat s dalším postupem. Toto potvrzení samo o sobě neznamená přijetí za člena.\n\nS pozdravem\nSekretariát České endodontické společnosti\nsekretariat@endodont.cz\n";
$confirmed = mail($clean['email'], '=?UTF-8?B?' . base64_encode('Potvrzení přihlášky do ČES') . '?=', chunk_split(base64_encode($confirmation)), $headers);
$_SESSION['accepted_fingerprint'] = $fingerprint;
$_SESSION['reference'] = $reference;
$_SESSION['confirmation'] = $confirmed;
respond(200, ['ok' => true, 'reference' => $reference, 'confirmation' => $confirmed]);
