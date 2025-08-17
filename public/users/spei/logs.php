<?php
require_once __DIR__ . '/../../../backend/includes/db.php';

session_start();

if (!isset($_SESSION['usuario'])) {
  header("Location: /login.php");
  exit;
}

$usuario = $_SESSION['usuario'];

$fecha_desde = trim($_GET['fecha_desde'] ?? '');
$fecha_hasta = trim($_GET['fecha_hasta'] ?? '');

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

    .sidebar-item {
      min-height: 3.5rem;
      width: 100%;
    }

    .w-16 .sidebar-item {
      justify-content: center !important;
    }

    .w-16 .sidebar-item span.sidebar-label {
      display: none;
    }

    .w-16 .sidebar-item span.text-xl {
      margin: 0 auto;
    }
  </style>
</head>

<body class="bg-gray-100 min-h-screen flex relative">
  <!-- Sidebar -->
  <nav id="sidebar" class="w-60 transition-all duration-300 bg-white shadow-lg px-4 py-4 flex flex-col gap-2">
    <button id="toggleSidebar" class="absolute top-4 left-4 z-50 text-2xl hover:text-indigo-600 transition">
            ☰
        </button>
    <div class="flex justify-center items-center p-2 mb-4 border-b border-gray-400 h-28">
      <img src="/images/et20ico.ico" class="sidebar-expanded block h-full w-auto object-contain">
      <img src="/images/et20ico.ico" class="sidebar-collapsed hidden h-10 w-auto object-contain">
    </div>
    <a href="index.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100 transition" title="Inicio">
      <span class="text-xl">🏠</span><span class="sidebar-label">Inicio</span>
    </a>
    <a href="stock.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100 transition" title="Stock">
      <span class="text-xl">📂</span><span class="sidebar-label">Stock</span>
    </a>
    <a href="prestamos.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100 transition" title="Prestamos">
      <span class="text-xl">📑</span><span class="sidebar-label">Prestamos</span>
    </a>
    <a href="logs.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-900 font-semibold hover:bg-gray-200 transition" title="Logs">
      <span class="text-xl">📝</span><span class="sidebar-label">Logs</span>
    </a>
    <button onclick="window.location='/includes/logout.php'" class="sidebar-item flex items-center justify-center gap-2 mt-auto py-2 px-3 rounded-xl text-white bg-red-500 hover:bg-red-600">
      <span class="text-xl">🚪</span><span class="sidebar-label">Salir</span>
    </button>
  </nav>

  <!-- Contenido principal -->
    <main class="flex-1 p-10">
        <!-- BLOQUE DE USUARIO, ROL, CONFIGURACIÓN Y NOTIFICACIONES -->
        <div class="w-full flex justify-end mb-6">
            <div class="flex items-center gap-3 bg-white rounded-xl px-5 py-2 shadow border">

                <!-- Avatar -->
                <img src="<?php echo $usuario['foto_url'] ?? 'https://ui-avatars.com/api/?name=' . $usuario['nombre']; ?>"
                    class="rounded-full w-12 h-12 object-cover">

                <!-- Nombre y rol -->
                <div class="flex flex-col pr-2 text-right">
                    <div class="font-bold text-base leading-tight"><?php echo $usuario['nombre']; ?></div>
                    <div class="font-bold text-base leading-tight"><?php echo $usuario['apellido']; ?></div>
                    <div class="mt-1 text-xs text-gray-500">Alumno/a</div>
                </div>

                <!-- Selector de rol (si corresponde) -->
                <?php if (isset($_SESSION['roles_disponibles']) && count($_SESSION['roles_disponibles']) > 1): ?>
                    <form method="post" action="/includes/cambiar_rol.php" class="ml-4">
                        <input type="hidden" name="csrf" value="<?= $csrf ?>">
                        <select name="rol" onchange="this.form.submit()"
                            class="px-2 py-1 border text-sm rounded-xl text-gray-700 bg-white">
                            <?php foreach ($_SESSION['roles_disponibles'] as $r): ?>
                                <option value="<?php echo $r['id']; ?>"
                                    <?php if ($_SESSION['usuario']['rol'] == $r['id']) echo 'selected'; ?>>
                                    Cambiar a: <?php echo ucfirst($r['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                <?php endif; ?>

                <!-- Botón de Configuración -->
                <a href="configuracion.php"
                    class="relative focus:outline-none group ml-2">
                    <i class="fa-solid fa-gear text-2xl text-gray-500 group-hover:text-gray-700 transition-colors"></i>
                </a>

                <!-- Notificaciones -->
                <button id="btn-notificaciones" class="relative focus:outline-none group ml-2">
                    <i id="icono-campana" class="fa-regular fa-bell text-2xl text-gray-400 group-hover:text-gray-700 transition-colors"></i>
                    <span id="badge-notificaciones"
                        class="absolute -top-1 -right-1 bg-red-600 text-white text-xs rounded-full px-1 hidden border border-white font-bold"
                        style="min-width:1.2em; text-align:center;"></span>
                </button>
            </div>
        </div>

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
    document.getElementById('toggleSidebar').addEventListener('click', function() {
      const sidebar = document.getElementById('sidebar');
      const labels = sidebar.querySelectorAll('.sidebar-label');
      const expandedElements = sidebar.querySelectorAll('.sidebar-expanded');
      const collapsedElements = sidebar.querySelectorAll('.sidebar-collapsed');

      if (sidebar.classList.contains('w-60')) {
        sidebar.classList.remove('w-60');
        sidebar.classList.add('w-16');
        labels.forEach(label => label.classList.add('hidden'));
        expandedElements.forEach(el => el.classList.add('hidden'));
        collapsedElements.forEach(el => el.classList.remove('hidden'));
      } else {
        sidebar.classList.remove('w-16');
        sidebar.classList.add('w-60');
        labels.forEach(label => label.classList.remove('hidden'));
        expandedElements.forEach(el => el.classList.remove('hidden'));
        collapsedElements.forEach(el => el.classList.add('hidden'));
      }
    });
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