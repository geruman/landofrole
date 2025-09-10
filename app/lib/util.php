<?php
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/**
 * Incluye una vista pasando $vars como variables locales.
 * Uso: view('play/intro', ['pj'=>$pj, 'mesaId'=>$mesaId, ...])
 */
function view(string $path, array $vars = []): void {
    extract($vars, EXTR_SKIP);
    require  $path . '.php';
}
