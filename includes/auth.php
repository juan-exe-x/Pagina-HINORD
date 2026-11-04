<?php
require_once __DIR__ . '/db.php';

function iniciar_sesion(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => !empty($_SERVER['HTTPS']),
        ]);
        session_start();
    }
}
iniciar_sesion();

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_valido(string $t): bool {
    return !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $t);
}

function usuario_actual(): ?array {
    if (empty($_SESSION['uid'])) return null;
    return ['id' => $_SESSION['uid'], 'usuario' => $_SESSION['usuario'], 'nombre' => $_SESSION['nombre'], 'rol' => $_SESSION['rol']];
}

// Protege una página. Con $rol exige ese rol (por ejemplo 'admin').
function require_login(?string $rol = null): array {
    $u = usuario_actual();
    if (!$u) { header('Location: login.php'); exit; }
    if ($rol !== null && $u['rol'] !== $rol) {
        http_response_code(403);
        exit('Acceso denegado: esta sección es solo para ' . e($rol) . '.');
    }
    return $u;
}

// Devuelve true si entró, o el mensaje de error para mostrar.
function intentar_login(string $usuario, string $pass) {
    $st = db()->prepare("SELECT *, (bloqueado_hasta IS NOT NULL AND bloqueado_hasta > NOW()) AS bloqueado
                         FROM usuarios_web WHERE usuario = ? AND activo = 1");
    $st->execute([$usuario]);
    $row = $st->fetch();

    if ($row && $row['bloqueado']) {
        return 'Demasiados intentos fallidos. Espera unos minutos e inténtalo de nuevo.';
    }
    // Si el usuario no existe se verifica contra un hash falso, para que no se note la diferencia.
    $hash = $row['password_hash'] ?? password_hash('relleno', PASSWORD_DEFAULT);
    if (!$row || !password_verify($pass, $hash)) {
        if ($row) {
            db()->prepare("UPDATE usuarios_web
                SET bloqueado_hasta = IF(intentos_fallidos + 1 >= 5, DATE_ADD(NOW(), INTERVAL 5 MINUTE), bloqueado_hasta),
                    intentos_fallidos = intentos_fallidos + 1 WHERE id = ?")->execute([$row['id']]);
        }
        return 'Usuario o contraseña incorrectos.';
    }
    if (password_needs_rehash($row['password_hash'], PASSWORD_DEFAULT)) {
        db()->prepare("UPDATE usuarios_web SET password_hash = ? WHERE id = ?")
            ->execute([password_hash($pass, PASSWORD_DEFAULT), $row['id']]);
    }
    db()->prepare("UPDATE usuarios_web SET intentos_fallidos = 0, bloqueado_hasta = NULL, ultimo_acceso = NOW() WHERE id = ?")
        ->execute([$row['id']]);
    session_regenerate_id(true);
    $_SESSION['uid'] = (int)$row['id'];
    $_SESSION['usuario'] = $row['usuario'];
    $_SESSION['nombre'] = $row['nombre'];
    $_SESSION['rol'] = $row['rol'];
    return true;
}
