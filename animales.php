<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/layout.php';
$u = require_login();

$q = trim($_GET['q'] ?? '');
$sexo = $_GET['sexo'] ?? '';
$estado = $_GET['estado'] ?? '';
$clas = $_GET['clas'] ?? '';
$pag = max(1, (int)($_GET['p'] ?? 1));
$por = 15;
$error = null;
try {
    $estados = array_column(q("SELECT DISTINCT estado_animal e FROM registro ORDER BY 1"), 'e');
    $clases  = array_column(q("SELECT DISTINCT clasificacion c FROM registro WHERE clasificacion IS NOT NULL ORDER BY 1"), 'c');
    $where = ['1=1']; $par = [];
    if ($q !== '') { $where[] = '(nombre LIKE ? OR CAST(IDICA AS CHAR) LIKE ?)'; $par[] = "%$q%"; $par[] = "%$q%"; }
    if (in_array($sexo, ['HEMBRA', 'MACHO'], true)) { $where[] = 'sexo = ?'; $par[] = $sexo; } else { $sexo = ''; }
    if (in_array($estado, $estados, true)) { $where[] = 'estado_animal = ?'; $par[] = $estado; } else { $estado = ''; }
    if (in_array($clas, $clases, true)) { $where[] = 'clasificacion = ?'; $par[] = $clas; } else { $clas = ''; }
    $w = implode(' AND ', $where);
    $total = (int)qp("SELECT COUNT(*) n FROM registro WHERE $w", $par)[0]['n'];
    $paginas = max(1, (int)ceil($total / $por));
    $pag = min($pag, $paginas);
    $off = ($pag - 1) * $por;
    $filas = qp("SELECT idregistro, nombre, IDICA, sexo, raza, clasificacion, edad, peso, estado_animal
                 FROM registro WHERE $w ORDER BY nombre LIMIT $por OFFSET $off", $par);
} catch (Throwable $ex) { $error = $ex->getMessage(); }

function enlace_pag(int $p): string {
    return 'animales.php?' . http_build_query(array_merge($_GET, ['p' => $p]));
}
pagina_inicio('Animales', 'animales', $u);
?>
<header class="encabezado">
  <h1>Animales</h1>
  <p><?= $error ? 'Registro del hato' : e($total) . ' animal' . ($total === 1 ? '' : 'es') . ' encontrado' . ($total === 1 ? '' : 's') ?></p>
</header>
<?php if ($error): ?>
  <section class="aviso"><h1>No se pudo leer la base de datos</h1><pre><?= e($error) ?></pre></section>
<?php else: ?>
<form class="filtros" method="get" action="animales.php">
  <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar por nombre o IDICA" aria-label="Buscar">
  <select name="sexo" aria-label="Sexo">
    <option value="">Todos los sexos</option>
    <option value="HEMBRA" <?= $sexo === 'HEMBRA' ? 'selected' : '' ?>>Hembras</option>
    <option value="MACHO" <?= $sexo === 'MACHO' ? 'selected' : '' ?>>Machos</option>
  </select>
  <select name="estado" aria-label="Estado">
    <option value="">Todos los estados</option>
    <?php foreach ($estados as $e1): ?><option <?= $estado === $e1 ? 'selected' : '' ?>><?= e($e1) ?></option><?php endforeach; ?>
  </select>
  <select name="clas" aria-label="Clasificación">
    <option value="">Todas las clasificaciones</option>
    <?php foreach ($clases as $c1): ?><option <?= $clas === $c1 ? 'selected' : '' ?>><?= e($c1) ?></option><?php endforeach; ?>
  </select>
  <button type="submit">Filtrar</button>
  <?php if ($q . $sexo . $estado . $clas !== ''): ?><a class="limpiar" href="animales.php">Limpiar</a><?php endif; ?>
</form>

<div class="tabla-caja">
<table class="tabla">
  <thead><tr><th>Nombre</th><th>IDICA</th><th>Sexo</th><th>Raza</th><th>Clasificación</th><th>Edad</th><th>Peso</th><th>Estado</th></tr></thead>
  <tbody>
  <?php foreach ($filas as $f): ?>
    <tr>
      <td><a href="animal.php?id=<?= (int)$f['idregistro'] ?>"><?= e($f['nombre']) ?></a></td>
      <td><?= e($f['IDICA']) ?></td>
      <td><?= e(ucfirst(strtolower($f['sexo']))) ?></td>
      <td><?= e($f['raza']) ?></td>
      <td><?= e($f['clasificacion']) ?></td>
      <td><?= e($f['edad']) ?> años</td>
      <td><?= e(number_format((float)$f['peso'], 0, ',', '.')) ?> kg</td>
      <td><?= insignia($f['estado_animal']) ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$filas): ?><tr><td colspan="8" class="vacio">No hay animales con esos filtros.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>

<?php if ($paginas > 1): ?>
<nav class="pager" aria-label="Paginación">
  <?php if ($pag > 1): ?><a href="<?= e(enlace_pag($pag - 1)) ?>">← Anterior</a><?php endif; ?>
  <span>Página <?= $pag ?> de <?= $paginas ?></span>
  <?php if ($pag < $paginas): ?><a href="<?= e(enlace_pag($pag + 1)) ?>">Siguiente →</a><?php endif; ?>
</nav>
<?php endif; ?>
<?php endif; pagina_fin(); ?>
