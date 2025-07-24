<?php
require_once __DIR__ . '/../../../backend/includes/db.php';

session_start();
if (!isset($_SESSION['usuario'])) {
  header("Location: /login.php");
  exit;
}

if (!isset($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf'];

$u = $_SESSION['usuario'];
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ATTP - Prestamos</title>
  <link rel="stylesheet" href="/output.css">
  <link rel="icon" type="image/x-icon" href="../images/et20png.png">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <!-- Font Awesome CDN -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <style>
    body {
      font-family: 'Poppins', sans-serif;
    }

    img {
      width: 50px;
      height: 50px;
    }
  </style>
</head>

<body class="bg-gray-100">
  <div class="flex flex-col md:flex-row min-h-screen">
    <!-- Sidebar -->
    <div id="sidebar" class="absolute top-0 left-0 w-64 bg-blue-800 text-white min-h-screen z-50 transform -translate-x-full transition-transform duration-300">
      <div class="flex justify-between items-center p-4 border-b border-blue-700">
        <a href="#" class="flex items-center text-xl font-bold">
          <img src="/images/et20ico.ico" class="mr-2">
          Panel SPEI
        </a>
      </div>

      <!-- Perfil del usuario -->
      <div class="p-6 text-center border-b border-blue-700">
        <img src="<?php echo $u['foto_url'] ?? 'https://ui-avatars.com/api/?name=' . $u['nombre']; ?>" class="block mx-auto rounded-full w-14 h-14">
        <h2 class="text-lg font-semibold"><?php echo $u['nombre'] . ' ' . $u['apellido']; ?></h2>
        <p class="text-sm text-blue-200">SPEI</p>
        <button id="btn-notificaciones" class="relative focus:outline-none group mt-4">
          <!-- Campanita Font Awesome -->
          <i id="icono-campana" class="fa-regular fa-bell text-2xl text-gray-400 group-hover:text-gray-700 transition-colors"></i>
          <!-- Badge cantidad (oculto si no hay notificaciones) -->
          <span id="badge-notificaciones"
            class="absolute -top-1 -right-1 bg-red-600 text-white text-xs rounded-full px-1 hidden border border-white font-bold"
            style="min-width:1.2em; text-align:center;"></span>
        </button>
      </div>

      <!-- Menú -->
      <nav class="p-4 space-y-2">
        <a href="index.php" class="block py-2 px-4 hover:bg-blue-700 rounded">Página Principal</a>
        <a href="stock.php" class="block py-2 px-4 hover:bg-blue-700 rounded">Stock</a>
        <a href="prestamos.php" class="block py-2 px-4 bg-blue-700 rounded">Préstamos</a>
        <a href="logs.php" class="block py-2 px-4 hover:bg-blue-700 rounded">Logs</a>
      </nav>
      <div class="p-4 border-t border-blue-700">
        <?php if (isset($_SESSION['roles_disponibles']) && count($_SESSION['roles_disponibles']) > 1): ?>
          <form method="post" action="/includes/cambiar_rol.php" class="mt-auto mb-3">
            <input type="hidden" name="csrf" value="<?= $csrf ?>">
            <select name="rol" onchange="this.form.submit()" class="w-full px-3 py-2 border text-sm rounded-xl text-gray-700 bg-white">
              <?php foreach ($_SESSION['roles_disponibles'] as $r): ?>
                <option value="<?php echo $r['id']; ?>" <?php if ($_SESSION['usuario']['rol'] == $r['id']) echo 'selected'; ?>>
                  Cambiar a: <?php echo ucfirst($r['nombre']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </form>
        <?php endif; ?>
        <form action="/includes/logout.php" method="POST">
          <input type="hidden" name="csrf" value="<?= $csrf ?>">
          <button type="submit" class="w-full py-2 px-4 mt-4 bg-red-600 hover:bg-red-700 text-white rounded text-center">
            Cerrar sesión
          </button>
        </form>
      </div>
      <div class="p-6 mt-10 text-center text-gray-400"><button onclick="mostrarCreditos()">Créditos</button></div>
    </div>

    <!-- Contenido -->
    <main id="mainContent" class="w-full p-4 md:p-8 transition-all duration-300">
      <!-- POPUP DE NOTIFICACIONES -->
      <div id="popup-notificaciones" class="hidden fixed right-4 top-16 w-80 max-h-[70vh] bg-white shadow-2xl rounded-2xl border border-gray-200 z-50 flex flex-col">
        <div class="flex items-center justify-between px-4 py-3 border-b">
          <span class="font-bold text-gray-800 text-lg">Notificaciones</span>
          <button onclick="cerrarPopup()" class="text-gray-400 hover:text-red-400 text-xl">&times;</button>
        </div>
        <div id="lista-notificaciones" class="overflow-y-auto p-2">
          <!-- Notificaciones aquí -->
        </div>
      </div>
      <!-- Botón hamburguesa -->
      <div class="mb-4 md">
        <button id="toggleSidebar" class="text-2xl text-blue-800 bg-white p-2 rounded shadow">
          ☰
        </button>
      </div>

      <h1 class="text-3xl font-bold mb-6">Gestión de Préstamos</h1>

      <!-- Formulario para agregar préstamo -->
      <form action="agregar_prestamo.php" method="POST" class="bg-white p-4 rounded shadow mb-6 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
        <input type="hidden" name="csrf" value="<?= $csrf ?>">
        <input name="Netbook_ID" required placeholder="Netbook ID (ej: A1)" class="border p-2 rounded">
        <input name="Alumno" required placeholder="Alumno" class="border p-2 rounded">
        <input name="Curso" required placeholder="Curso (ej: 3°4°)" class="border p-2 rounded">
        <input name="Tutor" required placeholder="Tutor" class="border p-2 rounded">
        <input name="Fecha_Prestamo" required placeholder="Fecha (DD/MM/AAAA)" class="border p-2 rounded">
        <input name="Hora_Prestamo" required placeholder="Hora (HH:MM)" class="border p-2 rounded">
        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded">Registrar Préstamo</button>
      </form>

      <!-- Tabla de préstamos -->
      <div class="overflow-x-auto">
        <table class="min-w-full bg-white shadow rounded-lg text-sm">
          <thead class="bg-blue-800 text-white">
            <tr>
              <th class="py-2 px-4 text-left">ID</th>
              <th class="py-2 px-4">Netbook</th>
              <th class="py-2 px-4">Alumno</th>
              <th class="py-2 px-4">Curso</th>
              <th class="py-2 px-4">Fecha</th>
              <th class="py-2 px-4">Hora</th>
              <th class="py-2 px-4">Tutor</th>
              <th class="py-2 px-4">Acciones</th>
            </tr>
          </thead>
          <tbody class="text-gray-700 divide-y">
            <?php
            $result = $conexion->query("SELECT * FROM prestamos WHERE Fecha_Devolucion IS NULL ORDER BY Prestamo_ID DESC");
            while ($row = $result->fetch_assoc()):
            ?>
              <tr>
                <td class="py-2 px-4 text-center"><?= $row['Prestamo_ID'] ?></td>
                <td class="py-2 px-4 text-center"><?= $row['Netbook_ID'] ?></td>
                <td class="py-2 px-4 text-center"><?= $row['Alumno'] ?></td>
                <td class="py-2 px-4 text-center"><?= $row['Curso'] ?></td>
                <td class="py-2 px-4 text-center"><?= $row['Fecha_Prestamo'] ?></td>
                <td class="py-2 px-4 text-center"><?= $row['Hora_Prestamo'] ?></td>
                <td class="py-2 px-4 text-center"><?= $row['Tutor'] ?></td>
                <td class="py-2 px-4 text-center space-y-1 md:space-x-2 md:space-y-0 flex flex-col md:flex-row justify-center">
                  <a href="devolver_prestamo.php?id=<?= $row['Prestamo_ID'] ?>" class="bg-green-600 text-white px-2 py-1 rounded text-sm">Devolver</a>
                  <a href="eliminar_prestamo.php?id=<?= $row['Prestamo_ID'] ?>" onclick="return confirm('¿Eliminar préstamo?')" class="bg-red-600 text-white px-2 py-1 rounded text-sm">Eliminar</a>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>

  <script>
    const sidebar = document.getElementById("sidebar");
    const toggleSidebar = document.getElementById("toggleSidebar");
    const closeSidebar = document.getElementById("closeSidebar");
    const mainContent = document.getElementById("mainContent");

    function toggleSidebarVisible() {
      const visible = !sidebar.classList.contains("-translate-x-full");
      if (visible) {
        sidebar.classList.add("-translate-x-full");
        mainContent.classList.remove("ml-64");
      } else {
        sidebar.classList.remove("-translate-x-full");
        mainContent.classList.add("ml-64");
      }
    }

    toggleSidebar.addEventListener("click", toggleSidebarVisible);
    closeSidebar.addEventListener("click", toggleSidebarVisible);
  </script>
  <script>
    document.getElementById('btn-notificaciones').addEventListener('click', function() {
      const popup = document.getElementById('popup-notificaciones');
      popup.classList.toggle('hidden');
      cargarNotificaciones();
    });

    function cerrarPopup() {
      document.getElementById('popup-notificaciones').classList.add('hidden');
    }

    function marcarLeida(destinatarioId) {
      fetch('/../../../includes/notificaciones/marcar_leida.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: 'id=' + encodeURIComponent(destinatarioId)
        }).then(res => res.json())
        .then(data => {
          if (data.ok) cargarNotificaciones();
        });
    }

    function confirmar(destinatarioId) {
      fetch('/../../../includes/notificaciones/confirmar.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: 'id=' + encodeURIComponent(destinatarioId)
        }).then(res => res.json())
        .then(data => {
          if (data.ok) cargarNotificaciones();
        });
    }

    function cargarNotificaciones() {
      fetch('/../../../includes/notificaciones/listar.php')
        .then(res => res.json())
        .then(data => {
          const lista = document.getElementById('lista-notificaciones');
          const badge = document.getElementById('badge-notificaciones');
          const campana = document.getElementById('icono-campana');
          lista.innerHTML = '';
          let sinLeer = 0;
          if (data.length === 0) {
            lista.innerHTML = '<div class="text-center text-gray-400 p-4">Sin notificaciones nuevas.</div>';
            badge.classList.add('hidden');
            // Ícono gris claro, sin detalles rojos
            campana.classList.remove('text-red-500');
            campana.classList.add('text-gray-400');
            campana.classList.remove('fa-shake');
          } else {
            data.forEach(n => {
              if (n.estado_lectura === 'NO_LEIDA') sinLeer++;
              lista.innerHTML += `
                                <div class="rounded-xl px-3 py-2 mb-2 bg-gray-100 shadow hover:bg-gray-50 flex flex-col">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-base font-semibold">${n.titulo}</span>
                                    <span class="ml-auto text-xs">${n.fecha_creacion}</span>
                                </div>
                                <div class="text-sm text-gray-700 mb-2">${n.contenido}</div>
                                <div class="flex gap-2">
                                    ${n.estado_lectura === 'NO_LEIDA' ? `<button class="text-blue-600 text-xs" onclick="marcarLeida(${n.destinatario_row_id})">Marcar como leída</button>` : ''}
                                    ${(n.requiere_confirmacion == 1 && n.estado_lectura !== 'CONFIRMADA') ? `<button class="text-green-600 text-xs" onclick="confirmar(${n.destinatario_row_id})">Confirmar</button>` : ''}
                                    ${n.estado_lectura === 'LEIDA' ? '<span class="text-green-700 text-xs">Leída</span>' : ''}
                                    ${n.estado_lectura === 'CONFIRMADA' ? '<span class="text-green-700 text-xs">Confirmada</span>' : ''}
                                </div>
                                </div>`;
            });

            if (sinLeer > 0) {
              badge.textContent = sinLeer;
              badge.classList.remove('hidden');
              // Ícono gris pero con detalle rojo (y/o animación, opcional)
              campana.classList.remove('text-gray-400');
              campana.classList.add('text-red-500');
              campana.classList.add('fa-shake'); // animación de FA, opcional
            } else {
              badge.classList.add('hidden');
              campana.classList.remove('text-red-500');
              campana.classList.add('text-gray-400');
              campana.classList.remove('fa-shake');
            }
          }
        });
    }
    document.addEventListener('DOMContentLoaded', function() {
      cargarNotificaciones(); // Esto chequea notificaciones ni bien se carga la página
      setInterval(cargarNotificaciones, 15000);
    });
  </script>
  <!-- Modal Créditos -->
  <div id="popupCreditos" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden transition-shadow 0.3s">
    <div class="bg-white rounded-lg shadow-lg p-6 max-w-md text-center relative">
      <h2 class="text-2xl font-bold mb-4">Créditos</h2>
      <p class="text-gray-700 mb-4">
        Panel de Noticias desarrollado por el equipo de ATTP:<br>
        👨‍💻 Desarrolladores:
        <br>- Liz Vera
        - Uma Perez
        - Michael Martinez
        - Jaco Alfaro
        - Kevin Mamani
        - Brenda Huanca
        <br>🤗 Colaboradores:
        <br>- Fabricio Toscano
        <br>👨‍🏫 Profesores:
        <br>- Nicolas Bogarin
      </p>
      <button onclick="cerrarCreditos()" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
        Cerrar
      </button>
    </div>
  </div>
  <!-- Función de Mostrar/Ocultar el Popup de Créditos -->
  <script>
    function mostrarCreditos() {
      document.getElementById('popupCreditos').classList.remove('hidden');
    }

    function cerrarCreditos() {
      document.getElementById('popupCreditos').classList.add('hidden');
    }
  </script>

</body>

</html>