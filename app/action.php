<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__.'/../lib/db.php';

$in = json_decode(file_get_contents('php://input'), true) ?? [];
$tipo = $in['type'] ?? '';
$pj_id = (int)($in['pj_id'] ?? 0);
$mesa  = $in['mesa_id'] ?? '';

function out($arr){ echo json_encode($arr, JSON_UNESCAPED_UNICODE); exit; }

if (!$tipo || !$pj_id || !$mesa) out(['ok'=>false,'error'=>'bad-request']);

switch ($tipo) {
  case 'OBSERVAR':
    $t = $in['target'] ?? '';
    $texto = match($t){
      'taquilla'        => 'En la taquilla hay papeles arrugados y un recibo con tinta corrida.',
      'cartel_horarios' => 'El último autobús llegó hace 20 minutos. Una línea está tachada con marcador.',
      'policia'         => 'El policía te mira de reojo, parece agotado y con ojeras profundas.',
      default           => 'No ves nada fuera de lo común.'
    };
    out(['ok'=>true,'texto'=>$texto]);

  case 'MOVER':
    $d = $in['destino'] ?? '';
    $texto = ($d==='avenida') ? 'Sales a la avenida bajo la lluvia. Un neón parpadea a lo lejos.'
                              : 'No encuentras salida por ahí.';
    out(['ok'=>true,'texto'=>$texto]);

  default:
    out(['ok'=>false,'error'=>'action-not-implemented']);
}
