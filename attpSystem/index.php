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
  header("Location: ../login.php?error=rol");
  exit;
}

// Guarda los datos del usuario logueado en una variable $u
$u = $_SESSION['usuario'];

// Incluye la conexión a la base de datos
include "../includes/db.php";

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
  <link rel="stylesheet" href="../output.css">
  <link rel="icon" type="image/x-icon" href="../images/et20png.png">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
  <div class="relative min-h-screen flex flex-col md:flex-row">
    <!-- Sidebar -->
    <div id="sidebar" class="absolute top-0 left-0 w-64 bg-blue-800 text-white min-h-screen z-50 transform -translate-x-full transition-transform duration-300">
      <div class="flex justify-between items-center p-4 border-b border-blue-700">
        <a href="#" class="flex items-center text-xl font-bold">
          <img src="../images/et20ico.ico" class="mr-2">
          Panel SPEI
        </a>
      </div>

      <!-- Perfil del usuario -->
      <div class="p-6 text-center border-b border-blue-700">
        <img src="<?php echo $u['foto_url'] ?? 'https://ui-avatars.com/api/?name=' . $u['nombre']; ?>" class="block mx-auto rounded-full w-14 h-14">
        <h2 class="text-lg font-semibold"><?php echo $u['nombre'] . ' ' . $u['apellido']; ?></h2>
        <p class="text-sm text-blue-200">SPEI</p>
      </div>

      <!-- Menú -->
      <nav class="p-4 space-y-2">
        <a href="index.php" class="block py-2 px-4 bg-blue-700 rounded">Página Principal</a>
        <a href="stock.php" class="block py-2 px-4 hover:bg-blue-700 rounded">Stock</a>
        <a href="prestamos.php" class="block py-2 px-4 hover:bg-blue-700 rounded">Préstamos</a>
        <a href="logs.php" class="block py-2 px-4 hover:bg-blue-700 rounded">Logs</a>
      </nav>
      <div class="p-4 border-t border-blue-700">
        <?php if (isset($_SESSION['roles_disponibles']) && count($_SESSION['roles_disponibles']) > 1): ?>
          <form method="post" action="../includes/cambiar_rol.php" class="mt-auto mb-3">
            <select name="rol" onchange="this.form.submit()" class="w-full px-3 py-2 border text-sm rounded-xl text-gray-700 bg-white">
              <?php foreach ($_SESSION['roles_disponibles'] as $r): ?>
                <option value="<?php echo $r['id']; ?>" <?php if ($_SESSION['usuario']['rol'] == $r['id']) echo 'selected'; ?>>
                  Cambiar a: <?php echo ucfirst($r['nombre']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </form>
        <?php endif; ?>
        <form action="../includes/logout.php" method="POST">
          <button type="submit" class="w-full py-2 px-4 mt-4 bg-red-600 hover:bg-red-700 text-white rounded text-center">
            Cerrar sesión
          </button>
        </form>
      </div>
      <div class="p-6 mt-10 text-center text-gray-400"><button onclick="mostrarCreditos()">Créditos</button></div>
    </div>

    <!-- Contenido principal -->
    <main id="mainContent" class="w-full p-4 md:p-8 transition-all duration-300">
      <!-- Botón hamburguesa -->
      <div class="mb-4 md">
        <button id="toggleSidebar" class="text-2xl text-blue-800 bg-white p-2 rounded shadow">
          ☰
        </button>
      </div>
      <h1 class="text-3xl font-bold mb-6">Panel Principal</h1>
      <section class="p-4">
        <div class="bg-white shadow rounded-xl p-6 max-w-3xl mx-auto">
          <h2 class="text-xl font-bold mb-4">📝 Pizarrón de SPEI</h2>

          <!-- Formulario para nueva nota -->
          <form id="formNota" class="mb-4">
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
        fetch("./actions/obtenerNotas.php")
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
            fetch("./actions/borrarNota.php", {
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

        fetch("./actions/guardarNota.php", {
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

  <!-- Transición del Sidebar al Mostrarse/Ocultarse -->
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
</body>

</html>