<?php
$cfgClima = clima_config();
$diasSem = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
?>
<section class="clima tarjeta" aria-labelledby="t-clima">
  <h2 id="t-clima">Clima de la finca <small><?= e($cfgClima['nombre']) ?></small></h2>
<?php if (!$clima): ?>
  <p class="vacio">No se pudo obtener el clima en este momento. Se intentará de nuevo al recargar la página.</p>
<?php else:
  $c = $clima['current']; $d = $clima['daily'];
  $t = (float)$c['temperature_2m']; $hr = (float)$c['relative_humidity_2m'];
  $thi = indice_thi($t, $hr); [$nivel, $clase, $consejo] = thi_nivel($thi);
?>
  <div class="clima-fila">
    <div class="clima-ahora">
      <span class="grado"><?= round($t) ?>°</span>
      <span class="desc"><?= e(clima_texto((int)($c['weather_code'] ?? -1))) ?></span>
      <span class="det">Sensación <?= round((float)($c['apparent_temperature'] ?? $t)) ?>° · Humedad <?= round($hr) ?>% · Viento <?= round((float)($c['wind_speed_10m'] ?? 0)) ?> km/h</span>
    </div>
    <div class="clima-thi <?= e($clase) ?>">
      <b>Estrés por calor: <?= e($nivel) ?></b>
      <span>Índice THI <?= round($thi) ?></span>
      <p><?= e($consejo) ?></p>
    </div>
  </div>
  <div class="clima-dias">
    <?php foreach (array_slice($d['time'], 0, 5, true) as $i => $f):
      $ts = strtotime($f); $et = $i === 0 ? 'Hoy' : ($i === 1 ? 'Mañana' : $diasSem[(int)date('w', $ts)] . ' ' . date('j', $ts)); ?>
      <div class="dia">
        <b><?= e($et) ?></b>
        <span><?= e(clima_texto((int)($d['weather_code'][$i] ?? -1))) ?></span>
        <span class="temps"><?= round((float)$d['temperature_2m_max'][$i]) ?>° <small><?= round((float)$d['temperature_2m_min'][$i]) ?>°</small></span>
        <span class="lluvia"><?= isset($d['precipitation_probability_max'][$i]) ? (int)$d['precipitation_probability_max'][$i] . '% lluvia' : '' ?></span>
      </div>
    <?php endforeach; ?>
  </div>
  <p class="nota">Pronóstico de Open-Meteo. El nivel de estrés es una referencia para ganado lechero tipo Holstein; las razas cebuinas toleran más calor.<?= !empty($clima['obsoleto']) ? ' Sin conexión con el servicio: se muestran los últimos datos guardados.' : '' ?></p>
<?php endif; ?>
</section>
