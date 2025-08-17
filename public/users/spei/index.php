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

if (!isset($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf'];

// Guarda los datos del usuario logueado en una variable $u
$usuario = $_SESSION['usuario'];

// Incluye la conexión a la base de datos
require_once __DIR__ . '/../../../backend/includes/db.php';

$mostrar_modal = ($usuario['rol'] != 0 && $usuario['rol'] != 4 && empty($usuario['ficha_censal']));

/* ======================== BLOQUES DE CONSULTAS ======================== */

// 1. Consulta cuántas computadoras están "En uso" y **no están prestadas actualmente**
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

// 2. Consulta cuántas computadoras están en estado "Hurto" o "Obsoleta"
$sql_no_disponibles = "
  SELECT COUNT(*) AS cantidad 
  FROM netbooks
  WHERE estado IN ('Hurto', 'Obsoleta')
";
$res_no_disp = $conexion->query($sql_no_disponibles);
$no_disponibles = $res_no_disp->fetch_assoc()['cantidad'] ?? 0;

// 3. Consulta cuántos préstamos siguen activos (sin fecha de devolución)
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
  <meta charset="UTF-8" />
  <title>SPEI - Página Principal</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="/output.css?v=<?= time() ?>" rel="stylesheet">
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
    <a href="index.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-900 font-semibold hover:bg-gray-200 transition" title="Inicio">
      <span class="text-xl">🏠</span><span class="sidebar-label">Inicio</span>
    </a>
    <a href="stock.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100 transition" title="Stock">
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

    <h1 class="text-3xl font-bold mb-6">Panel Principal</h1>
    <section class="p-4">
      <div class="bg-white shadow rounded-xl p-6 max-w-3xl mx-auto">
        <h2 class="text-xl font-bold mb-4">📝 Pizarrón de SPEI</h2>

        <!-- Formulario para nueva nota -->
        <form id="formNota" class="mb-4">
          <input type="hidden" name="csrf" value="<?= $csrf ?>">
          <div class="flex flex-col gap-2">
            <textarea id="mensaje" name="mensaje" placeholder="Escribí una nota..." rows="3" class="border p-2 rounded"></textarea>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded w-fit">
              Agregar nota
            </button>
          </div>
        </form>

        <!-- Lista de notas -->
        <div id="listaNotas" class="space-y-4 max-h-[300px] overflow-y-auto pr-2">
          <!-- Se cargan dinámicamente -->
        </div>
      </div>
    </section>
  </main>
  </div>

  <!-- Crea las funciones Cargar/Editar/Eliminar del Panel de Noticias -->
  <script>
    document.addEventListener("DOMContentLoaded", function() {
      const lista = document.getElementById("listaNotas");
      const form = document.getElementById("formNota");

      function cargarNotas() {
        fetch("obtenerNotas.php")
          .then(res => res.json())
          .then(data => {
            lista.innerHTML = "";
            data.forEach(nota => {
              const card = document.createElement("div");
              card.className = "p-4 border rounded bg-gray-50 shadow";
              card.innerHTML = `
                <div class="flex justify-between items-start">
                  <p class="text-gray-800 mr-2">${nota.mensaje}</p>
                  <button data-id="${nota.id}" class="text-red-500 hover:text-red-700 text-xl leading-none" title="Borrar nota">
                    &times;
                  </button>
                </div>
                <div class="text-sm text-gray-500 mt-2">
                  ${nota.autor || "Anónimo"} – ${new Date(nota.fecha).toLocaleString()}
                </div>
              `;
              lista.appendChild(card);
            });
          });
      }

      lista.addEventListener("click", function(e) {
        if (e.target.tagName === "BUTTON" && e.target.dataset.id) {
          const id = e.target.dataset.id;
          if (confirm("¿Estás seguro de borrar esta nota?")) {
            fetch("borrarNota.php", {
                method: "POST",
                headers: {
                  "Content-Type": "application/x-www-form-urlencoded"
                },
                body: "id=" + encodeURIComponent(id)
              })
              .then(res => res.json())
              .then(respuesta => {
                if (respuesta.success) {
                  cargarNotas();
                } else {
                  alert(respuesta.error || "No se pudo borrar.");
                }
              });
          }
        }
      });

      form.addEventListener("submit", function(e) {
        e.preventDefault();
        const datos = new FormData(form);

        fetch("guardarNota.php", {
            method: "POST",
            body: datos
          })
          .then(res => res.json())
          .then(respuesta => {
            if (respuesta.success) {
              form.reset();
              cargarNotas();
            } else {
              alert(respuesta.error || "Error al guardar la nota.");
            }
          });
      });

      cargarNotas(); // carga inicial
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
        Panel de Noticias desarrollado por el equipo de SPEI:<br>
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
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const modal = document.getElementById('modalFichaCensal');
      const form = document.getElementById('fichaCensalForm');
      const errorMsg = document.getElementById('errorFichaCensal');

      if (modal && form) {
        form.addEventListener('submit', function(e) {
          e.preventDefault();
          errorMsg.classList.add('hidden');
          const ficha = form.ficha_censal.value.trim();
          if (!ficha) {
            errorMsg.textContent = "El campo ficha censal es obligatorio.";
            errorMsg.classList.remove('hidden');
            return;
          }

          // Enviar AJAX
          fetch('/includes/guardar_ficha_censal.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
              },
              body: 'ficha_censal=' + encodeURIComponent(ficha)
            })
            .then(res => res.text())
            .then(res => {
              if (res.trim() === 'OK') {
                // Cerrar el modal
                modal.classList.add('hidden');
                location.reload();
              } else {
                errorMsg.textContent = res;
                errorMsg.classList.remove('hidden');
              }
            })
            .catch(() => {
              errorMsg.textContent = "Hubo un error. Intentá de nuevo.";
              errorMsg.classList.remove('hidden');
            });
        });
      }
    });
  </script>
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
  <!-- Modal de ficha censal -->
  <div id="modalFichaCensal"
    class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 <?= $mostrar_modal ? '' : 'hidden' ?>">
    <form id="fichaCensalForm"
      class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md space-y-5"
      method="POST"
      autocomplete="off"
      style="min-width:300px">
      <h2 class="text-2xl font-bold text-center mb-3 text-blue-700">Completar ficha censal</h2>
      <p class="mb-2 text-gray-700 text-center">Para continuar, ingresá tu número de ficha censal:</p>
      <input type="text" id="ficha_censal" name="ficha_censal" required
        class="w-full border rounded-xl p-2" maxlength="30" autofocus>
      <button type="submit"
        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 rounded-xl transition mt-2">
        Guardar
      </button>
      <p id="errorFichaCensal" class="text-red-600 text-center text-sm mt-2 hidden"></p>
    </form>
  </div>
</body>

</html>