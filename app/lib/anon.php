<?php
// lib/anon.php
require_once __DIR__ . '/db.php';

/* ---------- Helpers genéricos ---------- */
function b64url(string $s): string {
    return rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
}
function randomUid22(): string {
    // 22 chars URL-safe
    return b64url(random_bytes(16));
}
function isHttps(): bool {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
}

/* ---------- Cookies de jugador anónimo ---------- */
function getAnonCookie(): ?string {
    return $_COOKIE['lor_uid'] ?? null;
}
function setAnonCookie(string $uid): void {
    setcookie('lor_uid', $uid, [
        'expires'  => time() + 60*60*24*365*5, // 5 años
        'path'     => '/',
        'secure'   => isHttps(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    $_COOKIE['lor_uid'] = $uid; // reflejar en request actual
}

/* ---------- Cookie de personaje ---------- */
function setPjCookie(int $personajeJugadorId): void {
    $val = (string)$personajeJugadorId;
    setcookie('lor_pj', $val, [
        'expires'  => time() + 60*60*24*365*5,
        'path'     => '/',
        'secure'   => isHttps(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    $_COOKIE['lor_pj'] = $val;
}
function getPjCookie(): int {
    return isset($_COOKIE['lor_pj']) ? (int)$_COOKIE['lor_pj'] : 0;
}
function clearPjCookie(): void {
    setcookie('lor_pj', '', time() - 3600, '/');
    unset($_COOKIE['lor_pj']);
}

/* ---------- Core: asegurar jugador anónimo ---------- */
/**
 * Devuelve jugadores.id asociado a la cookie lor_uid.
 * Si no existe cookie/registro, lo crea con personaje_jugador_id = NULL.
 *
 * Requisitos DB:
 *   - jugadores(id BIGINT PK, anon_uid CHAR(22) UNIQUE, personaje_jugador_id BIGINT NULL)
 */
function ensureAnon(): int {
    $pdo = db();

    // Si ya hay cookie, resolver a id
    $uid = getAnonCookie();
    if ($uid) {
        $st = $pdo->prepare('SELECT id FROM jugadores WHERE anon_uid = ?');
        $st->execute([$uid]);
        $id = (int)$st->fetchColumn();
        if ($id > 0) return $id;
        // Si la cookie apunta a un uid inexistente, seguimos a crear uno nuevo
    }

    // Crear nuevo uid y registrar; manejar posible carrera por UNIQUE(anon_uid)
    $uid = randomUid22();
    try {
        $ins = $pdo->prepare('INSERT INTO jugadores (anon_uid) VALUES (?)');
        $ins->execute([$uid]);
        setAnonCookie($uid);
        return (int)$pdo->lastInsertId();
    } catch (PDOException $e) {
        // Si hubo colisión por UNIQUE (muy raro), intentamos recuperar y reusar
        if ((int)$e->errorInfo[1] === 1062) { // duplicate key
            $st = $pdo->prepare('SELECT id FROM jugadores WHERE anon_uid = ?');
            $st->execute([$uid]);
            $id = (int)$st->fetchColumn();
            if ($id > 0) {
                setAnonCookie($uid);
                return $id;
            }
        }
        // Reintento simple con otro uid
        $uid2 = randomUid22();
        $ins2 = $pdo->prepare('INSERT INTO jugadores (anon_uid) VALUES (?)');
        $ins2->execute([$uid2]);
        setAnonCookie($uid2);
        return (int)$pdo->lastInsertId();
    }
}
