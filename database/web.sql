-- Usuarios de la página web (contraseñas con hash). Independiente de la tabla `usuario` del sistema Java.
USE ganadb;
CREATE TABLE IF NOT EXISTS usuarios_web (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario VARCHAR(50) NOT NULL UNIQUE,
  nombre VARCHAR(100) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  rol VARCHAR(20) NOT NULL DEFAULT 'usuario',   -- admin | usuario
  activo TINYINT(1) NOT NULL DEFAULT 1,
  intentos_fallidos INT NOT NULL DEFAULT 0,
  bloqueado_hasta DATETIME NULL,
  ultimo_acceso DATETIME NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
