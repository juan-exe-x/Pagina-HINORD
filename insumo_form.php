<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/layout.php';
$u = require_login('admin');

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$cats = qp("SELECT id_categoria, nombre FROM categoria_insumo WHERE activo = 1 ORDER BY nombre");
$f = ['nombre' => '', 'id_categoria' => '', 'unidad_medida' => '', 'descripcion' => '', 'precio_unitario' => '', 'stock_minimo' => '0',
      'stock_actual' => '0', 'dosis_recomendada' => '', 'proveedor_habitual' => '', 'activo' => 1];
if ($id) {
    $r = qp("SELECT * FROM insumo WHERE id_insumo = ?", [$id])[0] ?? null;
    if (!$r) { http_response_code(404); exit('Producto no encontrado.'); }
    foreach ($f as $k => $_) $f[$k] = $r[$k] ?? $f[$k];
    foreach (['precio_unitario', 'stock_minimo', 'stock_actual'] as $k) $f[$k] = rtrim(rtrim(number_format((float)$f[$k], 2, '.', ''), '0'), '.') ?: '0';
}
$num = fn($s) => is_numeric($x = str_replace(',', '.', trim((string)$s))) ? (float)$x : null;
$err = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_keys($f) as $k) if ($k !== 'activo' && isset($_POST[$k])) $f[$k] = trim((string)$_POST[$k]);
    $f['activo'] = $id ? (isset($_POST['activo']) ? 1 : 0) : 1;
    $precio = $num($f['precio_unitario']); $min = $num($f['stock_minimo']); $ini = $num($f['stock_actual']);
    if (!csrf_valido($_POST['csrf'] ?? '')) $err[] = 'La sesión expiró. Vuelve a intentarlo.';
    else {
        if ($f['nombre'] === '') $err[] = 'El nombre es obligatorio.';
        if (!in_array((int)$f['id_categoria'], array_column($cats, 'id_categoria'))) $err[] = 'Elige una categoría.';
        if ($precio === null || $precio < 0) $err[] = 'El precio unitario debe ser un número (0 o más).';
        if ($min === null || $min < 0) $err[] = 'El stock mínimo debe ser un número (0 o más).';
        if (!$id && ($ini === null || $ini < 0)) $err[] = 'El stock inicial debe ser un número (0 o más).';
        if (qp("SELECT 1 FROM insumo WHERE nombre = ? AND activo = 1 AND id_insumo <> ?", [$f['nombre'], $id])) $err[] = 'Ya existe un producto activo con ese nombre.';
    }
    if (!$err) {
        $v = [(int)$f['id_categoria'], $f['nombre'], $f['descripcion'] ?: null, $f['unidad_medida'] ?: null, $precio, $min, $f['dosis_recomendada'] ?: null, $f['proveedor_habitual'] ?: null];
        if ($id) {
            db()->prepare("UPDATE insumo SET id_categoria=?, nombre=?, descripcion=?, unidad_medida=?, precio_unitario=?, stock_minimo=?, dosis_recomendada=?, proveedor_habitual=?, activo=? WHERE id_insumo=?")
                ->execute(array_merge($v, [$f['activo'], $id]));
            flash_set('Producto actualizado: ' . $f['nombre'] . '.');
        } else {
            db()->prepare("INSERT INTO insumo (id_categoria, nombre, descripcion, unidad_medida, precio_unitario, stock_minimo, dosis_recomendada, proveedor_habitual, stock_actual) VALUES (?,?,?,?,?,?,?,?,?)")
                ->execute(array_merge($v, [$ini]));
            flash_set('Producto creado: ' . $f['nombre'] . '.');
        }
        header('Location: inventario.php'); exit;
    }
}
pagina_inicio($id ? 'Editar producto' : 'Nuevo producto', 'inventario', $u);
$val = fn($k) => e($f[$k]);
?>
<p class="migas"><a href="inventario.php">← Volver al inventario</a></p>
<header class="encabezado"><h1><?= $id ? 'Editar producto' : 'Nuevo producto' ?></h1></header>
<?php foreach ($err as $m): ?><div class="msg error" role="alert"><?= e($m) ?></div><?php endforeach; ?>
<form class="formulario tarjeta" method="post" action="insumo_form.php">
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= $id ?>">
  <label>Nombre del producto<input type="text" name="nombre" maxlength="100" value="<?= $val('nombre') ?>" required></label>
  <div class="fila">
    <label>Categoría
      <select name="id_categoria" required><option value="">Elige una categoría</option>
        <?php foreach ($cats as $c): ?><option value="<?= (int)$c['id_categoria'] ?>" <?= (int)$f['id_categoria'] === (int)$c['id_categoria'] ? 'selected' : '' ?>><?= e($c['nombre']) ?></option><?php endforeach; ?>
      </select></label>
    <label>Unidad de medida<input type="text" name="unidad_medida" maxlength="30" placeholder="frasco, bulto, dosis…" value="<?= $val('unidad_medida') ?>"></label>
  </div>
  <label>Presentación o características<input type="text" name="descripcion" maxlength="200" placeholder="Ej. Frasco de 500 ml" value="<?= $val('descripcion') ?>"></label>
  <div class="fila">
    <label>Precio unitario ($)<input type="text" inputmode="decimal" name="precio_unitario" value="<?= $val('precio_unitario') ?>" required></label>
    <label>Stock mínimo<input type="text" inputmode="decimal" name="stock_minimo" value="<?= $val('stock_minimo') ?>" required></label>
    <?php if (!$id): ?><label>Stock inicial (lo que tienes hoy)<input type="text" inputmode="decimal" name="stock_actual" value="<?= $val('stock_actual') ?>" required></label><?php endif; ?>
  </div>
  <div class="fila">
    <label>Dosis recomendada<input type="text" name="dosis_recomendada" maxlength="100" placeholder="Ej. 2 ml por animal" value="<?= $val('dosis_recomendada') ?>"></label>
    <label>Proveedor habitual<input type="text" name="proveedor_habitual" maxlength="100" value="<?= $val('proveedor_habitual') ?>"></label>
  </div>
  <?php if ($id): ?>
    <p class="ayuda">El stock actual (<?= $val('stock_actual') ?>) cambia solo al registrar compras y usos.</p>
    <label class="check"><input type="checkbox" name="activo" <?= $f['activo'] ? 'checked' : '' ?>> Producto activo (desmárcalo para dejar de usarlo)</label>
  <?php endif; ?>
  <div class="botones"><button type="submit">Guardar</button><a href="inventario.php">Cancelar</a></div>
</form>
<?php pagina_fin(); ?>
