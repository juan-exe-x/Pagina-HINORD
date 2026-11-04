-- HINORD: esquema reconstruido a partir del código Java (consultas de los DAO y ventanas).
-- Puede diferir en tipos o detalles de la base original. Ver notas al final.
CREATE DATABASE IF NOT EXISTS ganadb CHARACTER SET utf8mb4;
USE ganadb;

SET FOREIGN_KEY_CHECKS = 0;
DROP VIEW IF EXISTS v_gestaciones_activas;
DROP PROCEDURE IF EXISTS insertar_animal;
DROP PROCEDURE IF EXISTS actualizar_animal;
DROP PROCEDURE IF EXISTS eliminar_animal;
DROP PROCEDURE IF EXISTS registrar_prevencion;
DROP TABLE IF EXISTS cria, partos_reproductivo, gestacion, lote_semen, prevenciones, produccion_leche,
  venta_animal, venta_leche, uso_insumo, compra_insumo, insumo, categoria_insumo,
  catalogo_insumos_predefinidos, vacunas, mascotas, registro, usuario;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE usuario (
  idusuario INT AUTO_INCREMENT PRIMARY KEY,
  user VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL
);

CREATE TABLE registro (
  idregistro INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(60) NOT NULL,
  IDICA BIGINT NOT NULL UNIQUE,
  fecnac DATE,
  raza VARCHAR(60),
  sexo VARCHAR(10) NOT NULL,
  edad INT,
  peso DECIMAL(8,2),
  clasificacion VARCHAR(40),
  estado_animal VARCHAR(20) NOT NULL DEFAULT 'Activo'
);

CREATE TABLE mascotas (
  idmascotas INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(60), especie VARCHAR(40), raza VARCHAR(60), edad INT,
  sexo VARCHAR(10), color VARCHAR(40), fecnac DATE, esterilizado VARCHAR(5)
);

CREATE TABLE vacunas (
  idvacunas INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(60),
  idmascotas INT,
  nombrevacuna VARCHAR(80), tipoprevencion VARCHAR(60), dosis DECIMAL(8,2),
  viaadministracion VARCHAR(40), fecaplicacion DATE, fecproxima DATE, veterinario VARCHAR(80),
  FOREIGN KEY (idmascotas) REFERENCES mascotas(idmascotas) ON DELETE CASCADE
);

CREATE TABLE categoria_insumo (
  id_categoria INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(60) NOT NULL, descripcion VARCHAR(200), activo TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE insumo (
  id_insumo INT AUTO_INCREMENT PRIMARY KEY,
  id_categoria INT NOT NULL,
  nombre VARCHAR(100) NOT NULL, descripcion VARCHAR(200), unidad_medida VARCHAR(30),
  precio_unitario DECIMAL(12,2) NOT NULL DEFAULT 0,
  stock_actual DECIMAL(12,2) NOT NULL DEFAULT 0,
  stock_minimo DECIMAL(12,2) NOT NULL DEFAULT 0,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_categoria) REFERENCES categoria_insumo(id_categoria)
);

CREATE TABLE catalogo_insumos_predefinidos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL, unidad_medida VARCHAR(30),
  precio_sugerido DECIMAL(12,2), categoria_sugerida VARCHAR(60), uso_recomendado VARCHAR(200),
  activo TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE compra_insumo (
  id_compra INT AUTO_INCREMENT PRIMARY KEY,
  id_insumo INT NOT NULL,
  fecha_compra DATE NOT NULL,
  cantidad DECIMAL(12,2) NOT NULL,
  precio_unitario DECIMAL(12,2) NOT NULL,
  valor_total DECIMAL(14,2) AS (cantidad * precio_unitario) STORED,
  proveedor VARCHAR(100), factura VARCHAR(40), observaciones VARCHAR(255),
  fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_insumo) REFERENCES insumo(id_insumo)
);

CREATE TABLE uso_insumo (
  id_uso INT AUTO_INCREMENT PRIMARY KEY,
  id_insumo INT NOT NULL,
  fecha_uso DATE NOT NULL,
  cantidad DECIMAL(12,2) NOT NULL,
  precio_unitario DECIMAL(12,2) NOT NULL,
  valor_total DECIMAL(14,2) AS (cantidad * precio_unitario) STORED,
  id_animal INT NULL, lote VARCHAR(40), motivo VARCHAR(200),
  fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_insumo) REFERENCES insumo(id_insumo),
  FOREIGN KEY (id_animal) REFERENCES registro(idregistro) ON DELETE SET NULL
);

CREATE TABLE produccion_leche (
  id_produccion INT AUTO_INCREMENT PRIMARY KEY,
  idregistro INT NOT NULL,
  fecha DATE NOT NULL,
  litros DECIMAL(8,2) NOT NULL,
  turno VARCHAR(10) NOT NULL,
  observaciones VARCHAR(255),
  FOREIGN KEY (idregistro) REFERENCES registro(idregistro) ON DELETE CASCADE
);

