<?php
// Campos obligatorios base (todos los roles)
$CAMPOS_OBLIGATORIOS_COMUNES = [
    'nombre'           => 'Nombre',
    'apellido'         => 'Apellido',
    'mail'             => 'Correo electrónico',
    'dni'              => 'DNI',
    'telefono'         => 'Teléfono',
    'direccion'        => 'Dirección',
    'fecha_nacimiento' => 'Fecha de nacimiento',
];

// Campos obligatorios extra (solo NO alumnos)
$CAMPOS_OBLIGATORIOS_EXTRA = [
    'ficha_censal'     => 'Ficha censal',
];