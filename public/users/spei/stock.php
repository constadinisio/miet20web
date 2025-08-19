<?php
// Inicia la sesión del usuario
session_start();

// Verifica que el usuario esté logueado, que su sesión sea un array válido y que su rol sea 5 (rol autorizado)
if (
  !isset($_SESSION['usuario']) ||
  !is_array($_SESSION['usuario']) ||
  (int)$_SESSION['usuario']['rol'] !== 5
) {
  // Si no cumple las condiciones, redirige al login con un error de rol
  header("Location: /login.php?error=rol");
  exit;
}

require_once __DIR__ . '/../../../backend/includes/db.php';

if (!isset($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf'];
$usuario = $_SESSION['usuario'];

$query = "SELECT id, carrito, numero, numero_serie, fecha_adquisicion, estado, observaciones FROM netbooks ORDER BY carrito, numero";
$result = $conexion->query($query);

$estados = ['En uso', 'Dañada', 'Hurto', 'Obsoleta'];

if (!$result) {
  die("Error en la consulta SQL: " . $conexion->error);
}

$sql_disponibles = "
  SELECT COUNT(*) AS cantidad
  FROM netbooks n
  WHERE n.estado = 'En uso'
  AND NOT EXISTS (
    SELECT 1 FROM prestamos p 
    WHERE p.Netbook_ID = CONCAT(n.carrito, n.numero)
    AND p.Fecha_Devolucion IS NULL
  )
";
$res_disponibles = $conexion->query($sql_disponibles);
$disponibles = $res_disponibles->fetch_assoc()['cantidad'] ?? 0;

$sql_no_disponibles = "
  SELECT COUNT(*) AS cantidad 
  FROM netbooks
  WHERE estado IN ('Hurto', 'Obsoleta', 'Dañada')
";
$res_no_disp = $conexion->query($sql_no_disponibles);
$no_disponibles = $res_no_disp->fetch_assoc()['cantidad'] ?? 0;

$sql_prestamos_curso = "
  SELECT COUNT(*) AS cantidad
  FROM prestamos
  WHERE Fecha_Devolucion IS NULL OR Fecha_Devolucion = ''
";
$res_prestamos = $conexion->query($sql_prestamos_curso);
$prestamos_curso = $res_prestamos->fetch_assoc()['cantidad'] ?? 0;
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ATTP - Stock</title>
  <link rel="stylesheet" href="/output.css">
  <link rel="icon" type="image/x-icon" href="/images/et20png.png">
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
    <a href="stock.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-900 font-semibold hover:bg-gray-200 transition" title="Stock">
      <span class="text-xl">📂</span><span class="sidebar-label">Stock</span>
    </a>
    <a href="prestamos.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100 transition" title="Prestamos">
      <span class="text-xl">📑</span><span class="sidebar-label">Prestamos</span>
    </a>
    <a href="logs.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100 transition" title="Logs">
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
                    <div class="mt-1 text-xs text-gray-500">SPEI</div>
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

    <h1 class="text-3xl font-bold mb-6">Gestión de Netbooks</h1>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 p-4">
      <div class="bg-white rounded shadow p-6">
        <h2 class="text-xl font-semibold mb-2">Computadoras disponibles</h2>
        <p class="text-5xl font-bold text-green-600"><?= $disponibles ?></p>
      </div>
      <div class="bg-white rounded shadow p-6">
        <h2 class="text-xl font-semibold mb-2">Computadoras no disponibles</h2>
        <p class="text-5xl font-bold text-red-600"><?= $no_disponibles ?></p>
      </div>
      <div class="bg-white rounded shadow p-6">
        <h2 class="text-xl font-semibold mb-2">Préstamos en curso</h2>
        <p class="text-5xl font-bold text-yellow-600"><?= $prestamos_curso ?></p>
      </div>
    </div>

    <form action="agregar_netbook.php" method="POST" class="bg-white p-4 rounded shadow mb-6 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
      <input type="hidden" name="csrf" value="<?= $csrf ?>">
      <input name="carrito" required placeholder="Carrito (A-Z)" class="border p-2 rounded">
      <input name="numero" required placeholder="Número (01-30)" class="border p-2 rounded">
      <input name="numero_serie" required placeholder="Número de Serie" class="border p-2 rounded">
      <input name="fecha_adquisicion" placeholder="Fecha Adquisición (DD/MM/AAAA)" class="border p-2 rounded">
      <select name="estado" class="border p-2 rounded">
        <option value="En uso">En uso</option>
        <option value="Dañada">Dañada</option>
        <option value="Hurto">Hurto</option>
        <option value="Obsoleta">Obsoleta</option>
      </select>
      <textarea name="observaciones" placeholder="Observaciones" class="border p-2 rounded col-span-full"></textarea>
      <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded">Añadir Netbook</button>
    </form>

    <div class="mb-4">
      <input id="searchInput" type="text" placeholder="Buscar netbook por carrito, número, serie, estado..." class="w-full p-2 border rounded" onkeyup="filtrarTabla()" />
    </div>

    <div class="overflow-x-auto w-full">
      <table class="min-w-full bg-white shadow rounded text-sm">
        <thead class="sticky top-0 bg-gray-50 z-10">
          <tr>
            <th class="py-2 px-4 border-b">Carrito</th>
            <th class="py-2 px-4 border-b">Número</th>
            <th class="py-2 px-4 border-b">Número de Serie</th>
            <th class="py-2 px-4 border-b">Fecha Adquisición</th>
            <th class="py-2 px-4 border-b">Estado</th>
            <th class="py-2 px-4 border-b">Observaciones</th>
            <th class="py-2 px-4 border-b">Acción</th>
          </tr>
        </thead>
        <tbody id="tablaNetbooks">
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr class="hover:bg-gray-100">
              <td class="py-2 px-4 border-b text-center"><?= htmlspecialchars($row['carrito']) ?></td>
              <td class="py-2 px-4 border-b text-center"><?= htmlspecialchars($row['numero']) ?></td>
              <td class="py-2 px-4 border-b text-center"><?= htmlspecialchars($row['numero_serie']) ?></td>
              <td class="py-2 px-4 border-b text-center"><?= htmlspecialchars($row['fecha_adquisicion']) ?></td>
              <td class="py-2 px-4 border-b text-center">
                <select onchange="actualizarEstado(this, <?= $row['id'] ?>)" class="border rounded px-2 py-1 text-center">
                  <?php foreach ($estados as $estado): ?>
                    <option value="<?= $estado ?>" <?= ($row['estado'] === $estado) ? 'selected' : '' ?>>
                      <?= $estado ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </td>
              <td class="py-2 px-4">
                <form action="editar_observacion.php" method="POST" class="flex">
                  <input type="hidden" name="csrf" value="<?= $csrf ?>">
                  <input type="hidden" name="id" value="<?= $row['id'] ?>">
                  <input name="observaciones" value="<?= htmlspecialchars($row['observaciones']) ?>" class="border p-1 rounded w-full text-sm">
                  <button class="ml-2 px-2 bg-yellow-500 text-white text-sm rounded">💾</button>
                </form>
              </td>
              <td class="py-2 px-4">
                <a href="eliminar_netbook.php?id=<?= $row['id'] ?>" onclick="return confirm('¿Eliminar esta netbook?')" class="bg-red-600 hover:bg-red-700 text-white px-2 py-1 rounded text-sm">Eliminar</a>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>

    <script>
      function filtrarTabla() {
        const input = document.getElementById("searchInput");
        const filter = input.value.toLowerCase();
        const tabla = document.getElementById("tablaNetbooks");
        const filas = tabla.getElementsByTagName("tr");

        for (let i = 0; i < filas.length; i++) {
          const fila = filas[i];
          const celdas = fila.getElementsByTagName("td");
          let textoFila = "";

          for (let j = 0; j < celdas.length; j++) {
            textoFila += celdas[j].textContent.toLowerCase() + " ";
          }

          fila.style.display = textoFila.indexOf(filter) > -1 ? "" : "none";
        }
      }

      function actualizarEstado(selectElement, id) {
        const nuevoEstado = selectElement.value;
        fetch('update_estado.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'id=' + encodeURIComponent(id) + '&estado=' + encodeURIComponent(nuevoEstado)
          })
          .then(response => response.text())
          .then(data => {
            if (data !== 'OK') {
              alert('Error al actualizar el estado: ' + data);
            }
          })
          .catch(error => {
            alert('Error en la conexión: ' + error);
          });
      }
    </script>
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