CREATE TABLE venta_leche (
  id_venta INT AUTO_INCREMENT PRIMARY KEY,
  fecha_venta DATE NOT NULL, fecha_desde DATE, fecha_hasta DATE,
  entidad VARCHAR(100), precio_litro DECIMAL(10,2), litros_totales DECIMAL(12,2),
  valor_total DECIMAL(14,2), observaciones VARCHAR(255)
);

CREATE TABLE venta_animal (
  id_venta INT AUTO_INCREMENT PRIMARY KEY,
  id_animal INT NOT NULL,
  fecha_venta DATE NOT NULL,
  tipo_venta VARCHAR(10) NOT NULL,          -- en_pie | carne
  destino VARCHAR(100), comprador_tipo VARCHAR(40), comprador_nombre VARCHAR(100),
  comprador_tel VARCHAR(30), comprador_nit VARCHAR(30), modalidad_carne VARCHAR(40),
  peso_vivo_kg DECIMAL(8,2), peso_canal_kg DECIMAL(8,2), precio_por_kg DECIMAL(10,2),
  precio_total DECIMAL(14,2), rendimiento_canal DECIMAL(5,2),
  tenia_cria TINYINT(1), cria_vendida TINYINT(1), num_partos_finca INT, observaciones VARCHAR(255),
  FOREIGN KEY (id_animal) REFERENCES registro(idregistro)
);

CREATE TABLE prevenciones (
  idprevenciones INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(60), idica BIGINT, idregistro INT NOT NULL,
  vacunaaplicada VARCHAR(80), viaadministracion VARCHAR(40), dosis DECIMAL(8,2),
  fechaapli DATE, fechaproxima DATE, veteresponsable VARCHAR(80), enfermedadpreviene VARCHAR(80),
  FOREIGN KEY (idregistro) REFERENCES registro(idregistro) ON DELETE CASCADE
);

