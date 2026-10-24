<?php
declare(strict_types=1);
/** Canonical scoring rules and HTML report for the migrated ČES assessment. */
function assessment_catalogue(string $path): array {
    $data = json_decode((string) file_get_contents($path), true, 32, JSON_THROW_ON_ERROR);
    if (!is_array($data) || !isset($data['groups']) || !is_array($data['groups'])) {
        throw new RuntimeException('Neplatná sada hodnoticích kritérií.');
    }
    $options = [];
    foreach ($data['groups'] as $gi => $group) {
        foreach ($group['questions'] as $qi => $question) {
            foreach (['class1' => 1, 'class2' => 2, 'class3' => 5] as $class => $points) {
                foreach ($question[$class] as $i => $item) {
                    $id = 'g'.$gi.'q'.$qi.'p'.$points.'i'.$i;
                    $options[$id] = [
                        'score' => $points,
                        'name' => (string) $item['name'],
                        'group' => (string) $group['name'],
                        'question' => (string) $question['name'],
                    ];
                }
            }
        }
    }
    return [$data, $options];
}
function assessment_score(array $ids, array $options): array {
    if (count($ids) > count($options)) throw new InvalidArgumentException('Příliš mnoho odpovědí.');
    $unique = [];
    $score = 0;
    foreach ($ids as $id) {
        if (!is_string($id) || !isset($options[$id])) {
            throw new InvalidArgumentException('Neplatná odpověď.');
        }
        if (!isset($unique[$id])) {
            $unique[$id] = true;
            $score += $options[$id]['score'];
        }
    }
    $level = $score >= 26 ? 'Třída 3' : ($score >= 18 ? 'Třída 2' : ($score >= 15 ? 'Třída 1' : 'Bez klasifikace'));
    return ['score'=>$score, 'class'=>$level, 'selected'=>array_keys($unique)];
}
function assessment_clean_label(string $s): string {
    return trim(html_entity_decode(strip_tags($s), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}
function assessment_escape(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function assessment_report_html(array $catalogue, array $result, string $senderName, string $note): string {
    $selected = array_fill_keys($result['selected'], true);
    $html = '<!doctype html><html lang="cs"><head><meta charset="UTF-8"><style>'
      .'@page{margin:15mm 11mm}body{font-family:"DejaVu Sans",sans-serif;font-size:9px;line-height:1.4;color:#182734}'
      .'h1{font-size:16px;color:#235b85}h2{font-size:13px;background:#eef4f8;padding:6px;color:#235b85}'
      .'h3{font-size:10px;margin:10px 0 3px}table{width:100%;border-collapse:collapse;margin-bottom:9px}'
      .'td,th{vertical-align:top;border:1px solid #d2dce6;padding:5px;width:33.33%;font-size:8px}'
      .'th{background:#ecf4f9;color:#235b85}.picked{font-weight:bold;color:#075e39}.muted{color:#727c86}'
      .'p{margin:6px 0}.group{page-break-before:auto}.question{page-break-inside:avoid}</style></head><body>';
    $html .= '<h1>Klasifikace endodontických případů podle stupně obtížnosti a rizik</h1>';
    $html .= '<p>Odesílatel: '.assessment_escape($senderName).' · Datum: '.date('d. m. Y').'</p>';
    $html .= '<p><strong>Celkem: '.$result['score'].' bodů · '.assessment_escape($result['class']).'</strong></p>';
    if ($note !== '') $html.='<p><strong>Poznámka:</strong> '.nl2br(assessment_escape($note)).'</p>';
    foreach ($catalogue['groups'] as $gi=>$group) {
        $html .= '<div class="group"><h2>'.assessment_escape($group['name']).'</h2>';
        foreach ($group['questions'] as $qi=>$question) {
            $html .= '<div class="question"><h3>'.assessment_escape($question['name']).'</h3>'
              .'<table><thead><tr><th>Průměrné riziko (1 bod)</th><th>Vysoké riziko (2 body)</th>'
              .'<th>Velmi vysoké riziko (5 bodů)</th></tr></thead><tbody><tr>';
            foreach (['class1'=>1,'class2'=>2,'class3'=>5] as $key=>$points) {
                $html .= '<td>';
                foreach ($question[$key] as $i=>$item) {
                    $id='g'.$gi.'q'.$qi.'p'.$points.'i'.$i;
                    $on=isset($selected[$id]);
                    $html.='<p class="'.($on?'picked':'muted').'">'.($on?'✓ ':'– ')
                      .assessment_escape(assessment_clean_label((string)$item['name'])).'</p>';
                }
                $html .= '</td>';
            }
            $html .= '</tr></tbody></table></div>';
        }
        $html .= '</div>';
    }
    $html .= '<p><small>Orientační hodnocení, nenahrazuje klinické rozhodnutí. Podle původního formuláře ČES.</small></p></body></html>';
    return $html;
}
