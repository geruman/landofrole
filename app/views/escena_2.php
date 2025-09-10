<main>
  <h2>Escena 2</h2>
  <?php $estado = json_decode($partida['estado_json'] ?? 'null', true) ?: []; ?>
  <p class="muted">Llegaste acá por: <b><?=h((string)($estado['opcion_elegida'] ?? ''))?></b></p>
  <p>Continuará…</p>
  <form method="post">
    <button name="action" value="continuar">Seguir</button>
  </form>
</main>
