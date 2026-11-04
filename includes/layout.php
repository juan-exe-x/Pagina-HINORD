<?php
// Consulta con parámetros (consultas preparadas).
function qp(string $sql, array $params = []): array {
    $st = db()->prepare($sql); $st->execute($params); return $st->fetchAll();
}
function fecha($v): string { return $v ? date('d/m/Y', strtotime($v)) : '—'; }
function insignia(string $estado): string {
    $c = ['Activo' => 'ok', 'Enfermo' => 'aviso', 'Muerto' => 'off', 'Vendido' => 'off'][$estado] ?? 'off';
    return '<span class="chip ' . $c . '">' . e($estado) . '</span>';
}

function pagina_inicio(string $titulo, string $activo, array $u): void { ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($titulo) ?> - HINORD</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/dashboard.css">
  <link rel="stylesheet" href="css/animales.css">
</head>
<body>
<div class="app">
<aside class="lateral">
  <a class="marca" href="index.html">HIN<span>ORD</span></a>
  <nav>
    <a class="<?= $activo === 'panel' ? 'activo' : '' ?>" href="dashboard.php">Panel general</a>
    <a class="<?= $activo === 'animales' ? 'activo' : '' ?>" href="animales.php">Animales</a>
    <span class="pronto">Estadísticas</span>
  </nav>
  <div class="sesion"><span><?= e($u['nombre']) ?></span><a href="logout.php">Cerrar sesión</a></div>
</aside>
<main>
<?php }

function pagina_fin(): void { echo "</main>\n</div>\n</body>\n</html>\n"; }
