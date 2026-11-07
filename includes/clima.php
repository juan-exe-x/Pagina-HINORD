<?php
// Clima de la finca con Open-Meteo (https://open-meteo.com). Sin clave; gratis para uso no comercial.
// Para cambiar la ubicación, agrega en config/config.php:  'finca' => ['nombre' => 'Mi finca', 'lat' => 5.2056, 'lon' => -74.7358],
function clima_config(): array {
    $c = @include __DIR__ . '/../config/config.php';
    $f = is_array($c) && isset($c['finca']) && is_array($c['finca']) ? $c['finca'] : [];
    return $f + ['nombre' => 'Honda, Tolima', 'lat' => 5.2056, 'lon' => -74.7358];
}

function clima_http(string $url): ?string {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 4, CURLOPT_CONNECTTIMEOUT => 3, CURLOPT_USERAGENT => 'HINORD/1.0']);
        $r = curl_exec($ch);
        return ($r !== false && curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200) ? $r : null;
    }
    $r = @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 4, 'user_agent' => 'HINORD/1.0']]));
    return $r === false ? null : $r;
}

// Devuelve los datos del clima (con caché de 30 minutos) o null si no hay forma de obtenerlos.
function clima_obtener(): ?array {
    $cfg = clima_config();
    $dir = __DIR__ . '/../cache';
    $file = $dir . '/clima.json';
    $leer = function () use ($file) { $d = is_file($file) ? json_decode((string)file_get_contents($file), true) : null; return is_array($d) ? $d : null; };
    if (is_file($file) && time() - filemtime($file) < 1800 && ($d = $leer())) return $d + ['obsoleto' => false];

    $url = 'https://api.open-meteo.com/v1/forecast?' . http_build_query([
        'latitude' => $cfg['lat'], 'longitude' => $cfg['lon'], 'timezone' => 'America/Bogota', 'forecast_days' => 5,
        'current' => 'temperature_2m,relative_humidity_2m,apparent_temperature,precipitation,weather_code,wind_speed_10m',
        'daily' => 'weather_code,temperature_2m_max,temperature_2m_min,precipitation_sum,precipitation_probability_max',
    ]);
    $json = clima_http($url);
    $d = $json ? json_decode($json, true) : null;
    if (is_array($d) && isset($d['current']['temperature_2m'], $d['current']['relative_humidity_2m'], $d['daily']['time'])) {
        @mkdir($dir, 0775, true);
        if (@file_put_contents($file . '.tmp', $json) !== false) @rename($file . '.tmp', $file);
        return $d + ['obsoleto' => false];
    }
    $d = $leer();                                   // sin conexión: usa lo último guardado, avisando
    return $d ? $d + ['obsoleto' => true] : null;
}

function clima_texto(int $c): string {
    if ($c === 0) return 'Despejado';
    if ($c <= 3) return $c === 1 ? 'Mayormente despejado' : ($c === 2 ? 'Parcialmente nublado' : 'Nublado');
    if ($c === 45 || $c === 48) return 'Niebla';
    if ($c >= 51 && $c <= 57) return 'Llovizna';
    if ($c >= 61 && $c <= 67) return 'Lluvia';
    if ($c >= 80 && $c <= 82) return 'Aguaceros';
    if ($c >= 95) return 'Tormenta eléctrica';
    return 'Variable';
}

// Índice de temperatura y humedad (THI) para ganado lechero.
function indice_thi(float $t, float $hr): float { return (1.8 * $t + 32) - (0.55 - 0.0055 * $hr) * (1.8 * $t - 26); }
function thi_nivel(float $thi): array {
    if ($thi < 72) return ['Sin estrés', 'ok', 'Condiciones cómodas para el ganado.'];
    if ($thi < 79) return ['Alerta', 'aviso', 'Estrés leve a moderado: asegura agua fresca y sombra.'];
    if ($thi < 89) return ['Peligro', 'peligro', 'Estrés severo: sombra y agua abundante, y evita mover o trabajar el ganado en las horas más calurosas.'];
    return ['Emergencia', 'emergencia', 'Riesgo grave por calor: sombra, agua y ventilación de inmediato, y no muevas a los animales.'];
}
