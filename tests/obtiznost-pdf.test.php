<?php
declare(strict_types=1);
require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/../api/obtiznost-lib.php';
[$catalogue,$options]=assessment_catalogue(__DIR__.'/../assets/assessment/questions.json');
$scored=assessment_score(['g0q0p5i0','g0q0p5i1','g0q0p5i2'],$options);
$html=assessment_report_html($catalogue,$scored,'Test ČES','Příloha je anonymizována.');
$cfg=new \Dompdf\Options();
$cfg->set('isRemoteEnabled',false);
$cfg->set('isPhpEnabled',false);
$cfg->set('defaultFont','DejaVu Sans');
$doc=new \Dompdf\Dompdf($cfg);
$doc->loadHtml($html,'UTF-8');
$doc->setPaper('A4');
$doc->render();
$pdf=$doc->output();
if (!str_starts_with($pdf,'%PDF-') || strlen($pdf)<5000) {
    fwrite(STDERR,"PDF export failed\n");exit(1);
}
echo 'PDF export OK, '.strlen($pdf)." bytes\n";
