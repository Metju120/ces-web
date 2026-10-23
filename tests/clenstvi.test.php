<?php
// Run with PHP 8.2+: php tests/clenstvi.test.php. All outgoing mail is mocked.
declare(strict_types=1);
$source = file_get_contents(__DIR__ . '/../api/clenstvi.php');
token_get_all($source, TOKEN_PARSE);
$source = str_replace(['<?php', 'declare(strict_types=1);', 'exit;', 'catch (JsonException'], ['', '', "throw new \\RuntimeException('CES_RESPONSE');", 'catch (\\JsonException'], $source);
$base = ['token' => str_repeat('a', 64), 'website' => '', 'name' => 'Test zájemce', 'email' => 'applicant@example.org', 'workplace' => 'Test ordinace', 'address' => 'Test 1', 'conditions' => true];
$cases = [
 ['name' => 'valid application sends two messages', 'status' => 200, 'mail' => 2],
 ['name' => 'missing consent', 'change' => ['conditions' => false], 'status' => 422, 'mail' => 0],
 ['name' => 'invalid email', 'change' => ['email' => "applicant@example.org\r\nBcc: victim@example.org"], 'status' => 422, 'mail' => 0],
 ['name' => 'invalid CSRF', 'change' => ['token' => 'wrong'], 'status' => 403, 'mail' => 0],
 ['name' => 'honeypot', 'change' => ['website' => 'spam'], 'status' => 422, 'mail' => 0],
 ['name' => 'missing workplace', 'change' => ['workplace' => ''], 'status' => 422, 'mail' => 0],
 ['name' => 'secretariat mail failure', 'failure' => 1, 'status' => 502, 'mail' => 1],
 ['name' => 'confirmation failure keeps accepted application', 'failure' => 2, 'status' => 200, 'mail' => 2, 'confirmation' => false],
 ['name' => 'duplicate does not send again', 'duplicate' => true, 'status' => 200, 'mail' => 0],
 ['name' => 'rate limit blocks further messages', 'limited' => true, 'status' => 429, 'mail' => 0],
 ['name' => 'invalid JSON', 'invalid_json' => true, 'status' => 400, 'mail' => 0],
];
foreach ($cases as $i => $case) {
 $data = array_replace($base, $case['change'] ?? []);
 $GLOBALS['testInput'] = !empty($case['invalid_json']) ? '{bad' : json_encode($data);
 $GLOBALS['testMails'] = [];
 $GLOBALS['testFailure'] = $case['failure'] ?? 0;
 $GLOBALS['testStatus'] = 200;
 $GLOBALS['testDir'] = sys_get_temp_dir() . '/ces-form-test-' . getmypid() . '-' . $i;
 mkdir($GLOBALS['testDir'], 0700, true);
 $_SERVER = ['REQUEST_METHOD' => 'POST', 'CONTENT_TYPE' => 'application/json', 'REMOTE_ADDR' => '192.0.2.10'];
 $_SESSION = ['token' => $base['token'], 'issued' => 900];
 if (!empty($case['duplicate'])) {
  $clean = [];
  foreach (['title','name','email','phone','workplace','address','note'] as $key) $clean[$key] = trim($data[$key] ?? '');
  $_SESSION += ['accepted_fingerprint' => hash('sha256', json_encode($clean)), 'reference' => 'CES-TEST', 'confirmation' => true];
 }
 $mocks = <<<'CODE'
function header($s): void {}
function http_response_code($code): void { $GLOBALS['testStatus'] = $code; }
function session_name($s): void {}
function session_set_cookie_params($p): void {}
function session_start(): bool { return true; }
function time(): int { return 1000; }
function sys_get_temp_dir(): string { return $GLOBALS['testDir']; }
function file_get_contents($path, ...$args) { return $path === 'php://input' ? $GLOBALS['testInput'] : \file_get_contents($path, ...$args); }
function mail($to, $subject, $body, $headers): bool {
 $GLOBALS['testMails'][] = ['to'=>$to, 'body'=>base64_decode($body), 'headers'=>$headers];
 return count($GLOBALS['testMails']) !== $GLOBALS['testFailure'];
}
CODE;
 if (!empty($case['limited'])) {
  // Preload the same rate file used by the evaluated handler.
  $ratePath = $GLOBALS['testDir'] . '/ces-membership-' . substr(hash('sha256', __DIR__), 0, 20) . '.json';
  $ip = hash('sha256', __DIR__ . '0' . $_SERVER['REMOTE_ADDR']);
  file_put_contents($ratePath, json_encode(array_fill(0, 5, ['ip' => $ip, 'at' => 999])));
 }
 ob_start();
 try { eval('namespace Case' . $i . ';' . $mocks . $source); }
 catch (RuntimeException $e) { if ($e->getMessage() !== 'CES_RESPONSE') throw $e; }
 $result = json_decode(ob_get_clean(), true, 32, JSON_THROW_ON_ERROR);
 if ($GLOBALS['testStatus'] !== $case['status'] || count($GLOBALS['testMails']) !== $case['mail']) throw new RuntimeException($case['name'] . ': ' . json_encode([$GLOBALS['testStatus'], $GLOBALS['testMails'], $result]));
 if (isset($case['confirmation']) && $result['confirmation'] !== $case['confirmation']) throw new RuntimeException('Wrong confirmation status');
 if ($i === 0) {
  if ($GLOBALS['testMails'][0]['to'] !== 'sekretariat@endodont.cz' || $GLOBALS['testMails'][1]['to'] !== $base['email']) throw new RuntimeException('Wrong recipients');
  if (!str_contains($GLOBALS['testMails'][0]['body'], $base['name']) || str_contains($GLOBALS['testMails'][1]['body'], $base['name'])) throw new RuntimeException('Mail contents invalid');
 }
 foreach (glob($GLOBALS['testDir'].'/*') as $f) unlink($f);
 rmdir($GLOBALS['testDir']);
 echo 'PASS: ' . $case['name'] . "\n";
}
