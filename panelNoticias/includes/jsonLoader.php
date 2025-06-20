<?php
define("JSON_PATH", __DIR__ . '../../data/noticias.json');

function cargarNoticias() {
    if (!file_exists(JSON_PATH)) {
        file_put_contents(JSON_PATH, json_encode([]));
    }
    $contenido = file_get_contents(JSON_PATH);
    return json_decode($contenido, true);
    
}
function guardarNoticias($noticias) {
    file_put_contents(JSON_PATH, json_encode($noticias, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
?>