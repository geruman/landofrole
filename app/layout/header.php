<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?=h($title ?? 'LandOfRole')?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="/css/app.css" rel="stylesheet">
</head>
<body>
<header class="topbar">
  <h1>LandOfRole</h1>
  <?php if (isset($mesaId, $pj, $partida)): ?>
    <span class="tag">Mesa: <?=h($mesaId)?></span>
    <span class="tag">PJ: <?=h($pj['nombre'])?></span>
    <span class="tag">Paso: <?=h($partida['paso_actual'])?></span>
  <?php endif; ?>
</header>

<?php if (!empty($activos ?? [])): ?>
  <div class="muted online">
    <strong>En mesa ahora (<?=count($activos)?>):</strong>
    <?php
      $labels = [];
      foreach ($activos as $a) {
        $labels[] = ((int)$a['id'] === (int)$pj['id']) ? '<b>'.h($a['nombre']).'</b>' : h($a['nombre']);
      }
      echo implode(', ', $labels);
    ?>
  </div>
<?php else: ?>
  <div class="muted online"><strong>En mesa ahora:</strong> nadie por el momento.</div>
<?php endif; ?>
