<?php
require_once __DIR__ . '/../../../backend/includes/db.php';

session_start();
$fecha_desde = trim($_GET['fecha_desde'] ?? '');
$fecha_hasta = trim($_GET['fecha_hasta'] ?? '');
if (!isset($_SESSION['usuario'])) {
  header("Location: /login.php");
  exit;
}
$u = $_SESSION['usuario'];
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <title>ATTP - Logs</title>
  <link href="/output.css?v=<?= time() ?>" rel="stylesheet">
  <link rel="icon" type="image/x-icon" href="/images/et20png.png">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
  <div class="relative min-h-screen flex">
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
        <a href="prestamos.php" class="block py-2 px-4 hover:bg-blue-700 rounded">Préstamos</a>
        <a href="logs.php" class="block py-2 px-4 bg-blue-700 rounded">Logs</a>
      </nav>
      <div class="p-4 border-t border-blue-700">
        <?php if (isset($_SESSION['roles_disponibles']) && count($_SESSION['roles_disponibles']) > 1): ?>
          <form method="post" action="/includes/cambiar_rol.php" class="mt-auto mb-3">
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
          <button type="submit" class="w-full py-2 px-4 mt-4 bg-red-600 hover:bg-red-700 text-white rounded text-center">
            Cerrar sesión
          </button>
        </form>
      </div>
      <div class="p-6 mt-10 text-center text-gray-400"><button onclick="mostrarCreditos()">Créditos</button></div>
    </div>

    <!-- Contenido -->
    <main id="mainContent" class="w-full px-4 py-8 transition-all duration-300 container mx-auto">
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
      <div class="mb-4">
        <button id="toggleSidebar" class="text-2xl text-blue-800 bg-white p-2 rounded shadow">
          ☰</button>
      </div>

      <h1 class="text-3xl font-bold mb-6">Logs de Préstamos</h1>

      <!-- Filtro de fecha -->
      <form method="GET" class="mb-6 flex flex-col sm:flex-row flex-wrap gap-4 items-end">
        <div class="w-full sm:w-auto">
          <label class="block mb-1 font-semibold" for="fecha_desde">Fecha Desde (DD/MM/YYYY):</label>
          <input type="text" name="fecha_desde" id="fecha_desde" placeholder="01/06/2025" value="<?= htmlspecialchars($fecha_desde ?? '') ?>" class="border p-2 rounded w-full">
        </div>
        <div class="w-full sm:w-auto">
          <label class="block mb-1 font-semibold" for="fecha_hasta">Fecha Hasta (DD/MM/YYYY):</label>
          <input type="text" name="fecha_hasta" id="fecha_hasta" placeholder="13/06/2025" value="<?= htmlspecialchars($fecha_hasta ?? '') ?>" class="border p-2 rounded w-full">
        </div>
        <div class="flex gap-2">
          <button type="submit" class="bg-blue-700 text-white px-4 py-2 rounded">Filtrar</button>
          <a href="logs.php" class="bg-blue-700 text-white px-4 py-2 rounded">Limpiar filtro</a>
        </div>
      </form>

      <!-- Tabla logs -->
      <div class="overflow-x-auto rounded shadow bg-white">
        <table class="min-w-full">
          <thead class="bg-blue-800 text-white text-sm">
            <tr>
              <th class="py-2 px-4 whitespace-nowrap">ID</th>
              <th class="py-2 px-4 whitespace-nowrap">Netbook</th>
              <th class="py-2 px-4 whitespace-nowrap">Alumno</th>
              <th class="py-2 px-4 whitespace-nowrap">Curso</th>
              <th class="py-2 px-4 whitespace-nowrap">Tutor</th>
              <th class="py-2 px-4 whitespace-nowrap">Fecha Préstamo</th>
              <th class="py-2 px-4 whitespace-nowrap">Hora Préstamo</th>
              <th class="py-2 px-4 whitespace-nowrap">Fecha Devolución</th>
              <th class="py-2 px-4 whitespace-nowrap">Hora Devolución</th>
            </tr>
          </thead>
          <tbody class="text-gray-700 divide-y text-sm">
            <?php
            function convertDateToSQL($fecha)
            {
              $parts = explode('/', $fecha);
              return count($parts) === 3 ? "$parts[2]-$parts[1]-$parts[0]" : null;
            }

            $where = [];
            if (!empty($fecha_desde)) {
              $desde = convertDateToSQL($fecha_desde);
              if ($desde) $where[] = "STR_TO_DATE(Fecha_Prestamo, '%d/%m/%Y') >= '$desde'";
            }
            if (!empty($fecha_hasta)) {
              $hasta = convertDateToSQL($fecha_hasta);
              if ($hasta) $where[] = "STR_TO_DATE(Fecha_Prestamo, '%d/%m/%Y') <= '$hasta'";
            }

            $sql = "SELECT * FROM prestamos";
            if (count($where) > 0) $sql .= " WHERE " . implode(' AND ', $where);
            $sql .= " ORDER BY STR_TO_DATE(Fecha_Prestamo, '%d/%m/%Y') DESC, Hora_Prestamo DESC";

            $result = $conexion->query($sql);
            while ($row = $result->fetch_assoc()):
            ?>
              <tr>
                <td class="py-2 px-4"><?= $row['Prestamo_ID'] ?></td>
                <td class="py-2 px-4"><?= $row['Netbook_ID'] ?></td>
                <td class="py-2 px-4"><?= $row['Alumno'] ?></td>
                <td class="py-2 px-4"><?= $row['Curso'] ?></td>
                <td class="py-2 px-4"><?= $row['Tutor'] ?></td>
                <td class="py-2 px-4"><?= $row['Fecha_Prestamo'] ?></td>
                <td class="py-2 px-4"><?= $row['Hora_Prestamo'] ?></td>
                <td class="py-2 px-4"><?= $row['Fecha_Devolucion'] ?? '-' ?></td>
                <td class="py-2 px-4"><?= $row['Hora_Devolucion'] ?? '-' ?></td>
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