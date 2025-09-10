<?php
require __DIR__ . '/../lib/db.php';
require __DIR__ . '/../lib/anon.php';
require __DIR__ . '/../lib/util.php';

function cargarPlayData(): array {
    $mesaId = $_GET['mesa'] ?? '';
    if ($mesaId === '') {
        header('Location: /index.php'); exit;
    }

    $pjId = getPjCookie();
    if (!$pjId) {
        header('Location: /mesa.php?id='.rawurlencode((string)$mesaId)); exit;
    }

    $pdo = db();
    // PJ
    $pjStmt = $pdo->prepare('SELECT id, nombre, mesa_id FROM personaje_jugador WHERE id = ?');
    $pjStmt->execute([$pjId]);
    $pj = $pjStmt->fetch(PDO::FETCH_ASSOC);
    if (!$pj) {
        setcookie('lor_pj', '', time()-3600, '/');
        header('Location: /mesa.php?id='.rawurlencode((string)$mesaId)); exit;
    }

    // keep-alive jugador
    $jugadorId = ensureAnon();
    $pdo->prepare('UPDATE jugadores SET last_seen = NOW() WHERE id = ?')->execute([$jugadorId]);

    $activeWindow = '10 MINUTE';
    $activeStmt = $pdo->prepare("
        SELECT pj.id, pj.nombre
        FROM jugadores j
        JOIN personaje_jugador pj ON pj.id = j.personaje_jugador_id
        WHERE pj.mesa_id = ?
          AND j.last_seen >= (NOW() - INTERVAL $activeWindow)
        ORDER BY pj.nombre
    ");
    $activeStmt->execute([$mesaId]);
    $activos = $activeStmt->fetchAll(PDO::FETCH_ASSOC);

    // Partida
    $partida = getOrCreatePartida($pdo, $mesaId, (int)$pjId);

    // Router de acciones (POST) – muta estado y vuelve por PRG
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        switch ($partida['paso_actual']) {
            case 'intro':
                if ($action === 'continuar') {
                    updatePaso($pdo, (int)$partida['id'], 'confirmaciones');
                }
                break;
            case 'confirmaciones':
                if ($action === 'aceptar_terminos') {
                    updatePaso($pdo, (int)$partida['id'], 'primera_escena');
                } elseif ($action === 'volver') {
                    updatePaso($pdo, (int)$partida['id'], 'intro');
                }
                break;
            case 'primera_escena':
                if ($action === 'elegir_opcion') {
                    $op = $_POST['op'] ?? '';
                    updatePaso($pdo, (int)$partida['id'], 'escena_2', ['opcion_elegida'=>$op]);
                } elseif ($action === 'volver') {
                    updatePaso($pdo, (int)$partida['id'], 'confirmaciones');
                }
                break;
            case 'escena_2':
                if ($action === 'continuar') {
                    updatePaso($pdo, (int)$partida['id'], 'fin_demo');
                }
                break;
        }
        // Post/Redirect/Get para evitar repost
        header('Location: /views/play.php?mesa='.rawurlencode((string)$mesaId));
        exit;
    }

    // Refrescar partida por si cambió
    $partida = getOrCreatePartida($pdo, $mesaId, (int)$pjId);

    return compact('pdo','mesaId','pj','activos','partida');
}

// --- servicios de Partida: quedan junto al controlador (o en otro archivo si querés)
function getOrCreatePartida(PDO $pdo, string $mesaId, int $pjId): array {
    $sel = $pdo->prepare('SELECT * FROM partidas WHERE mesa_id = ? AND pj_id = ?');
    $sel->execute([$mesaId, $pjId]);
    $row = $sel->fetch(PDO::FETCH_ASSOC);
    if ($row) return $row;

    $pdo->prepare('INSERT INTO partidas (mesa_id, pj_id, paso_actual, estado_json) VALUES (?, ?, "intro", JSON_OBJECT())')
        ->execute([$mesaId, $pjId]);

    $sel->execute([$mesaId, $pjId]);
    return $sel->fetch(PDO::FETCH_ASSOC);
}

function updatePaso(PDO $pdo, int $partidaId, string $nuevoPaso, ?array $estado = null): void {
    if ($estado === null) {
        $st = $pdo->prepare('UPDATE partidas SET paso_actual = ?, updated_at = NOW() WHERE id = ?');
        $st->execute([$nuevoPaso, $partidaId]);
    } else {
        $st = $pdo->prepare('UPDATE partidas SET paso_actual = ?, estado_json = ?, updated_at = NOW() WHERE id = ?');
        $st->execute([$nuevoPaso, json_encode($estado, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), $partidaId]);
    }
}
