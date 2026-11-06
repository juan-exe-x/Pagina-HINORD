<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/layout.php';
$u = require_login('admin');

$tipo = ($_GET['tipo'] ?? $_POST['tipo'] ?? 'compra') === 'uso' ? 'uso' : 'compra';
$insumos = qp("SELECT id_insumo, nombre, unidad_medida, precio_unitario, stock_actual, proveedor_habitual, dosis_recomendada FROM insumo WHERE activo = 1 ORDER BY nombre");
$animales = qp("SELECT idregistro, nombre, IDICA FROM registro WHERE estado_animal = 'Activo' ORDER BY nombre");
$ids = array_column($insumos, null, 'id_insumo');
$num = fn($s) => is_numeric($x = str_replace(',', '.', trim((string)$s))) ? (float)$x : null;

$f = ['id' => (int)($_GET['id'] ?? 0), 'fecha' => date('Y-m-d'), 'cantidad' => '', 'precio' => '', 'proveedor' => '', 'factura' => '',
      'obs' => '', 'animal' => '', 'lote' => '', 'motivo' => '', 'dosis' => ''];
$err = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_keys($f) as $k) if (isset($_POST[$k])) $f[$k] = trim((string)$_POST[$k]);
    $f['id'] = (int)($_POST['id'] ?? 0);
    $cant = $num($f['cantidad']); $precio = $num($f['precio']);
    if (!csrf_valido($_POST['csrf'] ?? '')) $err[] = 'La sesión expiró. Vuelve a intentarlo.';
    else {
        if (!isset($ids[$f['id']])) $err[] = 'Elige un producto.';
        if ($cant === null || $cant <= 0) $err[] = 'La cantidad debe ser un número mayor que cero.';
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $f['fecha']) || !checkdate((int)substr($f['fecha'], 5, 2), (int)substr($f['fecha'], 8, 2), (int)substr($f['fecha'], 0, 4))) $err[] = 'La fecha no es válida.';
        if ($tipo === 'compra' && ($precio === null || $precio < 0)) $err[] = 'El precio unitario debe ser un número (0 o más).';
        if ($tipo === 'uso' && isset($ids[$f['id']]) && $cant !== null && $cant > (float)$ids[$f['id']]['stock_actual'])
            $err[] = 'Stock insuficiente: solo hay ' . rtrim(rtrim(number_format((float)$ids[$f['id']]['stock_actual'], 2, ',', '.'), '0'), ',') . ' ' . $ids[$f['id']]['unidad_medida'] . ' en bodega.';
    }
    if (!$err) {
        try {
            if ($tipo === 'compra') {
                db()->prepare('CALL registrar_compra_insumo(?,?,?,?,?,?,?)')->execute([$f['id'], $f['fecha'], $cant, $precio, $f['proveedor'] ?: null, $f['factura'] ?: null, $f['obs'] ?: null]);
            } else {
                db()->prepare('CALL registrar_uso_insumo(?,?,?,?,?,?,?)')->execute([$f['id'], $f['fecha'], $cant, $f['animal'] !== '' ? (int)$f['animal'] : null, $f['lote'] ?: null, $f['motivo'] ?: null, $f['dosis'] ?: null]);
            }
            flash_set(($tipo === 'compra' ? 'Compra registrada: ' : 'Uso registrado: ') . $ids[$f['id']]['nombre'] . '.');
            header('Location: inventario.php'); exit;
        } catch (PDOException $ex) {
            $err[] = (($ex->errorInfo[1] ?? 0) == 1644) ? $ex->errorInfo[2] : 'No se pudo guardar el movimiento.';
        }
    }
}
$sel = $ids[$f['id']] ?? null;
if ($tipo === 'compra' && $sel && $f['precio'] === '') $f['precio'] = rtrim(rtrim(number_format((float)$sel['precio_unitario'], 2, '.', ''), '0'), '.');
if ($tipo === 'compra' && $sel && $f['proveedor'] === '') $f['proveedor'] = (string)$sel['proveedor_habitual'];

