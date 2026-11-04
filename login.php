<?php
require __DIR__ . '/includes/auth.php';
if (usuario_actual()) { header('Location: dashboard.php'); exit; }

$error = '';
$usuarioVal = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuarioVal = trim($_POST['usuario'] ?? '');
    if (!csrf_valido($_POST['csrf'] ?? '')) {
        $error = 'La sesión expiró. Vuelve a intentarlo.';
    } elseif ($usuarioVal === '' || ($_POST['password'] ?? '') === '') {
        $error = 'Escribe tu usuario y tu contraseña.';
    } else {
        try {
            $r = intentar_login($usuarioVal, $_POST['password']);
            if ($r === true) { header('Location: dashboard.php'); exit; }
            $error = $r;
        } catch (Throwable $ex) {
            $error = 'No se pudo conectar con la base de datos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ingresar - HINORD</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;0,900;1,700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/logincss.css">
  <style>body{display:block}.login-page{display:grid;grid-template-columns:1fr 1fr;min-height:100vh}@media(max-width:820px){.login-page{grid-template-columns:1fr}.login-left{display:none}.login-right{padding:40px 28px}}.alerta-login{background:#fbeae7;border:1px solid #a23b2a;color:#7a2a1d;padding:10px 14px;border-radius:6px;margin-bottom:16px;font-size:.92rem}</style>
</head>
<body>
<div class="login-page">
  <div class="login-left">
    <img src="img/finca.png" alt="Finca HINORD">
    <div class="login-left-overlay">
      <a class="login-brand" href="index.html">HIN<span>ORD</span></a>
      <div class="login-quote">
        <blockquote>Tu finca,<br><span>siempre bajo control</span></blockquote>
        <p>Accede al portal para ver el estado de tu hato, producción y registros desde cualquier lugar.</p>
      </div>
      <div class="login-stats">
        <div class="ls-item"><div class="ls-num">100%</div><div class="ls-label">Digital</div></div>
        <div class="ls-item"><div class="ls-num">24/7</div><div class="ls-label">Disponible</div></div>
        <div class="ls-item"><div class="ls-num">∞</div><div class="ls-label">Registros</div></div>
      </div>
    </div>
  </div>

  <div class="login-right">
    <a href="index.html" class="back-link">← Volver al inicio</a>
    <div class="form-tag">Portal HINORD</div>
    <h1 class="form-title">Bienvenido<br>de vuelta</h1>
    <p class="form-subtitle">Ingresa tus credenciales para acceder a los datos de tu finca.</p>

    <form method="post" action="login.php" autocomplete="on">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <?php if ($error): ?><div class="alerta-login" role="alert"><?= e($error) ?></div><?php endif; ?>

      <div class="form-group">
        <label for="usuario">Usuario</label>
        <input type="text" id="usuario" name="usuario" placeholder="Tu nombre de usuario" autocomplete="username" value="<?= e($usuarioVal) ?>" required autofocus>
      </div>
      <div class="form-group">
        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password" placeholder="••••••••" autocomplete="current-password" required>
      </div>
      <div class="form-options">
        <a href="soporte.html" class="form-forgot">¿Olvidaste tu contraseña?</a>
      </div>
      <button type="submit" class="btn-login">Ingresar al sistema</button>
    </form>

    <div class="form-divider">¿Necesitas ayuda?</div>
    <div class="form-footer">Contacta a soporte en <a href="soporte.html">soporte@hinord.com</a></div>
  </div>
</div>
</body>
</html>
