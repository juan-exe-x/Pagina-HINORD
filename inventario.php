<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/layout.php';
$u = require_login();
$esAdmin = $u['rol'] === 'admin';

function num($v): string { return rtrim(rtrim(number_format((float)$v, 2, ',', '.'), '0'), ','); }

$q = trim($_GET['q'] ?? '');
$estado = $_GET['estado'] ?? '';
$cat = $_GET['cat'] ?? '';
$error = null;
try {
    $rows = qp("SELECT i.id_insumo, i.nombre, i.unidad_medida, i.precio_unitario, i.stock_actual, i.stock_minimo, i.dosis_recomendada, i.proveedor_habitual, c.nombre AS categoria,
        COALESCE((SELECT SUM(u.cantidad) FROM uso_insumo u WHERE u.id_insumo = i.id_insumo AND u.fecha_uso >= CURDATE() - INTERVAL 90 DAY), 0) / 90 AS consumo_dia,
        (SELECT MAX(co.fecha_compra) FROM compra_insumo co WHERE co.id_insumo = i.id_insumo) AS ultima_compra
        FROM insumo i JOIN categoria_insumo c ON c.id_categoria = i.id_categoria WHERE i.activo = 1 ORDER BY i.nombre");
    $mov = qp("(SELECT 'Compra' tipo, co.fecha_compra fecha, i.nombre, co.cantidad, i.unidad_medida, co.valor_total valor, COALESCE(co.proveedor,'') detalle, co.id_compra id
                FROM compra_insumo co JOIN insumo i ON i.id_insumo = co.id_insumo)
               UNION ALL
               (SELECT 'Uso', u.fecha_uso, i.nombre, u.cantidad, i.unidad_medida, u.valor_total, COALESCE(u.motivo,''), u.id_uso
                FROM uso_insumo u JOIN insumo i ON i.id_insumo = u.id_insumo)
               ORDER BY fecha DESC, id DESC LIMIT 10");
} catch (Throwable $ex) { $error = $ex->getMessage(); $rows = []; $mov = []; }

$items = []; $cuenta = ['ok' => 0, 'bajo' => 0, 'sin' => 0]; $presupuesto = 0.0; $valorTotal = 0.0;
foreach ($rows as $r) {
    $st = (float)$r['stock_actual']; $mn = (float)$r['stock_minimo']; $cd = (float)$r['consumo_dia'];
    $nivel = $st <= 0 ? 'sin' : ($st <= $mn ? 'bajo' : 'ok');
    $cob = $cd > 0 ? $st / $cd : null;
    $prio = $nivel === 'sin' ? 1 : ($nivel === 'bajo' ? ((($cob !== null && $cob <= 15) || $st <= $mn * 0.5) ? 2 : 3) : 0);
    $sug = $nivel === 'ok' ? 0 : max(1, (int)ceil($mn * 2 - $st));
    $costo = $sug * (float)$r['precio_unitario'];
    $cuenta[$nivel]++; $presupuesto += $costo; $valorTotal += $st * (float)$r['precio_unitario'];
    $items[] = $r + ['nivel' => $nivel, 'cob' => $cob, 'prio' => $prio, 'sug' => $sug, 'costo' => $costo, 'consumo' => $cd];
}
$comprar = array_values(array_filter($items, fn($i) => $i['prio'] > 0));
usort($comprar, fn($a, $b) => [$a['prio'], $a['prio'] === 1 ? -$a['consumo'] : ($a['cob'] ?? INF), $a['nombre']]
                           <=> [$b['prio'], $b['prio'] === 1 ? -$b['consumo'] : ($b['cob'] ?? INF), $b['nombre']]);

$cats = array_values(array_unique(array_column($items, 'categoria'))); sort($cats);
if (!in_array($estado, ['sin', 'bajo', 'ok'], true)) $estado = '';
if (!in_array($cat, $cats, true)) $cat = '';
$lista = array_filter($items, fn($i) => ($estado === '' || $i['nivel'] === $estado) && ($cat === '' || $i['categoria'] === $cat)
                                      && ($q === '' || stripos($i['nombre'], $q) !== false));
$prioNom = [1 => ['Urgente', 'rojo'], 2 => ['Alta', 'aviso'], 3 => ['Media', 'media']];
$nivelNom = ['sin' => ['Sin stock', 'rojo'], 'bajo' => ['Bajo', 'aviso'], 'ok' => ['Suficiente', 'ok']];

pagina_inicio('Inventario', 'inventario', $u);
?>
<header class="encabezado">
  <h1>Inventario de insumos</h1>
  <p>Qué hay en la finca, qué se está acabando y qué conviene comprar primero</p>
</header>
<?php foreach (flash_get() as $m): ?><div class="msg <?= e($m[1]) ?>" role="status"><?= e($m[0]) ?></div><?php endforeach; ?>
<?php if ($esAdmin): ?>
<div class="acciones no-imprimir">
  <a class="btn" href="insumo_form.php">+ Nuevo producto</a>
  <a class="btn sec" href="movimiento.php?tipo=compra">Registrar compra</a>
  <a class="btn sec" href="movimiento.php?tipo=uso">Registrar uso</a>
</div>
<?php endif; ?>
<?php if ($error): ?>
  <section class="aviso"><h1>No se pudo leer la base de datos</h1><pre><?= e($error) ?></pre></section>
<?php else: ?>
<section class="dinero">
  <article><h3>Productos</h3><p><?= count($items) ?></p></article>
  <article class="pos"><h3>Con stock suficiente</h3><p><?= $cuenta['ok'] ?></p></article>
  <article class="amar"><h3>Stock bajo</h3><p><?= $cuenta['bajo'] ?></p></article>
  <article class="neg"><h3>Sin stock</h3><p><?= $cuenta['sin'] ?></p></article>
</section>
<p class="nota valor">Valor del inventario en bodega: <b><?= e(cop($valorTotal)) ?></b> (cantidad × precio unitario)</p>

<section class="tarjeta bloque">
  <div class="cab-lista">
    <h2>Prioridad de compra</h2>
    <button type="button" class="no-imprimir" onclick="window.print()">Imprimir lista</button>
  </div>
  <?php if (!$comprar): ?>
    <p class="vacio">Todo el inventario está por encima del mínimo. No hay nada urgente por comprar.</p>
  <?php else: ?>
  <div class="tabla-caja sin-borde"><table class="tabla chica">
    <thead><tr><th>Prioridad</th><th>Producto</th><th>Categoría</th><th>Hay</th><th>Mínimo</th><th>Alcanza para</th><th>Comprar</th><th>Costo estimado</th></tr></thead>
    <tbody>
    <?php foreach ($comprar as $c): ?>
      <tr>
        <td><span class="chip <?= $prioNom[$c['prio']][1] ?>"><?= $prioNom[$c['prio']][0] ?></span></td>
        <td><b><?= e($c['nombre']) ?></b></td>
        <td><?= e($c['categoria']) ?></td>
        <td><?= e(num($c['stock_actual'])) ?> <small><?= e($c['unidad_medida']) ?></small></td>
        <td><?= e(num($c['stock_minimo'])) ?></td>
        <td><?= $c['nivel'] === 'sin' ? 'Agotado' : ($c['cob'] === null ? '—' : ($c['cob'] > 365 ? 'más de 1 año' : '≈ ' . max(1, (int)round($c['cob'])) . ' días')) ?></td>
        <td><b><?= (int)$c['sug'] ?></b> <small><?= e($c['unidad_medida']) ?></small></td>
        <td><?= e(cop($c['costo'])) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot><tr><td colspan="7">Costo estimado de reponer todo</td><td><b><?= e(cop($presupuesto)) ?></b></td></tr></tfoot>
  </table></div>
  <?php endif; ?>
  <details class="no-imprimir"><summary>¿Cómo se calcula la prioridad?</summary>
    <ul class="reglas">
      <li><b>Urgente:</b> el producto está agotado.</li>
      <li><b>Alta:</b> está bajo el mínimo y le queda menos de la mitad de ese mínimo, o alcanza para 15 días o menos según lo que se ha gastado en los últimos 90 días.</li>
      <li><b>Media:</b> está bajo el mínimo, pero todavía con margen.</li>
      <li>La cantidad sugerida es la necesaria para llegar al doble del mínimo, valorada al precio unitario registrado.</li>
    </ul>
  </details>
</section>

<section class="tarjeta bloque">
  <h2>Todos los productos</h2>
  <form class="filtros no-imprimir" method="get" action="inventario.php">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar producto" aria-label="Buscar producto">
    <select name="estado" aria-label="Nivel de stock">
      <option value="">Todos los niveles</option>
      <?php foreach ($nivelNom as $k => $n): ?><option value="<?= $k ?>" <?= $estado === $k ? 'selected' : '' ?>><?= $n[0] ?></option><?php endforeach; ?>
    </select>
    <select name="cat" aria-label="Categoría">
      <option value="">Todas las categorías</option>
      <?php foreach ($cats as $c1): ?><option <?= $cat === $c1 ? 'selected' : '' ?>><?= e($c1) ?></option><?php endforeach; ?>
    </select>
    <button type="submit">Filtrar</button>
    <?php if ($q . $estado . $cat !== ''): ?><a class="limpiar" href="inventario.php">Limpiar</a><?php endif; ?>
  </form>
  <div class="tabla-caja sin-borde"><table class="tabla chica">
    <thead><tr><th>Producto</th><th>Categoría</th><th>Hay</th><th>Mínimo</th><th>Nivel</th><th>Última compra</th><th>Precio unitario</th><th>Valor</th><th>Dosis recomendada</th><?php if ($esAdmin): ?><th class="no-imprimir"></th><?php endif; ?></tr></thead>
    <tbody>
    <?php foreach ($lista as $i):
      $mn = (float)$i['stock_minimo']; $st = (float)$i['stock_actual'];
      $w = $mn > 0 ? min(100, round($st / ($mn * 2) * 100)) : ($st > 0 ? 100 : 0); ?>
      <tr>
        <td><b><?= e($i['nombre']) ?></b></td>
        <td><?= e($i['categoria']) ?></td>
        <td><?= e(num($i['stock_actual'])) ?> <small><?= e($i['unidad_medida']) ?></small></td>
        <td><?= e(num($i['stock_minimo'])) ?></td>
        <td><span class="medidor" aria-hidden="true"><i class="<?= $nivelNom[$i['nivel']][1] ?>" style="width:<?= $w ?>%"></i></span> <span class="chip <?= $nivelNom[$i['nivel']][1] ?>"><?= $nivelNom[$i['nivel']][0] ?></span></td>
        <td><?= e(fecha($i['ultima_compra'])) ?></td>
        <td><?= e(cop($i['precio_unitario'])) ?></td>
        <td><?= e(cop($st * (float)$i['precio_unitario'])) ?></td>
        <td><?= e($i['dosis_recomendada'] ?: '—') ?></td>
        <?php if ($esAdmin): ?><td class="no-imprimir acc"><a href="movimiento.php?tipo=compra&id=<?= (int)$i['id_insumo'] ?>">Comprar</a> <a href="movimiento.php?tipo=uso&id=<?= (int)$i['id_insumo'] ?>">Usar</a> <a href="insumo_form.php?id=<?= (int)$i['id_insumo'] ?>">Editar</a></td><?php endif; ?>
      </tr>
    <?php endforeach; ?>
    <?php if (!$lista): ?><tr><td colspan="10" class="vacio">No hay productos con esos filtros.</td></tr><?php endif; ?>
    </tbody>
  </table></div>
</section>
<section class="tarjeta bloque">
  <h2>Últimos movimientos</h2>
  <?php if (!$mov): ?><p class="vacio">Todavía no hay compras ni usos registrados.</p><?php else: ?>
  <div class="tabla-caja sin-borde"><table class="tabla chica">
    <thead><tr><th>Fecha</th><th>Tipo</th><th>Producto</th><th>Cantidad</th><th>Valor</th><th>Detalle</th></tr></thead>
    <tbody>
    <?php foreach ($mov as $m): ?>
      <tr><td><?= e(fecha($m['fecha'])) ?></td><td><span class="chip <?= $m['tipo'] === 'Compra' ? 'ok' : 'media' ?>"><?= e($m['tipo']) ?></span></td><td><?= e($m['nombre']) ?></td>
          <td><?= e(num($m['cantidad'])) ?> <small><?= e($m['unidad_medida']) ?></small></td><td><?= e(cop($m['valor'])) ?></td><td><?= e($m['detalle']) ?></td></tr>
    <?php endforeach; ?>
    </tbody></table></div>
  <?php endif; ?>
</section>
<?php endif; pagina_fin(); ?>
