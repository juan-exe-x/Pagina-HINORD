-- SOLO PARA PRUEBAS con los datos de ejemplo: crea productos agotados, bajos y suficientes para ver el inventario.
-- No ejecutar sobre la base real de HINORD.
USE ganadb;
UPDATE insumo SET stock_actual = 8 WHERE nombre = 'Vacuna brucelosis';
UPDATE insumo SET stock_actual = 0 WHERE nombre = 'Ivermectina 1%';
UPDATE insumo SET stock_actual = 12 WHERE nombre = 'Jeringa 10 ml';

INSERT INTO insumo (id_categoria, nombre, descripcion, unidad_medida, precio_unitario, stock_actual, stock_minimo) VALUES
(2,'Albendazol 10%','Frasco 250 ml','frasco',42000,6,3),
(3,'Concentrado lechero','Bulto 40 kg','bulto',98000,3,10),
(3,'Melaza','Galón','galón',18000,0,4),
(4,'Guantes de palpación','Caja x100','caja',55000,1,2),
(1,'Vacuna carbón sintomático','Dosis','dosis',3800,45,15),
(4,'Desinfectante yodado','Galón','galón',36000,0,2);

INSERT INTO uso_insumo (id_insumo, fecha_uso, cantidad, precio_unitario, motivo)
SELECT i.id_insumo, CURDATE() - INTERVAL s.seq DAY, x.cant, i.precio_unitario, 'Consumo de ejemplo'
FROM seq_5_to_65 s
JOIN (SELECT 'Concentrado lechero' n, 1 cant UNION SELECT 'Melaza', 1 UNION SELECT 'Ivermectina 1%', 1) x
JOIN insumo i ON i.nombre = x.n
WHERE MOD(s.seq, 5) = 0;