pagina_inicio($tipo === 'compra' ? 'Registrar compra' : 'Registrar uso', 'inventario', $u);
?>
<p class="migas"><a href="inventario.php">← Volver al inventario</a></p>
<header class="encabezado"><h1><?= $tipo === 'compra' ? 'Registrar compra' : 'Registrar uso' ?></h1>
  <p><?= $tipo === 'compra' ? 'Suma la cantidad al stock y actualiza el precio del producto.' : 'Descuenta la cantidad del stock y deja el registro de dónde se usó.' ?></p></header>
<?php foreach ($err as $m): ?><div class="msg error" role="alert"><?= e($m) ?></div><?php endforeach; ?>
<form class="formulario tarjeta" method="post" action="movimiento.php">
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="tipo" value="<?= $tipo ?>">
  <label>Producto
    <select name="id" id="producto" required>
      <option value="">Elige un producto</option>
      <?php foreach ($insumos as $i): ?>
        <option value="<?= (int)$i['id_insumo'] ?>" data-precio="<?= e(rtrim(rtrim(number_format((float)$i['precio_unitario'], 2, '.', ''), '0'), '.')) ?>" data-prov="<?= e($i['proveedor_habitual']) ?>" data-dosis="<?= e($i['dosis_recomendada']) ?>" <?= $f['id'] === (int)$i['id_insumo'] ? 'selected' : '' ?>><?= e($i['nombre']) ?> (hay <?= e(rtrim(rtrim(number_format((float)$i['stock_actual'], 2, ',', '.'), '0'), ',')) ?> <?= e($i['unidad_medida']) ?>)</option>
      <?php endforeach; ?>
    </select>
  </label>
  <div class="fila">
    <label>Fecha<input type="date" name="fecha" value="<?= e($f['fecha']) ?>" required></label>
    <label>Cantidad<input type="text" inputmode="decimal" name="cantidad" value="<?= e($f['cantidad']) ?>" required></label>
    <?php if ($tipo === 'compra'): ?><label>Precio unitario ($)<input type="text" inputmode="decimal" name="precio" id="precio" value="<?= e($f['precio']) ?>" required></label><?php endif; ?>
  </div>
  <?php if ($tipo === 'compra'): ?>
  <div class="fila">
    <label>Proveedor<input type="text" name="proveedor" id="proveedor" maxlength="100" value="<?= e($f['proveedor']) ?>"></label>
    <label>N.º de factura<input type="text" name="factura" maxlength="40" value="<?= e($f['factura']) ?>"></label>
  </div>
  <label>Observaciones<input type="text" name="obs" maxlength="255" value="<?= e($f['obs']) ?>"></label>
  <?php else: ?>
  <div class="fila">
    <label>Animal (opcional)
      <select name="animal"><option value="">Ninguno en particular</option>
        <?php foreach ($animales as $a): ?><option value="<?= (int)$a['idregistro'] ?>" <?= $f['animal'] === (string)$a['idregistro'] ? 'selected' : '' ?>><?= e($a['nombre']) ?> (<?= e($a['IDICA']) ?>)</option><?php endforeach; ?>
      </select></label>
    <label>Dosis aplicada<input type="text" name="dosis" id="dosis" maxlength="60" placeholder="Ej. 2 ml por animal" value="<?= e($f['dosis']) ?>"></label>
  </div>
  <div class="fila">
    <label>Lote<input type="text" name="lote" maxlength="40" value="<?= e($f['lote']) ?>"></label>
    <label>Motivo<input type="text" name="motivo" maxlength="200" placeholder="Ej. Vacunación" value="<?= e($f['motivo']) ?>"></label>
  </div>
  <?php endif; ?>
  <div class="botones"><button type="submit">Guardar</button><a href="inventario.php">Cancelar</a></div>
</form>
<script>
document.getElementById('producto').addEventListener('change', function () {
  var o = this.options[this.selectedIndex], p = document.getElementById('precio'), v = document.getElementById('proveedor'), d = document.getElementById('dosis');
  if (p && o.dataset.precio) p.value = o.dataset.precio;
  if (v && o.dataset.prov) v.value = o.dataset.prov;
  if (d && o.dataset.dosis && !d.value) d.placeholder = o.dataset.dosis;
});
</script>
<?php pagina_fin(); ?>