CREATE TABLE lote_semen (
  id_lote INT AUTO_INCREMENT PRIMARY KEY,
  codigo_lote VARCHAR(40) NOT NULL UNIQUE, raza_toro VARCHAR(60), nombre_toro VARCHAR(60),
  proveedor VARCHAR(100), fecha_ingreso DATE, dosis_disponibles INT NOT NULL DEFAULT 0,
  activo TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE gestacion (
  id_gestacion INT AUTO_INCREMENT PRIMARY KEY,
  id_vaca INT NOT NULL,
  tipo_fecundacion VARCHAR(40) NOT NULL,    -- Monta Natural | Inseminacion Artificial | Transferencia de embriones
  id_toro INT NULL, id_lote_semen INT NULL, id_donante INT NULL,
  fecha_servicio DATE NOT NULL, fecha_parto_estimada DATE NOT NULL,
  fecha_confirmacion DATE NULL, tipo_confirmacion VARCHAR(40) NULL,   -- p. ej. Ecografia, Observacion Visual
  estado VARCHAR(30) NOT NULL DEFAULT 'pendiente_confirmacion',       -- pendiente_confirmacion | confirmada | en_curso | parto_registrado
  observaciones VARCHAR(255), numero_servicio INT DEFAULT 1,
  FOREIGN KEY (id_vaca) REFERENCES registro(idregistro),
  FOREIGN KEY (id_toro) REFERENCES registro(idregistro),
  FOREIGN KEY (id_lote_semen) REFERENCES lote_semen(id_lote),
  FOREIGN KEY (id_donante) REFERENCES registro(idregistro)
);

CREATE TABLE partos_reproductivo (
  id_parto INT AUTO_INCREMENT PRIMARY KEY,
  id_gestacion INT NOT NULL,
  fecha_hora_parto DATETIME NOT NULL, tipo_parto VARCHAR(40), duracion_trabajo VARCHAR(40),
  estado_vaca_posparto VARCHAR(60), expulsion_placenta VARCHAR(40), calostro_suministrado VARCHAR(40),
  asistencia_veterinaria VARCHAR(40), medicamentos_aplicados VARCHAR(200), observaciones VARCHAR(255),
  FOREIGN KEY (id_gestacion) REFERENCES gestacion(id_gestacion)
);

CREATE TABLE cria (
  id_cria INT AUTO_INCREMENT PRIMARY KEY,
  id_parto INT NOT NULL, id_animal_nuevo INT NOT NULL, id_madre INT NOT NULL,
  id_padre_o_lote VARCHAR(60), sexo_cria VARCHAR(10), peso_nacimiento DECIMAL(6,2),
  condicion_nacimiento VARCHAR(60), observaciones VARCHAR(255),
  FOREIGN KEY (id_parto) REFERENCES partos_reproductivo(id_parto),
  FOREIGN KEY (id_animal_nuevo) REFERENCES registro(idregistro),
  FOREIGN KEY (id_madre) REFERENCES registro(idregistro)
);

CREATE VIEW v_gestaciones_activas AS
SELECT g.id_gestacion, r.IDICA AS idica_vaca, r.nombre AS nombre_vaca, g.tipo_fecundacion,
  CASE WHEN g.tipo_fecundacion = 'Monta Natural' THEN CONCAT(rt.nombre, ' (', rt.IDICA, ')')
       WHEN g.tipo_fecundacion = 'Inseminacion Artificial' THEN CONCAT('IA: ', ls.codigo_lote)
       ELSE CONCAT('TE: ', rd.nombre) END AS origen_genetico,
  g.fecha_servicio, g.fecha_parto_estimada,
  DATEDIFF(g.fecha_parto_estimada, CURDATE()) AS dias_para_parto,
  CASE WHEN DATEDIFF(g.fecha_parto_estimada, CURDATE()) < 0 THEN 'Vencida'
       WHEN DATEDIFF(g.fecha_parto_estimada, CURDATE()) <= 15 THEN 'Parto próximo'
       ELSE 'Normal' END AS alerta,
  g.estado, g.numero_servicio, g.fecha_confirmacion
FROM gestacion g
JOIN registro r ON r.idregistro = g.id_vaca
LEFT JOIN registro rt ON rt.idregistro = g.id_toro
LEFT JOIN lote_semen ls ON ls.id_lote = g.id_lote_semen
LEFT JOIN registro rd ON rd.idregistro = g.id_donante
WHERE g.estado IN ('pendiente_confirmacion','confirmada','en_curso');

DELIMITER $$
CREATE PROCEDURE insertar_animal(IN p_nombre VARCHAR(60), IN p_idica BIGINT, IN p_fecnac DATE, IN p_raza VARCHAR(60),
  IN p_sexo VARCHAR(10), IN p_edad INT, IN p_peso DOUBLE, IN p_clasificacion VARCHAR(40), IN p_estado VARCHAR(20))
BEGIN
  INSERT INTO registro (nombre, IDICA, fecnac, raza, sexo, edad, peso, clasificacion, estado_animal)
  VALUES (p_nombre, p_idica, p_fecnac, p_raza, p_sexo, p_edad, p_peso, p_clasificacion, p_estado);
  SELECT LAST_INSERT_ID() AS nuevo_idregistro;
END$$

CREATE PROCEDURE actualizar_animal(IN p_id INT, IN p_nombre VARCHAR(60), IN p_idica BIGINT, IN p_fecnac DATE,
  IN p_raza VARCHAR(60), IN p_sexo VARCHAR(10), IN p_edad INT, IN p_peso DOUBLE, IN p_clasificacion VARCHAR(40), IN p_estado VARCHAR(20))
BEGIN
  UPDATE registro SET nombre=p_nombre, IDICA=p_idica, fecnac=p_fecnac, raza=p_raza, sexo=p_sexo,
    edad=p_edad, peso=p_peso, clasificacion=p_clasificacion, estado_animal=p_estado
  WHERE idregistro = p_id;
  SELECT ROW_COUNT() AS filas_afectadas;
END$$

CREATE PROCEDURE eliminar_animal(IN p_id INT, OUT p_mensaje VARCHAR(100))
BEGIN
  IF EXISTS (SELECT 1 FROM registro WHERE idregistro = p_id) THEN
    DELETE FROM registro WHERE idregistro = p_id;
    SET p_mensaje = 'Animal eliminado correctamente';
  ELSE
    SET p_mensaje = 'ERROR: Animal no encontrado';
  END IF;
END$$

CREATE PROCEDURE registrar_prevencion(IN p_nombre VARCHAR(60), IN p_idica BIGINT, IN p_idregistro INT,
  IN p_vacuna VARCHAR(80), IN p_via VARCHAR(40), IN p_dosis DECIMAL(8,2), IN p_fechaapli DATE,
  IN p_fechaproxima DATE, IN p_vet VARCHAR(80), IN p_enfermedad VARCHAR(80), IN p_id_insumo INT)
BEGIN
  INSERT INTO prevenciones (nombre, idica, idregistro, vacunaaplicada, viaadministracion, dosis,
    fechaapli, fechaproxima, veteresponsable, enfermedadpreviene)
  VALUES (p_nombre, p_idica, p_idregistro, p_vacuna, p_via, p_dosis, p_fechaapli, p_fechaproxima, p_vet, p_enfermedad);
  SELECT LAST_INSERT_ID() AS nuevo_idprevencion;
  -- NOTA: el descuento de insumos (p_id_insumo) lo hacía un trigger de la base original; no se reconstruye aquí.
END$$
DELIMITER ;
