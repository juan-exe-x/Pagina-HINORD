<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/layout.php';
$u = require_login();
$id = (int)($_GET['id'] ?? 0);
$error = null; $a = null;
try {
    $r = qp("SELECT * FROM registro WHERE idregistro = ?", [$id]);
    $a = $r[0] ?? null;
    if ($a) {
        $madre = qp("SELECT m.idregistro, m.nombre FROM cria c JOIN registro m ON m.idregistro = c.id_madre WHERE c.id_animal_nuevo = ?", [$id])[0] ?? null;
        $gest = qp("SELECT estado, tipo_fecundacion, fecha_servicio, fecha_parto_estimada FROM gestacion WHERE id_vaca = ? ORDER BY fecha_servicio DESC", [$id]);
        $crias = qp("SELECT r.idregistro, r.nombre, c.sexo_cria, c.peso_nacimiento, p.fecha_hora_parto
                     FROM cria c JOIN partos_reproductivo p ON p.id_parto = c.id_parto
                     JOIN registro r ON r.idregistro = c.id_animal_nuevo
                     WHERE c.id_madre = ? ORDER BY p.fecha_hora_parto DESC", [$id]);
        $prev = qp("SELECT vacunaaplicada, enfermedadpreviene, fechaapli, fechaproxima, veteresponsable
                    FROM prevenciones WHERE idregistro = ? ORDER BY fechaapli DESC", [$id]);
        $leche = qp("SELECT COALESCE(SUM(litros),0) total, COUNT(DISTINCT fecha) dias
                     FROM produccion_leche WHERE idregistro = ? AND fecha >= CURDATE() - INTERVAL 29 DAY", [$id])[0];
    }
} catch (Throwable $ex) { $error = $ex->getMessage(); }

if (!$a && !$error) http_response_code(404);
pagina_inicio($a ? $a['nombre'] : 'Animal', 'animales', $u);
?>
<p class="migas"><a href="animales.php">← Volver a animales</a></p>
<?php if ($error): ?>
  <section class="aviso"><h1>No se pudo leer la base de datos</h1><pre><?= e($error) ?></pre></section>
<?php elseif (!$a): ?>
  <section class="aviso"><h1>Animal no encontrado</h1><p>No existe un animal con ese número.</p></section>
<?php else: ?>
  <header class="encabezado ficha-cab">
    <h1><?= e($a['nombre']) ?> <?= insignia($a['estado_animal']) ?></h1>
    <p>IDICA <?= e($a['IDICA']) ?><?= $madre ? ' · hija de <a href="animal.php?id=' . (int)$madre['idregistro'] . '">' . e($madre['nombre']) . '</a>' : '' ?></p>
  </header>

  <section class="datos">
    <div><span>Sexo</span><b><?= e(ucfirst(strtolower($a['sexo']))) ?></b></div>
    <div><span>Raza</span><b><?= e($a['raza'] ?: '—') ?></b></div>
    <div><span>Clasificación</span><b><?= e($a['clasificacion'] ?: '—') ?></b></div>
    <div><span>Nacimiento</span><b><?= e(fecha($a['fecnac'])) ?></b></div>
    <div><span>Edad</span><b><?= e($a['edad']) ?> años</b></div>
    <div><span>Peso</span><b><?= e(number_format((float)$a['peso'], 0, ',', '.')) ?> kg</b></div>
  </section>

  <?php if ($a['sexo'] === 'HEMBRA'): ?>
  <section class="tarjeta bloque">
    <h2>Leche, últimos 30 días</h2>
    <?php if ((float)$leche['total'] > 0): ?>
      <p class="grande"><?= e(number_format((float)$leche['total'], 0, ',', '.')) ?> L <small>en <?= (int)$leche['dias'] ?> días · promedio <?= e(number_format($leche['total'] / max(1, $leche['dias']), 1, ',', '.')) ?> L por día</small></p>
    <?php else: ?><p class="vacio">Sin registros de producción en este periodo.</p><?php endif; ?>
  </section>

  <section class="tarjeta bloque">
    <h2>Gestaciones</h2>
    <?php if (!$gest): ?><p class="vacio">Sin gestaciones registradas.</p><?php else: ?>
    <table class="tabla chica"><thead><tr><th>Servicio</th><th>Tipo</th><th>Parto estimado</th><th>Estado</th></tr></thead><tbody>
    <?php foreach ($gest as $g): ?><tr><td><?= e(fecha($g['fecha_servicio'])) ?></td><td><?= e($g['tipo_fecundacion']) ?></td><td><?= e(fecha($g['fecha_parto_estimada'])) ?></td><td><?= e(str_replace('_', ' ', $g['estado'])) ?></td></tr><?php endforeach; ?>
    </tbody></table><?php endif; ?>
  </section>

  <section class="tarjeta bloque">
    <h2>Crías</h2>
    <?php if (!$crias): ?><p class="vacio">Sin crías registradas.</p><?php else: ?>
    <table class="tabla chica"><thead><tr><th>Cría</th><th>Sexo</th><th>Peso al nacer</th><th>Parto</th></tr></thead><tbody>
    <?php foreach ($crias as $c): ?><tr><td><a href="animal.php?id=<?= (int)$c['idregistro'] ?>"><?= e($c['nombre']) ?></a></td><td><?= e(ucfirst(strtolower($c['sexo_cria']))) ?></td><td><?= e($c['peso_nacimiento']) ?> kg</td><td><?= e(fecha($c['fecha_hora_parto'])) ?></td></tr><?php endforeach; ?>
    </tbody></table><?php endif; ?>
  </section>
  <?php endif; ?>

  <section class="tarjeta bloque">
    <h2>Sanidad: vacunas y prevenciones</h2>
    <?php if (!$prev): ?><p class="vacio">Sin prevenciones registradas.</p><?php else: ?>
    <table class="tabla chica"><thead><tr><th>Aplicación</th><th>Producto</th><th>Previene</th><th>Próxima</th><th>Veterinario</th></tr></thead><tbody>
    <?php foreach ($prev as $p): ?><tr><td><?= e(fecha($p['fechaapli'])) ?></td><td><?= e($p['vacunaaplicada']) ?></td><td><?= e($p['enfermedadpreviene']) ?></td><td><?= e(fecha($p['fechaproxima'])) ?></td><td><?= e($p['veteresponsable']) ?></td></tr><?php endforeach; ?>
    </tbody></table><?php endif; ?>
  </section>
<?php endif; pagina_fin(); ?>
