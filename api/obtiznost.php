<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
function answer(int $code, array $message): never {
    http_response_code($code);
    echo json_encode($message, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}
if (isset($_SERVER['CONTENT_LENGTH']) && (int) $_SERVER['CONTENT_LENGTH'] > 13 * 1024 * 1024) {
    answer(413, ['error'=>'Příliš velká zpráva nebo přílohy.']);
}
$method = $_SERVER['REQUEST_METHOD'] ?? '';
if (!in_array($method, ['GET','POST'], true)) {
    header('Allow: GET, POST');
    answer(405,['error'=>'Nepodporovaný požadavek.']);
}
session_name('CESASSESS');
session_set_cookie_params(['httponly'=>true,'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite'=>'Strict','path'=>'/']);
if (!session_start()) answer(503,['error'=>'Odesílání je dočasně nedostupné.']);
if ($method === 'GET') {
    if (empty($_SESSION['assessment_token']) || time()-($_SESSION['assessment_issued']??0)>3600) {
        $_SESSION['assessment_token']=bin2hex(random_bytes(32));
        $_SESSION['assessment_issued']=time();
    }
    answer(200,['service'=>'ces-assessment-v1','token'=>$_SESSION['assessment_token']]);
}
$origin=$_SERVER['HTTP_ORIGIN']??'';
if ($origin !== '') {
    $scheme=parse_url($origin, PHP_URL_SCHEME);
    $originHost=parse_url($origin, PHP_URL_HOST);
    $host=explode(':', (string)($_SERVER['HTTP_HOST']??''))[0];
    if ($scheme!=='https' || $originHost!==$host) answer(403,['error'=>'Požadavek z jiné domény nebyl povolen.']);
}
if (!is_string($_POST['token']??null)
    || !hash_equals((string)($_SESSION['assessment_token']??''),$_POST['token'])
    || time()-($_SESSION['assessment_issued']??0)>3600) {
    answer(403,['error'=>'Platnost formuláře vypršela. Obnovte stránku a zkuste to znovu.']);
}
if (time()-($_SESSION['assessment_issued']??time())<3 || !empty($_POST['website'])) {
    answer(422,['error'=>'Odeslání se nezdařilo. Zkontrolujte formulář.']);
}
if (($_POST['consent']??'') !== 'on') answer(422,['error'=>'Je potřeba potvrdit oprávněnost odeslání údajů.']);
function field(string $key, int $max): string {
    $value=$_POST[$key]??'';
    if (!is_string($value) || strlen($value)>$max*4 || preg_match('//u',$value)!==1
        || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/',$value)) {
        answer(422,['error'=>'Neplatná hodnota ve formuláři.']);
    }
    return trim($value);
}
$name=field('name',120);
$sender=field('sender',254);
$rawRecipients=field('recipients',750);
$note=field('note',2000);
if ($name==='' || $sender==='' || $rawRecipients==='' || preg_match('/[\r\n]/',$name.$sender.$rawRecipients)) {
    answer(422,['error'=>'Zadejte jméno, e-mail a adresáta bez zalomení řádku.']);
}
if (!filter_var($sender,FILTER_VALIDATE_EMAIL)) answer(422,['error'=>'E-mail odesílatele není platný.']);
$recipients=preg_split('/[\s,;]+/u',$rawRecipients,-1,PREG_SPLIT_NO_EMPTY);
if (!$recipients || count($recipients)>3) answer(422,['error'=>'Uveďte nejvýše tři příjemce.']);
$recipients=array_values(array_unique(array_map('strtolower',$recipients)));
foreach ($recipients as $addr) if (!filter_var($addr,FILTER_VALIDATE_EMAIL)) {
    answer(422,['error'=>'Některá e-mailová adresa příjemce není platná.']);
}
if (!in_array(strtolower($sender),$recipients,true)) {
    if (count($recipients)>=3) answer(422,['error'=>'Mezi maximálně třemi příjemci musí být i odesílatel.']);
    $recipients[]=strtolower($sender);
}
$answers=$_POST['answers']??'';
if (!is_string($answers) || strlen($answers)>5000) answer(422,['error'=>'Příliš mnoho odpovědí.']);
try {
    $ids=json_decode($answers,true,16,JSON_THROW_ON_ERROR);
    if (!is_array($ids) || !array_is_list($ids)) throw new InvalidArgumentException('invalid');
    require_once __DIR__.'/obtiznost-lib.php';
    [$questions,$options]=assessment_catalogue(__DIR__.'/../assets/assessment/questions.json');
    $scored=assessment_score($ids,$options);
} catch (Throwable) {
    answer(422,['error'=>'Vyplněné hodnoticí odpovědi nejsou platné.']);
}
if ($scored['score']<15) answer(422,['error'=>'Výsledek zatím nelze klasifikovat (méně než 15 bodů).']);
$uploads=[];
$allowedMime=['application/pdf'=>['pdf'], 'image/jpeg'=>['jpg','jpeg'], 'image/png'=>['png']];
foreach (['file1','file2'] as $fieldName) {
    if (!isset($_FILES[$fieldName]) || ($_FILES[$fieldName]['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE) continue;
    $f=$_FILES[$fieldName];
    if (!is_array($f) || is_array($f['name']??null) || ($f['error']??-1)!==UPLOAD_ERR_OK
        || ($f['size']??0)>5*1024*1024 || ($f['size']??0)<1 || !is_uploaded_file((string)$f['tmp_name'])) {
        answer(422,['error'=>'Přílohu se nepodařilo načíst. Každá musí být menší než 5 MB.']);
    }
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $filename=basename(str_replace('\\','/',(string)$f['name']));
    $ext=strtolower(pathinfo($filename,PATHINFO_EXTENSION));
    if (!isset($allowedMime[$mime]) || !in_array($ext,$allowedMime[$mime],true)) {
        answer(422,['error'=>'Povoleny jsou pouze přílohy PDF, JPG a PNG.']);
    }
    $cleanName=preg_replace('/[^\p{L}\p{N}_. -]/u','_', $filename);
    $uploads[]=['filename'=>mb_substr($cleanName,0,100), 'content'=>(string)file_get_contents($f['tmp_name']), 'mime'=>$mime];
}
$fingerprint=hash('sha256',json_encode([$name,$sender,$recipients,$scored['selected'],$note]));
if (($_SESSION['assessment_last']??'')===$fingerprint) {
    answer(200,['ok'=>true,'reference'=>$_SESSION['assessment_reference']]);
}
$ratePath=sys_get_temp_dir().'/ces-endo-assessment-'.substr(hash('sha256',__DIR__),0,16).'.json';
$fh=@fopen($ratePath,'c+');
if (!$fh || !flock($fh,LOCK_EX)) answer(503,['error'=>'Odesílání je dočasně nedostupné.']);
@chmod($ratePath,0600);
$history=json_decode(stream_get_contents($fh),true)?:[];
$now=time();
$history=array_values(array_filter($history,fn($row)=>is_array($row)&&($row['at']??0)>$now-3600));
$ipHash=hash('sha256',__DIR__.gmdate('Y-m-d').($_SERVER['REMOTE_ADDR']??'unknown'));
$sentByIp=count(array_filter($history,fn($row)=>($row['ip']??'')===$ipHash));
if ($sentByIp>=3 || count($history)>=25) {
    flock($fh,LOCK_UN);fclose($fh);
    header('Retry-After: 3600');
    answer(429,['error'=>'Byl překročen počet odeslání. Zkuste to později.']);
}
$history[]=['at'=>$now,'ip'=>$ipHash];
rewind($fh);ftruncate($fh,0);fwrite($fh,json_encode($history));fflush($fh);
flock($fh,LOCK_UN);fclose($fh);
$autoload=__DIR__.'/../vendor/autoload.php';
if (!is_file($autoload)) answer(503,['error'=>'Server není připraven na tvorbu PDF.']);
require_once $autoload;
try {
    $optionsPdf=new \Dompdf\Options();
    $optionsPdf->set('isRemoteEnabled',false);
    $optionsPdf->set('isPhpEnabled',false);
    $optionsPdf->set('defaultFont','DejaVu Sans');
    $pdf=new \Dompdf\Dompdf($optionsPdf);
    $pdf->loadHtml(assessment_report_html($questions,$scored,$name,$note),'UTF-8');
    $pdf->setPaper('A4');
    $pdf->render();
    $pdfBinary=$pdf->output();
    if (!is_string($pdfBinary) || strlen($pdfBinary)<500) throw new RuntimeException('PDF generation failed');
} catch (Throwable) {
    answer(503,['error'=>'Nepodařilo se vytvořit PDF přehled.']);
}
$reference='CES-ENDO-'.gmdate('Ymd').'-'.strtoupper(bin2hex(random_bytes(3)));
$boundary='ces-assessment-'.bin2hex(random_bytes(18));
$subject='=?UTF-8?B?'.base64_encode(date('d.m.Y').' Hodnocení obtížnosti endodontického případu – '.$name).'?=';
$body="Hodnocení obtížnosti endodontického případu\n"
    ."Číslo: $reference\nOdesílatel: $name <$sender>\n"
    ."Celkem: ".$scored['score']." bodů – ".$scored['class']."\n\n"
    ."Poznámka:\n".$note."\n\n"
    ."V příloze je přehled všech hodnoticích položek ve formátu PDF.\n"
    ."Klasifikace je orientační a nenahrazuje klinické rozhodnutí.\n";
$parts=[];
$parts[]='--'.$boundary."\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
    .chunk_split(base64_encode($body));
function pdf_part(string $boundary,string $content,string $mime,string $filename): string {
    return '--'.$boundary."\r\nContent-Type: ".$mime."\r\nContent-Disposition: attachment; filename=\"".$filename."\"\r\n"
        ."Content-Transfer-Encoding: base64\r\n\r\n".chunk_split(base64_encode($content));
}
$parts[]=pdf_part($boundary,$pdfBinary,'application/pdf','hodnoceni-obtiznosti.pdf');
foreach ($uploads as $file) {
    // Restrict outgoing MIME filename to ASCII to avoid email header encoding ambiguities.
    $safe='priloha-'.(count($parts)-1).'.'.strtolower(pathinfo($file['filename'],PATHINFO_EXTENSION));
    $parts[]=pdf_part($boundary,$file['content'],$file['mime'],$safe);
}
$content=implode("\r\n",$parts)."\r\n--".$boundary."--\r\n";
$headers=[
 'From: Ceska endodonticka spolecnost <sekretariat@endodont.cz>',
 'Reply-To: '.$sender,
 'MIME-Version: 1.0',
 'Content-Type: multipart/mixed; boundary="'.$boundary.'"',
 'X-Auto-Response-Suppress: All',
];
if (!mail(implode(', ', $recipients),$subject,$content,implode("\r\n",$headers))) {
    answer(502,['error'=>'Zprávu se nepodařilo předat poštovnímu serveru.']);
}
$_SESSION['assessment_last']=$fingerprint;
$_SESSION['assessment_reference']=$reference;
$_SESSION['assessment_token']=bin2hex(random_bytes(32));
answer(200,['ok'=>true,'reference'=>$reference,'score'=>$scored['score'],'classification'=>$scored['class']]);
