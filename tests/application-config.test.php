<?php
declare(strict_types=1);
require dirname(__DIR__).'/api/application-config.php';
function configcheck(bool $ok,string $message): void {if(!$ok)throw new RuntimeException($message);}
$temp=sys_get_temp_dir().'/ces-form-config-'.bin2hex(random_bytes(8));
mkdir($temp,0700);mkdir($temp.'/web',0700);mkdir($temp.'/private',0700);
$oldRoot=$_SERVER['DOCUMENT_ROOT']??null;$oldEnv=getenv('CESEX_MEMBERSHIP_CONFIG');
$_SERVER['DOCUMENT_ROOT']=$temp.'/web';$file=$temp.'/private/config.php';putenv('CESEX_MEMBERSHIP_CONFIG='.$file);
$write=static function(array $config)use($file): void {file_put_contents($file,'<?php return '.var_export($config,true).';');};
try{
 $config=['application_intake_secret'=>str_repeat('a',64),'application_mail_enabled'=>false];
 $write($config);configcheck(private_application_config()===null,'Disabled by default');
 $config['application_mail_enabled']=true;$write($config);configcheck(private_application_config()!==null,'Valid private configuration accepted');
 $config['application_intake_port']=18443;$write($config);configcheck((private_application_config()['application_intake_port']??0)===18443,'Explicit HTTPS port accepted');
 $config['application_intake_port']=65536;$write($config);configcheck(private_application_config()===null,'Invalid port refused');
 $config['application_intake_port']=443;$config['application_intake_secret']='short';$write($config);configcheck(private_application_config()===null,'Weak key refused');
 $config['application_intake_secret']=str_repeat('b',64);$write($config);
 $public=$temp.'/web/config.php';copy($file,$public);putenv('CESEX_MEMBERSHIP_CONFIG='.$public);configcheck(private_application_config()===null,'Public configuration refused');
 $link=$temp.'/private/linked.php';symlink($file,$link);putenv('CESEX_MEMBERSHIP_CONFIG='.$link);configcheck(private_application_config()===null,'Symlink refused');
 echo "Application config: disabled default, key, port, public path and symlink checks passed.\n";
}finally{
 if($oldRoot===null)unset($_SERVER['DOCUMENT_ROOT']);else $_SERVER['DOCUMENT_ROOT']=$oldRoot;
 if($oldEnv===false)putenv('CESEX_MEMBERSHIP_CONFIG');else putenv('CESEX_MEMBERSHIP_CONFIG='.$oldEnv);
 @unlink($temp.'/private/linked.php');@unlink($file);@unlink($temp.'/web/config.php');@rmdir($temp.'/private');@rmdir($temp.'/web');@rmdir($temp);
}
