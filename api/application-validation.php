<?php
declare(strict_types=1);

// The public application must be validated on the receiving server too. This
// module does not expose an endpoint or send mail; connection is a later step.
final class ApplicationInputError extends RuntimeException {}
function application_input(array $input, DateTimeImmutable $today): array {
 $limits=['first_name'=>100,'last_name'=>100,'title_before'=>100,'title_after'=>100,'faculty'=>200,'address_line'=>500,'phone'=>40,'email'=>254];
 $v=[];
 foreach($limits as $key=>$limit){
  $x=$input[$key]??'';
  if(!is_string($x)||!preg_match('//u',$x)||preg_match('/[\x00-\x1f\x7f]/',$x)||mb_strlen($x)>$limit)throw new ApplicationInputError('Zkontrolujte vyplněné údaje.');
  $v[$key]=trim($x);
 }
 foreach(['first_name','last_name','faculty','address_line'] as $key)if($v[$key]==='')throw new ApplicationInputError('Vyplňte jméno, příjmení, fakultu a bydliště.');
 $v['email']=mb_strtolower($v['email']);
 if(!filter_var($v['email'],FILTER_VALIDATE_EMAIL))throw new ApplicationInputError('Zkontrolujte e-mail.');
 $country=$input['country_code']??'';if(!is_string($country)||!in_array($country,['CZ','SK'],true))throw new ApplicationInputError('Vyberte Česko nebo Slovensko.');
 $v['country_code']=$country;
 $birth=$input['birth_date']??'';if(!is_string($birth))throw new ApplicationInputError('Zkontrolujte datum narození.');
 if($birth!==''){$date=DateTimeImmutable::createFromFormat('!Y-m-d',$birth);if(!$date||$date->format('Y-m-d')!==$birth||$birth>'9999-12-31'||$birth<'1900-01-01'||$date>$today)throw new ApplicationInputError('Zkontrolujte datum narození.');}
 $v['birth_date']=$birth?:null;
 $year=$input['graduation_year']??null;
 if(!is_string($year)&&!is_int($year))throw new ApplicationInputError('Vyplňte rok promoce.');
 if(!preg_match('/^\d{4}$/',(string)$year)||(int)$year<1900||(int)$year>(int)$today->format('Y'))throw new ApplicationInputError('Zkontrolujte rok promoce.');
 $v['graduation_year']=(int)$year;
 if(($input['conditions']??null)!==true)throw new ApplicationInputError('Potvrďte žádost o členství a podmínky stanov.');
 return $v;
}
