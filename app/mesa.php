<?php
require __DIR__.'/lib/db.php';
require __DIR__.'/lib/anon.php';
function claimPj(PDO $pdo, int $pjId, int $jugadorId): void {
  $pdo->beginTransaction();
  try {
    // liberar dueño anterior (si hubiera)
    $pdo->prepare("UPDATE jugadores SET personaje_jugador_id = NULL WHERE personaje_jugador_id = ?")
        ->execute([$pjId]);
    // asignar al jugador actual
    $pdo->prepare("UPDATE jugadores SET personaje_jugador_id = ? WHERE id = ?")
        ->execute([$pjId, $jugadorId]);
    $pdo->commit();
  } catch (\Throwable $e) {
    $pdo->rollBack();
    throw $e;
  }
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// --- Params básicos ---
$mesa_id = $_GET['id'] ?? '';
if ($mesa_id === '') { header('Location: /'); exit; }

$pdo = db();
$jugador_id = ensureAnon();
$pdo->prepare('UPDATE jugadores SET last_seen = NOW() WHERE id = ?')->execute([$jugador_id]);

$step = $_GET['step'] ?? 'quien';
$err  = null;

// Marcar al jugador actual como activo (last_seen = NOW())
$jugadorId = ensureAnon(); // ya lo tenés en anon.php
$pdo = db();
$pdo->prepare('UPDATE jugadores SET last_seen = NOW() WHERE id = ?')->execute([$jugadorId]);


// Asegura jugador anónimo y recupera su ID desde cookie lor_uid
$jugador_id = ensureAnon();   // => jugadores.id

$step = $_GET['step'] ?? 'quien';
$err  = null;

// --- IMPORTANTE: el upsert usa UNIQUE (mesa_id, nombre) en personaje_jugador ---
/*
ALTER TABLE personaje_jugador
  ADD CONSTRAINT uq_pj_mesa_nombre UNIQUE (mesa_id, nombre);
*/

// --- POST handler ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // 1) Alta de personaje (nuevo)
  if ($step === 'nuevo') {
    $nombre = trim($_POST['nombre'] ?? '');
    $pass   = (string)($_POST['password'] ?? '');

    if ($nombre === '' || $pass === '') {
      $err = "Completá nombre y contraseña.";
    } else {
      $hash = password_hash($pass, PASSWORD_DEFAULT);

      // Upsert por (mesa_id, nombre)
      $sql = "INSERT INTO personaje_jugador (mesa_id, nombre, pass_hash)
              VALUES (?, ?, ?)
              ON DUPLICATE KEY UPDATE pass_hash = VALUES(pass_hash)";
      $st = db()->prepare($sql);
      $st->execute([$mesa_id, $nombre, $hash]);

      // Obtener id (insert o update)
      $id = (int)db()->lastInsertId();
      if ($id === 0) {
        $q = db()->prepare("SELECT id FROM personaje_jugador WHERE mesa_id=? AND nombre=?");
        $q->execute([$mesa_id, $nombre]);
        $id = (int)$q->fetchColumn();
      }

      // Vincular jugador → pj
      // Vincular jugador → pj (liberando dueño anterior si aplica)
      claimPj($pdo, $id, $jugador_id);


      // Cookie de pj
      setPjCookie($id);

      // PRG directo al juego
      header('Location: /views/play.php?mesa=' . rawurlencode((string)$mesa_id));
      exit;
    }
  }

  // 2) Login (jugador existente)
  if ($step === 'login') {
    $nombre = trim($_POST['nombre'] ?? '');
    $pass   = (string)($_POST['password'] ?? '');

    if ($nombre === '' || $pass === '') {
      $err = "Completá nombre y contraseña.";
    } else {
      $q = db()->prepare("SELECT id, pass_hash FROM personaje_jugador WHERE mesa_id=? AND nombre=?");
      $q->execute([$mesa_id, $nombre]);
      $row = $q->fetch(PDO::FETCH_ASSOC);

      $ok = false;
      if ($row) {
        // Verificación moderna (password_hash / password_verify)
        if (password_verify($pass, $row['pass_hash'])) {
          $ok = true;
        }
        // Si tenías un esquema legacy con sal fija y hash determinístico,
        // podés agregar aquí un segundo chequeo (fallback) antes de rechazar.
        // Ejemplo orientativo (desactivado):
        // else {
        //   $legacySalt = getenv('LOR_LEGACY_SALT') ?: '';
        //   if ($legacySalt !== '') {
        //     $legacy = hash('sha256', $nombre . ':' . $pass . ':' . $legacySalt);
        //     if (hash_equals($legacy, $row['pass_hash'])) { $ok = true; }
        //   }
        // }

        if ($ok) {
          // Vincular jugador → este PJ (mudarlo al jugador actual si aplica)
          // Vincular jugador → este PJ (mudarlo al jugador actual)
          claimPj($pdo, (int)$row['id'], $jugador_id);

          // Cookie de pj
          setPjCookie((int)$row['id']);

          // PRG directo al juego
          // Ventana de actividad (ej: 10 minutos). Ajustá a gusto.

          header('Location:/views/play.php?mesa=' . rawurlencode((string)$mesa_id));
          exit;
        }
      }

      // Si llegó acá, falló
      $err = "Nombre o contraseña incorrectos.";
    }
  }
}

// --- HTML ---
?>

<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Mesa <?= h($mesa_id) ?> — LandOfRole</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body{font-family:system-ui,Arial;margin:2rem;max-width:740px}
    form{margin:1rem 0} label{display:block;margin:.5rem 0 .2rem}
    input,button{padding:.5rem;font-size:1rem}
    .ok{color:green}.err{color:#b00}
    code{background:#f6f6f6;padding:.1rem .3rem;border-radius:4px}
    .choice a{display:inline-block;margin-right:1rem}
  </style>
</head>
<body>
  <h1>Mesa: <?= h($mesa_id) ?></h1>

  <?php if ($step === 'quien'): ?>
    <p>¿<strong>Te conozco</strong>?</p>
    <div class="choice">
      <a href="/mesa.php?id=<?=h($mesa_id)?>&step=nuevo">No, soy nuevo</a>
      <a href="/mesa.php?id=<?=h($mesa_id)?>&step=login">Sí</a>
    </div>

  <?php elseif ($step === 'nuevo'): ?>
    <?php if ($err): ?><p class="err"><?=h($err)?></p><?php endif; ?>
    <form method="post" autocomplete="off">
      <label>Nombre del personaje</label>
      <input name="nombre" required placeholder="Ej: Rurik">
      <label>Contraseña del personaje</label>
      <input name="password" type="password" required>
      <button>Crear personaje</button>
    </form>

  <?php elseif ($step === 'login'): ?>
    <?php if ($err): ?><p class="err"><?=h($err)?></p><?php endif; ?>
    <form method="post" autocomplete="off">
      <label>Nombre del personaje</label>
      <input name="nombre" required>
      <label>Contraseña</label>
      <input name="password" type="password" required>
      <button>Entrar</button>
    </form>

  <?php else: ?>
    <p class="err">Paso inválido.</p>
  <?php endif; ?>

  <p><a href="/index.php">Volver</a></p>
</body>
</html>
