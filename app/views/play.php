<?php
require __DIR__ . '/../controllers/play_controller.php';

$data = cargarPlayData(); // trae $mesaId, $pj, $activos, $partida, etc.

$title = "LandOfRole — Mesa {$data['mesaId']} — {$data['pj']['nombre']}";
view('../layout/header', ['title'=>$title, 'mesaId'=>$data['mesaId'], 'pj'=>$data['pj'], 'partida'=>$data['partida'], 'activos'=>$data['activos']]);

// Cuerpo según el paso
switch ($data['partida']['paso_actual']) {
    case 'intro':            view('../views/intro',          $data); break;
    case 'confirmaciones':   view('../views/confirmaciones', $data); break;
    case 'primera_escena':   view('../views/primera_escena', $data); break;
    case 'escena_2':         view('../views/escena_2',       $data); break;
    case 'fin_demo':         view('../views/fin_demo',       $data); break;
    default:
        echo '<main><h2>'.h($data['partida']['paso_actual']).'</h2><p>(Sin render definido.)</p></main>';
}

view('../layout/footer');
