<?php
require __DIR__ . '/includes/auth.php';
$usuario = require_login();
$error = null;
try {
    $leche   = one("SELECT COALESCE(SUM(valor_total),0) t FROM venta_leche WHERE MONTH(fecha_venta)=MONTH(CURDATE()) AND YEAR(fecha_venta)=YEAR(CURDATE())")['t'] ?? 0;
    $anim    = one("SELECT COALESCE(SUM(precio_total),0) t FROM venta_animal WHERE MONTH(fecha_venta)=MONTH(CURDATE()) AND YEAR(fecha_venta)=YEAR(CURDATE())")['t'] ?? 0;
    $insumos = one("SELECT COALESCE(SUM(valor_total),0) t FROM compra_insumo WHERE MONTH(fecha_compra)=MONTH(CURDATE()) AND YEAR(fecha_compra)=YEAR(CURDATE())")['t'] ?? 0;
    $hato    = one("SELECT COUNT(*) total, COALESCE(SUM(sexo='HEMBRA'),0) hembras, COALESCE(SUM(sexo='MACHO'),0) machos FROM registro WHERE estado_animal='Activo'");
    $gest    = one("SELECT COUNT(*) total FROM gestacion WHERE estado IN ('confirmada','en_curso')")['total'] ?? 0;
    $partos  = q("SELECT r.nombre, DATEDIFF(g.fecha_parto_estimada, CURDATE()) dias FROM gestacion g JOIN registro r ON g.id_vaca=r.idregistro WHERE g.estado IN ('confirmada','en_curso') AND g.fecha_parto_estimada BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) ORDER BY g.fecha_parto_estimada LIMIT 8");
    $vacunas = q("SELECT nombre, enfermedadpreviene, fechaproxima FROM prevenciones WHERE fechaproxima BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) ORDER BY fechaproxima LIMIT 8");
    $alertas = q("SELECT nombre, estado_animal FROM registro WHERE estado_animal IN ('Enfermo','Muerto') ORDER BY estado_animal LIMIT 8");
    $filas   = q("SELECT fecha, SUM(litros) total FROM produccion_leche WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 29 DAY) GROUP BY fecha");
} catch (Throwable $ex) {
    $error = $ex->getMessage();
}
if (!$error) {
    $porDia = array_column($filas, 'total', 'fecha');
    $serie = [];
    for ($i = 29; $i >= 0; $i--) { $d = date('Y-m-d', strtotime("-$i day")); $serie[$d] = (float)($porDia[$d] ?? 0); }
    $max = max($serie) ?: 1;
    $totalHato = max((int)$hato['total'], 1);
    $pctH = round($hato['hembras'] / $totalHato * 100);
    $balance = $leche + $anim - $insumos;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel - HINORD</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
<div class="app">
<aside class="lateral">
  <a class="marca" href="index.html">HIN<span>ORD</span></a>
  <nav>
    <a class="activo" href="dashboard.php">Panel general</a>
    <a href="animales.php">Animales</a>
    <a href="inventario.php">Inventario</a>
    <a href="estadisticas.php">Estadísticas</a>
  </nav>
  <div class="sesion"><span><?= e($usuario['nombre']) ?></span><a href="logout.php">Cerrar sesión</a></div>
</aside>
<main>
<?php if ($error): ?>
  <section class="aviso">
    <h1>No se pudo conectar con la base de datos</h1>
    <p>Revisa que MariaDB esté activa y que <code>config/config.php</code> tenga los datos correctos.</p>
    <pre><?= e($error) ?></pre>
  </section>
<?php else: ?>
  <header class="encabezado">
    <h1>Panel general</h1>
    <p>Resumen de la finca · <?= e(date('d/m/Y')) ?></p>
  </header>

  <section class="hato">
    <div class="hato-num"><?= (int)$hato['total'] ?></div>
    <div class="hato-info">
      <h2>animales activos</h2>
      <div class="barra" role="img" aria-label="<?= (int)$hato['hembras'] ?> hembras y <?= (int)$hato['machos'] ?> machos">
        <i style="width:<?= $pctH ?>%"></i>
      </div>
      <p><?= (int)$hato['hembras'] ?> hembras · <?= (int)$hato['machos'] ?> machos · <?= (int)$gest ?> en gestación</p>
    </div>
  </section>

  <section class="dinero">
    <article><h3>Venta de leche (mes)</h3><p><?= cop($leche) ?></p></article>
    <article><h3>Venta de animales (mes)</h3><p><?= cop($anim) ?></p></article>
    <article><h3>Compra de insumos (mes)</h3><p><?= cop($insumos) ?></p></article>
    <article class="<?= $balance >= 0 ? 'pos' : 'neg' ?>"><h3>Balance del mes</h3><p><?= cop($balance) ?></p></article>
  </section>

  <section class="tarjeta">
    <h2>Producción de leche, últimos 30 días</h2>
    <div class="grafica">
      <?php foreach ($serie as $d => $l): ?>
        <div class="col" title="<?= e(date('d/m', strtotime($d))) ?>: <?= e(number_format($l, 1, ',', '.')) ?> L">
          <i style="height:<?= round($l / $max * 100) ?>%"></i>
        </div>
      <?php endforeach; ?>
    </div>
    <p class="nota">Máximo del periodo: <?= e(number_format($max, 1, ',', '.')) ?> L por día</p>
  </section>

  <section class="listas">
    <article class="tarjeta">
      <h2>Partos en los próximos 30 días</h2>
      <?php if (!$partos): ?><p class="vacio">No hay partos estimados en este periodo.</p><?php endif; ?>
      <ul><?php foreach ($partos as $p): ?><li><span><?= e($p['nombre']) ?></span><b><?= (int)$p['dias'] ?> d</b></li><?php endforeach; ?></ul>
    </article>
    <article class="tarjeta">
      <h2>Vacunas y prevenciones próximas</h2>
      <?php if (!$vacunas): ?><p class="vacio">No hay prevenciones programadas.</p><?php endif; ?>
      <ul><?php foreach ($vacunas as $v): ?><li><span><?= e($v['nombre']) ?> <small><?= e($v['enfermedadpreviene']) ?></small></span><b><?= e(date('d/m', strtotime($v['fechaproxima']))) ?></b></li><?php endforeach; ?></ul>
    </article>
    <article class="tarjeta">
      <h2>Animales con alerta</h2>
      <?php if (!$alertas): ?><p class="vacio">Ningún animal enfermo o fallecido.</p><?php endif; ?>
      <ul><?php foreach ($alertas as $a): ?><li><span><?= e($a['nombre']) ?></span><b class="<?= $a['estado_animal'] === 'Muerto' ? 'neg' : 'aviso-t' ?>"><?= e($a['estado_animal']) ?></b></li><?php endforeach; ?></ul>
    </article>
  </section>
<?php endif; ?>
</main>
</div>
</body>
</html>
