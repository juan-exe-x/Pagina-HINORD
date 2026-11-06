-- Piloto del módulo de inventario: dosis y proveedor habitual en el producto, y reglas de stock en la base.
-- Las compras y los usos se registran SIEMPRE con estos procedimientos, desde la web, Java o Python,
-- así el stock se calcula igual en todos lados.
-- OJO: si la base real ya descuenta el stock con triggers, avisar antes de usar estos procedimientos (se contaría doble).
USE ganadb;
ALTER TABLE insumo ADD COLUMN IF NOT EXISTS dosis_recomendada VARCHAR(100) NULL, ADD COLUMN IF NOT EXISTS proveedor_habitual VARCHAR(100) NULL;
ALTER TABLE uso_insumo ADD COLUMN IF NOT EXISTS dosis_aplicada VARCHAR(60) NULL;

DROP PROCEDURE IF EXISTS registrar_compra_insumo;
DROP PROCEDURE IF EXISTS registrar_uso_insumo;
DELIMITER $$
CREATE PROCEDURE registrar_compra_insumo(IN p_id_insumo INT, IN p_fecha DATE, IN p_cantidad DECIMAL(12,2),
  IN p_precio DECIMAL(12,2), IN p_proveedor VARCHAR(100), IN p_factura VARCHAR(40), IN p_obs VARCHAR(255))
BEGIN
  DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;
  START TRANSACTION;
  IF NOT EXISTS (SELECT 1 FROM insumo WHERE id_insumo = p_id_insumo AND activo = 1) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El producto no existe o está inactivo';
  END IF;
  IF p_cantidad <= 0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La cantidad debe ser mayor que cero'; END IF;
  INSERT INTO compra_insumo (id_insumo, fecha_compra, cantidad, precio_unitario, proveedor, factura, observaciones)
  VALUES (p_id_insumo, p_fecha, p_cantidad, p_precio, p_proveedor, p_factura, p_obs);
  UPDATE insumo SET stock_actual = stock_actual + p_cantidad, precio_unitario = p_precio WHERE id_insumo = p_id_insumo;
  COMMIT;
END$$

CREATE PROCEDURE registrar_uso_insumo(IN p_id_insumo INT, IN p_fecha DATE, IN p_cantidad DECIMAL(12,2),
  IN p_id_animal INT, IN p_lote VARCHAR(40), IN p_motivo VARCHAR(200), IN p_dosis VARCHAR(60))
BEGIN
  DECLARE v_stock DECIMAL(12,2) DEFAULT NULL;
  DECLARE v_precio DECIMAL(12,2) DEFAULT 0;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;
  START TRANSACTION;
  SELECT stock_actual, precio_unitario INTO v_stock, v_precio FROM insumo WHERE id_insumo = p_id_insumo AND activo = 1 FOR UPDATE;
  IF v_stock IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El producto no existe o está inactivo'; END IF;
  IF p_cantidad <= 0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La cantidad debe ser mayor que cero'; END IF;
  IF p_cantidad > v_stock THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Stock insuficiente'; END IF;
  INSERT INTO uso_insumo (id_insumo, fecha_uso, cantidad, precio_unitario, id_animal, lote, motivo, dosis_aplicada)
  VALUES (p_id_insumo, p_fecha, p_cantidad, v_precio, p_id_animal, p_lote, p_motivo, p_dosis);
  UPDATE insumo SET stock_actual = stock_actual - p_cantidad WHERE id_insumo = p_id_insumo;
  COMMIT;
END$$
DELIMITER ;

-- Dosis de ejemplo para los productos de prueba (no falla si no existen).
UPDATE insumo SET dosis_recomendada = '2 ml por animal, vía intramuscular', proveedor_habitual = 'Agropecuaria Honda' WHERE nombre = 'Vacuna aftosa' AND dosis_recomendada IS NULL;
UPDATE insumo SET dosis_recomendada = '1 ml por cada 50 kg de peso', proveedor_habitual = 'Agropecuaria Honda' WHERE nombre = 'Ivermectina 1%' AND dosis_recomendada IS NULL;
