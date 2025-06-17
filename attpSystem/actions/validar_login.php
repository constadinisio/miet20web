<?php
// Se inicia una sesión PHP
session_start();

// Incluye el archivo a la conexión a la base de datos.
include '../includes/conexion.php';

// Captura los datos del formulario
$mail = trim($_POST['mail'] ?? '');
$contrasena = trim($_POST['contrasena'] ?? '');

// Regenerar ID de sesión para seguridad
session_regenerate_id(true);

// Prepara una consulta SQL para buscar un usuario con un mail específico y estado activo (status = 1)
$stmt = $conexion->prepare("SELECT * FROM usuarios WHERE mail = ? AND status = 1 LIMIT 1");
// Asocia el valor de la variable $mail al marcador de posición de la consulta
$stmt->bind_param("s", $mail);
// Ejecuta la consulta preparada
$stmt->execute();
// Obtiene el resultado de la consulta en forma de conjunto de registros
$result = $stmt->get_result();

// Si se encontró un usuario con el mail ingresado
if ($usuario = $result->fetch_assoc()) {
    // Verifica que la contraseña ingresada coincida con la almacenada
    if ($contrasena === $usuario['contrasena']) {
        // Verifica que el rol del usuario sea 5 (autorizado para acceder)
        if ((int)$usuario['rol'] === 5) {
            // Guarda los datos del usuario en la sesión para uso posterior
            $_SESSION['usuario'] = [
                'id' => $usuario['id'],
                'nombre' => $usuario['nombre'],
                'apellido' => $usuario['apellido'],
                'rol' => $usuario['rol'],
                'foto_url' => $usuario['foto_url']
            ];
            // Redirige al usuario al panel principal
            header("Location: ../index.php");
            exit;
        } else {
            // Si el rol no es 5, redirige al login con un error de rol
            header("Location: ../login.php?error=rol");
            exit;
        }
    }
}

// Se ridirige a la página login.php con el estado de error en = 1.
header("Location: ../login.php?error=1");
exit;
?>