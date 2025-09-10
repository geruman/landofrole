<?php
$dsn = "mysql:host=db;dbname=" . getenv('MYSQL_DATABASE') . ";charset=utf8mb4";
$db  = new PDO($dsn, getenv('MYSQL_USER'), getenv('MYSQL_PASSWORD'), [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);
$mesas = $db->query("SELECT id, nombre, estado FROM mesas ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>LandOfRole</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>body{font-family:system-ui,Arial;margin:2rem}a{display:block;margin:.5rem 0}</style>
</head>
<body>
  <h1>LandOfRole</h1>
  <p>Bienvenido jugador, elige tu mesa:</p>
  <?php foreach ($mesas as $m): ?>
    <a href="/mesa.php?id=<?= htmlspecialchars($m['id']) ?>">
      <?= htmlspecialchars($m['nombre']) ?> (<?= htmlspecialchars($m['estado']) ?>)
    </a>
  <?php endforeach; ?>
</body>
</html>
