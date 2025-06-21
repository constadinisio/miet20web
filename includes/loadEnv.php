<?php
function cargarEntorno($ruta)
{
    if (!file_exists($ruta)) return;

    $lineas = file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lineas as $linea) {
        if (str_starts_with(trim($linea), '#') || !str_contains($linea, '=')) continue;

        list($clave, $valor) = explode('=', $linea, 2);
        $clave = trim($clave);
        $valor = trim($valor);

        $_ENV[$clave] = $valor;
        $_SERVER[$clave] = $valor;
        putenv("$clave=$valor");
    }
}
