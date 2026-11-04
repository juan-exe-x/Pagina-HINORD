-- Datos de ejemplo (ficticios). Ejecutar después de schema.sql.
USE ganadb;
INSERT INTO usuario (user, password) VALUES ('admin','admin123');

INSERT INTO registro (nombre, IDICA, fecnac, raza, sexo, edad, peso, clasificacion, estado_animal) VALUES
('Luna',100000001,'2019-03-10','Holstein','HEMBRA',7,520,'Vaca','Activo'),
('Estrella',100000002,'2020-05-21','Normando','HEMBRA',6,495,'Vaca','Activo'),
('Paloma',100000003,'2020-08-02','Gyr lechero','HEMBRA',6,470,'Vaca','Activo'),
('Margarita',100000004,'2021-01-15','Holstein','HEMBRA',5,455,'Vaca','Activo'),
('Canela',100000005,'2021-06-30','Normando','HEMBRA',5,440,'Vaca','Activo'),
('Lucero',100000006,'2022-02-11','Holstein','HEMBRA',4,390,'Novilla','Activo'),
('Pinta',100000007,'2022-09-09','Gyr lechero','HEMBRA',4,380,'Novilla','Activo'),
('Zeus',100000008,'2018-04-04','Brahman','MACHO',8,780,'Toro','Activo'),
('Capitán',100000009,'2019-07-19','Brahman','MACHO',7,740,'Toro','Activo'),
('Tormenta',100000010,'2020-10-10','Holstein','HEMBRA',6,480,'Vaca','Enfermo'),
('Negra',100000011,'2017-12-01','Normando','HEMBRA',9,500,'Vaca','Muerto'),
('Rayo',100000012,'2023-11-02','Brahman','MACHO',2,310,'Ternero/a de levante','Activo'),
('Sol',100000013,'2025-09-20','Holstein','HEMBRA',1,60,'Ternero/a de levante','Activo'),
('Bruno',100000014,'2021-03-03','Brahman','MACHO',5,690,'Toro','Vendido');

INSERT INTO lote_semen (codigo_lote, raza_toro, nombre_toro, proveedor, fecha_ingreso, dosis_disponibles, activo) VALUES
('LS-2026-01','Holstein','Júpiter','Semen Andino','2026-03-12',18,1),
('LS-2026-02','Gyr lechero','Apolo','Genética Tropical','2026-05-20',6,1);

INSERT INTO gestacion (id_vaca, tipo_fecundacion, id_toro, id_lote_semen, fecha_servicio, fecha_parto_estimada, fecha_confirmacion, tipo_confirmacion, estado, numero_servicio) VALUES
(1,'Monta Natural',8,NULL, CURDATE() - INTERVAL 270 DAY, CURDATE() + INTERVAL 10 DAY, CURDATE() - INTERVAL 220 DAY,'Ecografia','confirmada',1),
(3,'Inseminacion Artificial',NULL,1, CURDATE() - INTERVAL 262 DAY, CURDATE() + INTERVAL 21 DAY, CURDATE() - INTERVAL 210 DAY,'Ecografia','en_curso',1),
(5,'Monta Natural',9,NULL, CURDATE() - INTERVAL 200 DAY, CURDATE() + INTERVAL 80 DAY, CURDATE() - INTERVAL 150 DAY,'Observacion Visual','confirmada',2),
(7,'Inseminacion Artificial',NULL,2, CURDATE() - INTERVAL 30 DAY, CURDATE() + INTERVAL 250 DAY, NULL, NULL,'pendiente_confirmacion',1),
(2,'Monta Natural',8,NULL, '2025-12-10','2026-09-16','2026-02-01','Ecografia','parto_registrado',1);

INSERT INTO partos_reproductivo (id_gestacion, fecha_hora_parto, tipo_parto, duracion_trabajo, estado_vaca_posparto, expulsion_placenta, calostro_suministrado, asistencia_veterinaria, medicamentos_aplicados, observaciones) VALUES
(5,'2026-09-18 05:40:00','Eutócico','2 horas','Buena','Normal','Sí','No','Ninguno','Parto sin novedad');
INSERT INTO cria (id_parto, id_animal_nuevo, id_madre, id_padre_o_lote, sexo_cria, peso_nacimiento, condicion_nacimiento, observaciones) VALUES
(1,13,2,'Zeus','HEMBRA',34.5,'Sana','');

