<?php
// Uso: php crear_usuario.php <usuario> [admin|usuario] ["Nombre completo"]
// Si el usuario ya existe, le cambia la contraseña y lo desbloquea.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/includes/db.php';
$usuario = $argv[1] ?? null;
$rol = $argv[2] ?? 'usuario';
$nombre = $argv[3] ?? ucfirst((string)$usuario);
if (!$usuario || !in_array($rol, ['admin', 'usuario'], true)) {
    fwrite(STDERR, "Uso: php crear_usuario.php <usuario> [admin|usuario] [\"Nombre\"]\n"); exit(1);
}
function pedir(string $msg): string {
    echo $msg;
    $tty = function_exists('posix_isatty') && posix_isatty(STDIN);
    if ($tty) shell_exec('stty -echo');
    $v = rtrim((string)fgets(STDIN), "\r\n");
    if ($tty) { shell_exec('stty echo'); echo "\n"; }
    return $v;
}
$p1 = pedir('Contraseña (mínimo 8 caracteres): ');
$p2 = pedir('Repite la contraseña: ');
if ($p1 !== $p2 || strlen($p1) < 8) { fwrite(STDERR, "Las contraseñas no coinciden o son muy cortas.\n"); exit(1); }
db()->prepare("INSERT INTO usuarios_web (usuario, nombre, password_hash, rol) VALUES (?,?,?,?)
               ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), rol = VALUES(rol),
               intentos_fallidos = 0, bloqueado_hasta = NULL, activo = 1")
    ->execute([$usuario, $nombre, password_hash($p1, PASSWORD_DEFAULT), $rol]);
echo "Listo: usuario '$usuario' ($rol).\n";
