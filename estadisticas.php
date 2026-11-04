<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/layout.php';
$u = require_login();

$n = (int)($_GET['meses'] ?? 12);
if (!in_array($n, [6, 12, 24], true)) $n = 12;
$abre = ['01' => 'ene', '02' => 'feb', '03' => 'mar', '04' => 'abr', '05' => 'may', '06' => 'jun',
         '07' => 'jul', '08' => 'ago', '09' => 'sep', '10' => 'oct', '11' => 'nov', '12' => 'dic'];
$meses = [];
$d = new DateTime('first day of this month');
$d->modify('-' . ($n - 1) . ' months');
for ($i = 0; $i < $n; $i++) { $meses[$d->format('Y-m')] = ['ing' => 0.0, 'gas' => 0.0, 'lit' => 0.0]; $d->modify('+1 month'); }
$desde = array_key_first($meses) . '-01';

$error = null;
try {
    foreach ([['venta_leche', 'fecha_venta', 'valor_total', 'ing'], ['venta_animal', 'fecha_venta', 'precio_total', 'ing'],
              ['compra_insumo', 'fecha_compra', 'valor_total', 'gas'], ['produccion_leche', 'fecha', 'litros', 'lit']] as [$tab, $fec, $val, $k]) {
        foreach (qp("SELECT DATE_FORMAT($fec,'%Y-%m') m, SUM($val) t FROM $tab WHERE $fec >= ? GROUP BY m", [$desde]) as $r) {
            if (isset($meses[$r['m']])) $meses[$r['m']][$k] += (float)$r['t'];
        }
    }
    $top = qp("SELECT r.idregistro, r.nombre, SUM(p.litros) t FROM produccion_leche p JOIN registro r ON r.idregistro = p.idregistro
               WHERE p.fecha >= CURDATE() - INTERVAL 29 DAY GROUP BY r.idregistro ORDER BY t DESC LIMIT 5");
    $porClas = qp("SELECT COALESCE(clasificacion,'Sin clasificar') et, COUNT(*) t FROM registro WHERE estado_animal='Activo' GROUP BY et ORDER BY t DESC");
    $porRaza = qp("SELECT COALESCE(raza,'Sin raza') et, COUNT(*) t FROM registro WHERE estado_animal='Activo' GROUP BY et ORDER BY t DESC");
    $porCat = qp("SELECT cat.nombre et, SUM(c.valor_total) t FROM compra_insumo c JOIN insumo i ON i.id_insumo = c.id_insumo
                  JOIN categoria_insumo cat ON cat.id_categoria = i.id_categoria WHERE c.fecha_compra >= ? GROUP BY cat.id_categoria ORDER BY t DESC", [$desde]);
} catch (Throwable $ex) { $error = $ex->getMessage(); }

function etiqueta_mes(string $ym, array $abre, bool $anio): string {
    [$y, $m] = explode('-', $ym);
    return $abre[$m] . ($anio ? ' ’' . substr($y, 2) : '');
}
function hbar(array $filas, callable $fmt): void {
    $max = max(array_map(fn($f) => (float)$f['t'], $filas) ?: [0]) ?: 1;
    echo '<ul class="hbar">';
    foreach ($filas as $f) {
        echo '<li><span class="et">' . e($f['et'] ?? $f['nombre']) . '</span><span class="pista"><i style="width:' . round($f['t'] / $max * 100) . '%"></i></span><b>' . e($fmt($f['t'])) . '</b></li>';
    }
    echo '</ul>';
}

pagina_inicio('Estadísticas', 'estadisticas', $u);
?>
<header class="encabezado">
  <h1>Estadísticas</h1>
  <p>Resumen de los últimos <?= $n ?> meses</p>
</header>
<?php if ($error): ?>
  <section class="aviso"><h1>No se pudo leer la base de datos</h1><pre><?= e($error) ?></pre></section>
<?php else:
  $ing = array_sum(array_column($meses, 'ing')); $gas = array_sum(array_column($meses, 'gas')); $lit = array_sum(array_column($meses, 'lit'));
  $maxPlata = max(array_merge(array_column($meses, 'ing'), array_column($meses, 'gas'))) ?: 1;
  $maxLit = max(array_column($meses, 'lit')) ?: 1;
?>
<nav class="rango" aria-label="Periodo">
  <?php foreach ([6, 12, 24] as $o): ?><a class="<?= $o === $n ? 'sel' : '' ?>" href="estadisticas.php?meses=<?= $o ?>"><?= $o ?> meses</a><?php endforeach; ?>
</nav>

<section class="dinero">
  <article><h3>Ingresos</h3><p><?= cop($ing) ?></p></article>
  <article><h3>Gastos en insumos</h3><p><?= cop($gas) ?></p></article>
  <article class="<?= $ing - $gas >= 0 ? 'pos' : 'neg' ?>"><h3>Balance</h3><p><?= cop($ing - $gas) ?></p></article>
  <article><h3>Leche producida</h3><p><?= e(number_format($lit, 0, ',', '.')) ?> L</p></article>
</section>

<section class="tarjeta bloque">
  <h2>Ingresos y gastos por mes</h2>
  <p class="leyenda"><span class="a"></span> Ingresos (leche y animales) <span class="b"></span> Gastos en insumos</p>
  <div class="barras" role="img" aria-label="Ingresos y gastos mensuales de los últimos <?= $n ?> meses">
    <?php foreach ($meses as $m => $v): ?>
      <div class="par" title="<?= e(etiqueta_mes($m, $abre, true)) ?> · ingresos <?= e(cop($v['ing'])) ?> · gastos <?= e(cop($v['gas'])) ?>">
        <i class="a" style="height:<?= round($v['ing'] / $maxPlata * 100) ?>%"></i><i class="b" style="height:<?= round($v['gas'] / $maxPlata * 100) ?>%"></i>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="etiquetas"><?php $k = 0; foreach ($meses as $m => $v): $k++; ?><span><?= ($n <= 12 || $k % 2) ? e(etiqueta_mes($m, $abre, substr($m, 5) === '01' || $k === 1)) : '' ?></span><?php endforeach; ?></div>
  <p class="nota">Barra más alta: <?= e(cop($maxPlata)) ?></p>
  <details><summary>Ver tabla de datos</summary>
    <table class="tabla chica"><thead><tr><th>Mes</th><th>Ingresos</th><th>Gastos</th><th>Balance</th></tr></thead><tbody>
    <?php foreach ($meses as $m => $v): ?><tr><td><?= e(etiqueta_mes($m, $abre, true)) ?></td><td><?= e(cop($v['ing'])) ?></td><td><?= e(cop($v['gas'])) ?></td><td><?= e(cop($v['ing'] - $v['gas'])) ?></td></tr><?php endforeach; ?>
    </tbody></table>
  </details>
</section>

<section class="tarjeta bloque">
  <h2>Producción de leche por mes</h2>
  <div class="barras" role="img" aria-label="Litros de leche producidos por mes">
    <?php foreach ($meses as $m => $v): ?>
      <div class="par" title="<?= e(etiqueta_mes($m, $abre, true)) ?> · <?= e(number_format($v['lit'], 0, ',', '.')) ?> L"><i class="a" style="height:<?= round($v['lit'] / $maxLit * 100) ?>%"></i></div>
    <?php endforeach; ?>
  </div>
  <div class="etiquetas"><?php $k = 0; foreach ($meses as $m => $v): $k++; ?><span><?= ($n <= 12 || $k % 2) ? e(etiqueta_mes($m, $abre, substr($m, 5) === '01' || $k === 1)) : '' ?></span><?php endforeach; ?></div>
  <p class="nota">Mes de mayor producción: <?= e(number_format($maxLit, 0, ',', '.')) ?> L</p>
</section>

<section class="dos">
  <article class="tarjeta"><h2>Mejores productoras (30 días)</h2>
    <?php if (!$top): ?><p class="vacio">Sin registros de producción.</p><?php else: hbar($top, fn($t) => number_format($t, 0, ',', '.') . ' L'); endif; ?>
  </article>
  <article class="tarjeta"><h2>Gasto en insumos por categoría</h2>
    <?php if (!$porCat): ?><p class="vacio">Sin compras en este periodo.</p><?php else: hbar($porCat, fn($t) => cop($t)); endif; ?>
  </article>
  <article class="tarjeta"><h2>Hato activo por clasificación</h2>
    <?php if (!$porClas): ?><p class="vacio">Sin animales activos.</p><?php else: hbar($porClas, fn($t) => (int)$t); endif; ?>
  </article>
  <article class="tarjeta"><h2>Hato activo por raza</h2>
    <?php if (!$porRaza): ?><p class="vacio">Sin animales activos.</p><?php else: hbar($porRaza, fn($t) => (int)$t); endif; ?>
  </article>
</section>
<?php endif; pagina_fin(); ?>
