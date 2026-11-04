-- SOLO PARA PRUEBAS con los datos de ejemplo: llena 2 años de historia ficticia para ver las estadísticas.
-- No ejecutar sobre la base real de HINORD.
USE ganadb;

INSERT INTO produccion_leche (idregistro, fecha, litros, turno)
SELECT r.idregistro, CURDATE() - INTERVAL s.seq DAY, 5 + MOD(s.seq * 3 + r.idregistro, 7), 'mañana'
FROM seq_30_to_729 s JOIN registro r ON r.idregistro IN (1,2,3,4,5);

INSERT INTO venta_leche (fecha_venta, fecha_desde, fecha_hasta, entidad, precio_litro, litros_totales, valor_total)
SELECT DATE_FORMAT(CURDATE() - INTERVAL seq MONTH,'%Y-%m-15'), DATE_FORMAT(CURDATE() - INTERVAL seq MONTH,'%Y-%m-01'),
       DATE_FORMAT(CURDATE() - INTERVAL seq MONTH,'%Y-%m-28'), 'Cooperativa Lácteos del Tolima',
       1800 + MOD(seq * 37, 150), 800 + MOD(seq * 53, 200), (1800 + MOD(seq * 37, 150)) * (800 + MOD(seq * 53, 200))
FROM seq_1_to_23;

INSERT INTO venta_animal (id_animal, fecha_venta, tipo_venta, destino, comprador_nombre, peso_vivo_kg, precio_por_kg, precio_total)
SELECT 14, DATE_FORMAT(CURDATE() - INTERVAL seq MONTH,'%Y-%m-20'), 'en_pie', 'Feria ganadera', 'Comprador de ejemplo', 450, 6500, 450 * 6500
FROM seq_1_to_23 WHERE MOD(seq, 4) = 0;

INSERT INTO compra_insumo (id_insumo, fecha_compra, cantidad, precio_unitario, proveedor, factura)
SELECT i.id_insumo, DATE_FORMAT(CURDATE() - INTERVAL s.seq MONTH,'%Y-%m-10'), 2 + MOD(s.seq * 7 + i.id_insumo, 9),
       i.precio_unitario, 'Agropecuaria Honda', CONCAT('F-H', s.seq, i.id_insumo)
FROM seq_1_to_23 s JOIN insumo i ON i.id_insumo IN (1,3,4);