INSERT INTO categoria_insumo (nombre, descripcion) VALUES ('Vacunas','Biológicos'),('Desparasitantes','Control parasitario'),('Alimentos','Sales y concentrados'),('Material','Agujas, jeringas');
INSERT INTO insumo (id_categoria, nombre, descripcion, unidad_medida, precio_unitario, stock_actual, stock_minimo) VALUES
(1,'Vacuna aftosa','Dosis 2 ml','dosis',4500,60,20),(1,'Vacuna brucelosis','Terneras','dosis',6000,15,10),
(2,'Ivermectina 1%','Frasco 500 ml','frasco',85000,4,2),(3,'Sal mineralizada','Bulto 40 kg','bulto',120000,9,5),
(4,'Jeringa 10 ml','Desechable','unidad',900,80,30),(4,'Aguja 16G','Desechable','unidad',400,120,50);
INSERT INTO catalogo_insumos_predefinidos (nombre, unidad_medida, precio_sugerido, categoria_sugerida, uso_recomendado) VALUES
('Vacuna aftosa','dosis',4500,'Vacunas','Cada 6 meses'),('Sal mineralizada','bulto',120000,'Alimentos','Suplemento diario');

SET @m = DATE_FORMAT(CURDATE(),'%Y-%m-01');
INSERT INTO compra_insumo (id_insumo, fecha_compra, cantidad, precio_unitario, proveedor, factura) VALUES
(1,@m,40,4500,'Agropecuaria Honda','F-1021'),(4,@m,6,120000,'Agropecuaria Honda','F-1022');
INSERT INTO uso_insumo (id_insumo, fecha_uso, cantidad, precio_unitario, id_animal, lote, motivo) VALUES
(1,CURDATE() - INTERVAL 20 DAY,1,4500,1,'L-7731','Vacunación'),(5,CURDATE() - INTERVAL 20 DAY,1,900,1,NULL,'Aplicación');

INSERT INTO produccion_leche (idregistro, fecha, litros, turno)
SELECT r.idregistro, CURDATE() - INTERVAL s.seq DAY, 6 + MOD(s.seq * 3 + r.idregistro, 6), t.turno
FROM seq_0_to_29 s
JOIN (SELECT idregistro FROM registro WHERE idregistro IN (1,2,3,4,5)) r
JOIN (SELECT 'mañana' AS turno UNION SELECT 'tarde') t;

INSERT INTO venta_leche (fecha_venta, fecha_desde, fecha_hasta, entidad, precio_litro, litros_totales, valor_total) VALUES
(@m, @m, CURDATE(), 'Cooperativa Lácteos del Tolima', 1850, 700, 1295000),
(@m, @m, CURDATE(), 'Quesera La Esperanza', 1900, 520, 988000);
INSERT INTO venta_animal (id_animal, fecha_venta, tipo_venta, destino, comprador_tipo, comprador_nombre, peso_vivo_kg, precio_por_kg, precio_total, tenia_cria, cria_vendida, num_partos_finca) VALUES
(14,@m,'en_pie','Feria ganadera','Persona natural','Carlos Pérez',690,6500,4485000,0,0,0);

INSERT INTO prevenciones (nombre, idica, idregistro, vacunaaplicada, viaadministracion, dosis, fechaapli, fechaproxima, veteresponsable, enfermedadpreviene) VALUES
('Luna',100000001,1,'Vacuna aftosa','Intramuscular',2,CURDATE() - INTERVAL 20 DAY, CURDATE() + INTERVAL 6 DAY,'Dr. Ramírez','Fiebre aftosa'),
('Paloma',100000003,3,'Vacuna brucelosis','Subcutánea',2,CURDATE() - INTERVAL 150 DAY, CURDATE() + INTERVAL 15 DAY,'Dr. Ramírez','Brucelosis'),
('Zeus',100000008,8,'Desparasitante','Subcutánea',10,CURDATE() - INTERVAL 60 DAY, CURDATE() + INTERVAL 27 DAY,'Dr. Ramírez','Parásitos internos');
