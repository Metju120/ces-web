<?php
declare(strict_types=1);
require __DIR__.'/../api/obtiznost-lib.php';
[$catalogue,$map]=assessment_catalogue(__DIR__.'/../assets/assessment/questions.json');
assert(count($catalogue['groups'])===3);
assert(array_sum(array_map(fn($g)=>count($g['questions']),$catalogue['groups']))===13);
assert(count($map)===84);
assert(assessment_score(['g0q0p5i0','g0q0p5i1','g0q0p5i2'],$map)['class']==='Třída 1');
assert(assessment_score(['g0q0p5i0','g0q0p5i1','g0q0p5i2','g0q1p2i0'],$map)['class']==='Třída 1');
assert(assessment_score(['g0q0p5i0','g0q0p5i1','g0q0p5i2','g0q1p2i0','g0q1p1i0'],$map)['class']==='Třída 2');
assert(assessment_score(['g0q0p5i0','g0q0p5i1','g0q0p5i2','g0q1p2i0','g0q1p1i0','g0q2p5i0','g0q2p2i0'],$map)['class']==='Třída 2');
assert(assessment_score(['g0q0p5i0','g0q0p5i1','g0q0p5i2','g0q1p2i0','g0q1p1i0','g0q2p5i0','g0q2p2i0','g0q3p1i0'],$map)['class']==='Třída 3');
try{assessment_score(['nonexistent'],$map);throw new RuntimeException('Invalid answer accepted');}catch(InvalidArgumentException){}
$html=assessment_report_html($catalogue,assessment_score(['g0q0p5i0','g0q0p5i1','g0q0p5i2'],$map),'Tester','');
assert(str_contains($html,'A. Vliv pacienta') && str_contains($html,'Třída 1'));
echo "Assessment scoring and report: OK (13 questions, 84 choices)\n";
