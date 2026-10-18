<?php
/* ================= CONFIG ================= */
<?php
// Habilitar reporte de errores para ver si algo falla
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// getenv() detecta automáticamente el usuario, contraseña y base de datos en Railway
$host     = getenv('MYSQLHOST') ?: 'mysql.railway.internal'; 
$user     = getenv('MYSQLUSER') ?: 'root';
$password = getenv('MYSQLPASSWORD') ?: 'EeBwWKVhMoYXMisvHxDIZAtbwbNdQcsQ';
$database = getenv('MYSQLDATABASE') ?: 'datos';
$port     = getenv('MYSQLPORT') ?: 8080; // Crucial: Railway usa puertos variables

try {
    // Conexión pasándole el puerto ($port) al final obligatoriamente
    $mysqli = new mysqli($host, $user, $password, $database, $port);
    $mysqli->set_charset("utf8mb4"); 
} catch (Exception $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}
?>


/* ================= ACCIONES ================= */
if (isset($_POST['accion'])) {
    switch ($_POST['accion']) {
        case 'agregar':
            $nombre = $_POST['nombre'];
            $correo = $_POST['correo'];
            $conexion->query("INSERT INTO $table (nombre, correo) VALUES ('$nombre','$correo')");
            $mensaje = "Usuario agregado correctamente";
        break;

        case 'editar':
            $id = $_POST['id'];
            $nombre = $_POST['nombre'];
            $correo = $_POST['correo'];
            $conexion->query("UPDATE $table SET nombre='$nombre', correo='$correo' WHERE id=$id");
            $mensaje = "Usuario actualizado correctamente";
        break;

        case 'eliminar':
            $id = $_POST['id'];
            $conexion->query("DELETE FROM $table WHERE id=$id");
            $mensaje = "Usuario eliminado";
        break;

        case 'eliminar_todo':
            $conexion->query("TRUNCATE TABLE $table");
            $mensaje = "Todos los registros fueron eliminados";
            $tipoAlerta = "danger";
        break;
    }
}

/* ================= EXPORTAR CSV ================= */
if (isset($_GET['exportar'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="usuarios.csv"');
    $salida = fopen('php://output', 'w');
    fputcsv($salida, ['ID', 'Nombre', 'Correo']);
    $res = $conexion->query("SELECT * FROM $table");
    while ($row = $res->fetch_assoc()) {
        fputcsv($salida, $row);
    }
    exit;
}

/* ================= PAGINACIÓN ================= */
$por_pagina = 5;
$pagina = isset($_GET['pagina']) ? $_GET['pagina'] : 1;
$inicio = ($pagina - 1) * $por_pagina;
$total = $conexion->query("SELECT COUNT(*) total FROM $table")->fetch_assoc()['total'];
$paginas = ceil($total / $por_pagina);
$datos = $conexion->query("SELECT * FROM $table ORDER BY id DESC LIMIT $inicio,$por_pagina");
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Lista de Usuarios</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<style>
body{background:linear-gradient(135deg,#1d2671,#c33764);min-height:100vh}
.card{border-radius:20px}
.btn{border-radius:30px}
</style>
</head>
<body class="p-4">
<div class="container">
<div class="card shadow-lg p-4">
<h3 class="text-center mb-3">Lista de Usuarios</h3>

<?php if ($mensaje): ?>
<div class="alert alert-<?= $tipoAlerta ?> alert-dismissible fade show">
<?= $mensaje ?>
<button class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="d-flex gap-2 mb-3">
<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAgregar">➕ Agregar</button>
<a href="?exportar=1" class="btn btn-success">⬇ Descargar CSV</a>
<button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalEliminarTodo">🗑 Borrar Todo</button>
</div>

<table class="table table-hover table-bordered">
<thead class="table-dark"><tr><th>ID</th><th>Nombre</th><th>Correo</th><th>Acciones</th></tr></thead>
<tbody>
<?php while($row=$datos->fetch_assoc()): ?>
<tr>
<td><?= $row['id'] ?></td>
<td><?= $row['nombre'] ?></td>
<td><?= $row['correo'] ?></td>
<td>
<button class="btn btn-warning btn-sm btnEditar"
 data-id="<?= $row['id'] ?>"
 data-nombre="<?= $row['nombre'] ?>"
 data-correo="<?= $row['correo'] ?>"
 data-bs-toggle="modal" data-bs-target="#modalEditar">✏</button>
<button class="btn btn-danger btn-sm btnEliminar"
 data-id="<?= $row['id'] ?>"
 data-bs-toggle="modal" data-bs-target="#modalEliminar">🗑</button>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

<!-- PAGINACIÓN -->
<nav>
<ul class="pagination justify-content-center">
<?php for($i=1;$i<=$paginas;$i++): ?>
<li class="page-item <?= $i==$pagina?'active':'' ?>">
<a class="page-link" href="?pagina=<?= $i ?>"><?= $i ?></a>
</li>
<?php endfor; ?>
</ul>
</nav>
</div>
</div>

<!-- MODALES -->
<div class="modal fade" id="modalAgregar"><div class="modal-dialog"><form class="modal-content" method="POST">
<div class="modal-header"><h5>Agregar</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><input type="hidden" name="accion" value="agregar">
<input class="form-control mb-2" name="nombre" placeholder="Nombre" required>
<input class="form-control" name="correo" placeholder="Correo" required></div>
<div class="modal-footer"><button class="btn btn-success">Guardar</button></div>
</form></div></div>

<div class="modal fade" id="modalEditar"><div class="modal-dialog"><form class="modal-content" method="POST">
<div class="modal-header"><h5>Editar</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><input type="hidden" name="accion" value="editar">
<input type="hidden" name="id" id="edit_id">
<input class="form-control mb-2" name="nombre" id="edit_nombre" required>
<input class="form-control" name="correo" id="edit_correo" required></div>
<div class="modal-footer"><button class="btn btn-warning">Actualizar</button></div>
</form></div></div>

<div class="modal fade" id="modalEliminar"><div class="modal-dialog"><form class="modal-content" method="POST">
<div class="modal-header"><h5>Eliminar</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><input type="hidden" name="accion" value="eliminar">
<input type="hidden" name="id" id="delete_id">¿Eliminar este registro?</div>
<div class="modal-footer"><button class="btn btn-danger">Eliminar</button></div>
</form></div></div>

<div class="modal fade" id="modalEliminarTodo"><div class="modal-dialog"><form class="modal-content" method="POST">
<div class="modal-header"><h5>⚠ Eliminar Todo</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><input type="hidden" name="accion" value="eliminar_todo">
<p>Esto borrará TODOS los registros.</p></div>
<div class="modal-footer"><button class="btn btn-danger">Confirmar</button></div>
</form></div></div>

<script>
document.querySelectorAll('.btnEditar').forEach(b=>b.onclick=()=>{
edit_id.value=b.dataset.id;edit_nombre.value=b.dataset.nombre;edit_correo.value=b.dataset.correo;});
document.querySelectorAll('.btnEliminar').forEach(b=>b.onclick=()=>{delete_id.value=b.dataset.id;});
</script>
</body>
</html>
