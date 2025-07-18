<?php

session_start();

include './incl/lib/.panel_pass.php';

if (!isset($_SESSION['panel_autenticado'])) {

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {

        if ($_POST['password'] === $panelPassword) {

            $_SESSION['panel_autenticado'] = false;

            header("Location: " . $_SERVER['PHP_SELF']);

            exit;

        } else {

            $error = "Contraseña incorrecta.";

        }

    }

    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Acceso</title></head><body>';

    echo "<h2>🔐 Acceso protegido</h2>";

    if (isset($error)) echo "<p style='color:red;'>$error</p>";

    echo '<form method="post"><input type="password" name="password" required autofocus><button type="submit">Entrar</button></form></body></html>';

    exit;

}

include './incl/lib/db_only.php';

// Añadir nueva tabla

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_tabla'])) {

    $nombre = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['nombre_tabla']);

    if ($nombre !== '') {

        $sql = "CREATE TABLE IF NOT EXISTS `$nombre` (

            `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY

        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        $db->exec($sql);

    }

    header("Location: " . $_SERVER['PHP_SELF']);

    exit;

}

// Eliminar tabla

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_tabla'])) {

    $nombre = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['nombre_tabla']);

    if ($nombre !== '') {

        $sql = "DROP TABLE IF EXISTS `$nombre`;";

        $db->exec($sql);

    }

    header("Location: " . $_SERVER['PHP_SELF']);

    exit;

}

// Obtener tablas

$tablas = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_NUM);

?>

<!DOCTYPE html>

<html lang="es">

<head>

<meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Administrar Tablas</title>

<style>

    body { background:#111; color:#eee; font-family:sans-serif; padding:20px; }

    input, select, button { background:#222; color:#eee; border:none; padding:6px; margin:5px; }

    button { cursor:pointer; }

    .btn-red { background:#d32f2f; }

    .btn-green { background:#388e3c; }

    table { width:100%; background:#222; border-collapse: collapse; margin-top:20px; }

    th, td { padding:10px; border:1px solid #444; }

</style>

</head>

<body>

<h1>📋 Administrar Tablas</h1>

<a href="db.php" style="color:#80cbc4;">⬅ Volver al panel</a>

<h2>➕ Crear nueva tabla</h2>

<form method="post">

    <input type="text" name="nombre_tabla" placeholder="Nombre de la nueva tabla" required pattern="[a-zA-Z0-9_]+" title="Solo letras, números y guión bajo">

    <button type="submit" name="crear_tabla" class="btn-green">Crear</button>

</form>

<h2>📄 Tablas existentes</h2>

<table>

<tr><th>Nombre de la tabla</th><th>Acción</th></tr>

<?php foreach ($tablas as $t): ?>

<tr>

    <td><?= htmlspecialchars($t[0]) ?></td>

    <td>

        <form method="post" onsubmit="return confirm('¿Eliminar tabla <?= htmlspecialchars($t[0]) ?>? Se borrarán todos los datos.')">

            <input type="hidden" name="nombre_tabla" value="<?= htmlspecialchars($t[0]) ?>">

            <button type="submit" name="eliminar_tabla" class="btn-red">🗑️ Borrar</button>

        </form>

    </td>

</tr>

<?php endforeach; ?>

</table>

</body>

</html>
<html lang="es">

<head><meta charset="UTF-8"></head>

<body>

<p>Hora local del usuario: <span id="horaLocal"></span></p>

<script>

function actualizarHora() {

  const ahora = new Date();

  document.getElementById('horaLocal').textContent = ahora.toLocaleTimeString();

}

actualizarHora();

setInterval(actualizarHora, 1000);

</script>

</body>

</html>