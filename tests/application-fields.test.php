<?php
declare(strict_types=1);
require dirname(__DIR__).'/api/application-validation.php';
$day=new DateTimeImmutable('2026-11-02');
$valid=['first_name'=>' Jana ','last_name'=>'Testovací','title_before'=>'MDDr.','title_after'=>'','faculty'=>'Lékařská fakulta','address_line'=>'Biskupská 1, 110 00 Praha','phone'=>'','email'=>'JANA@example.invalid','country_code'=>'CZ','birth_date'=>'','graduation_year'=>'2020','conditions'=>true];
function appcheck(bool $ok,string $label): void {if(!$ok)throw new RuntimeException($label);}
$v=application_input($valid,$day);
appcheck($v['first_name']==='Jana'&&$v['email']==='jana@example.invalid','normalizes names and email');
appcheck($v['birth_date']===null&&$v['phone']===''&&$v['graduation_year']===2020,'optional date and phone, required graduation year');
$sk=$valid;$sk['country_code']='SK';$sk['birth_date']='1984-02-29';appcheck(application_input($sk,$day)['birth_date']==='1984-02-29','Slovak applicant and real leap day');
foreach([
 ['faculty',''],['graduation_year',''],['graduation_year','2027'],['country_code','XX'],['birth_date','2023-02-29'],['birth_date','2099-01-01'],['first_name',"x\ny"],['email',"fake@example.invalid\r\nBcc:x@example.invalid"],['conditions',false]
] as [$key,$bad]){
 $x=$valid;$x[$key]=$bad;$rejected=false;try{application_input($x,$day);}catch(ApplicationInputError){$rejected=true;}
 appcheck($rejected,'Reject invalid or missing '.$key);
}
echo "Application validation: required education, optional contact data, Czech and Slovak addresses, dates and injection checks passed.\n";
