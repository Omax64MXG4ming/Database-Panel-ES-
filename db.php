 <html lang="es">

<head><meta charset="UTF-8"></head>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
<body>
      <a class="button" href="/dashboard/">Dashboard</a>
  
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
<?php
//don't change these, if you don't know what you're doing
session_start();
//Browser translation available
include 'incl/lib/.panel_pass.php';
//if you put this file somewhere else, change the include 
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

//<!-- change the texts to whatever, 
// (and the language)-->

    echo "<h2>🔐 Panel protegido</h2>";

    if (isset($error)) echo "<p style='color:red;'>$error</p>";

    echo '<form method="post"><input type="password" name="password" required autofocus><button type="submit">Entrar</button></form></body></html>';

    exit;

}

include 'incl/lib/db_only.php';
//Is this file somewhere else? Change it: ***/ . as you wish
// 🛡️ Rate limit simple (1 petición cada 1s por IP)

$ip = $_SERVER['REMOTE_ADDR'];

$rateFile = sys_get_temp_dir() . "/panel_rate_" . md5($ip);

if (file_exists($rateFile) && time() - filemtime($rateFile) < 1) {

    http_response_code(429);

    die("Demasiadas peticiones. Espera un momento..");

}

touch($rateFile);

if (isset($_GET['logout'])) {

    session_destroy();

    header("Location: " . $_SERVER['PHP_SELF']);

    exit;

}

$selectedTable = $_GET['tabla'] ?? null;

$editId = $_GET['edit'] ?? null;

$deleteId = $_GET['delete'] ?? null;

$page = max(1, intval($_GET['page'] ?? 1));

$limit = 50;

$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';

$export = isset($_GET['export']);

// Obtener clave primaria

function getPrimaryKey($db, $table) {

    $cols = $db->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($cols as $col) {

        if ($col['Key'] === 'PRI') return $col['Field'];

    }

    return null;

}

// Borrar fila

if ($selectedTable && $deleteId) {

    $pk = getPrimaryKey($db, $selectedTable);

    $stmt = $db->prepare("DELETE FROM `$selectedTable` WHERE `$pk` = :id");

    $stmt->execute([':id' => $deleteId]);

    header("Location: ?tabla=" . urlencode($selectedTable));

    exit;

}

// Editar fila

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_row'])) {

    $table = $_POST['table'];

    $pk = $_POST['pk'];

    $pkVal = $_POST['pk_val'];

    $columns = array_diff(array_keys($_POST), ['update_row', 'table', 'pk', 'pk_val']);

    $sets = [];

    $params = [];

    foreach ($columns as $col) {

        $sets[] = "`$col` = :$col";

        $params[":$col"] = $_POST[$col];

    }

    $params[":pk"] = $pkVal;

    $sql = "UPDATE `$table` SET " . implode(", ", $sets) . " WHERE `$pk` = :pk";

    $stmt = $db->prepare($sql);

    $stmt->execute($params);

    header("Location: ?tabla=" . urlencode($table));

    exit;

}

// Añadir fila

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_row'])) {

    $table = $_POST['table'];

    $columns = $db->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);

    $fields = [];

    $placeholders = [];

    $params = [];

    foreach ($columns as $col) {

        if ($col['Extra'] === 'auto_increment') continue;

        $fields[] = "`{$col['Field']}`";

        $placeholders[] = ":{$col['Field']}";

        $params[":{$col['Field']}"] = $_POST[$col['Field']] ?? null;

    }

    $sql = "INSERT INTO `$table` (" . implode(",", $fields) . ") VALUES (" . implode(",", $placeholders) . ")";

    $stmt = $db->prepare($sql);

    $stmt->execute($params);

    header("Location: ?tabla=" . urlencode($table));

    exit;

}

// --- NUEVO: Procesar cambios en estructura de tabla ---

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['alter_table'])) {

    $table = $_POST['table'];

    $action = $_POST['action']; // add, drop, modify

    if ($action === 'add') {

        $colName = $_POST['col_name'];

        $colType = $_POST['col_type'];

        $nullable = isset($_POST['nullable']) ? 'NULL' : 'NOT NULL';

        $default = $_POST['default'] !== '' ? "DEFAULT " . $db->quote($_POST['default']) : '';

        $extra = $_POST['extra'] ?? '';

        $sql = "ALTER TABLE `$table` ADD COLUMN `$colName` $colType $nullable $default $extra";

        $db->exec($sql);

    } elseif ($action === 'drop') {

        $colName = $_POST['col_name'];

        $sql = "ALTER TABLE `$table` DROP COLUMN `$colName`";

        $db->exec($sql);

    } elseif ($action === 'modify') {

        $oldName = $_POST['old_name'];

        $newName = $_POST['col_name'];

        $colType = $_POST['col_type'];

        $nullable = isset($_POST['nullable']) ? 'NULL' : 'NOT NULL';

        $default = $_POST['default'] !== '' ? "DEFAULT " . $db->quote($_POST['default']) : '';

        $extra = $_POST['extra'] ?? '';

        $sql = "ALTER TABLE `$table` CHANGE COLUMN `$oldName` `$newName` $colType $nullable $default $extra";

        $db->exec($sql);

    }

    header("Location: ?tabla=" . urlencode($table));

    exit;

}

?>

<!DOCTYPE html>

<html lang="es">

<head>

<meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Panel DB</title>

<style>

    body { background:#111; color:#eee; font-family:sans-serif; padding:20px; }

    table { border-collapse: collapse; width: 100%; margin: 10px 0; background:#222; }

    th, td { border: 1px solid #444; padding: 8px; }

    th { background:#333; }

    a, button { color:#80cbc4; text-decoration:none; }

    input, textarea, select { background:#333; border:none; color:#eee; padding:4px; width:100%; }

    .btn { background:#4caf50; color:white; padding:4px 8px; border:none; cursor:pointer; }

    .btn-red { background:#f44336; }

    .pagination { margin-top:10px; }

    .pagination a { margin-right: 5px; }

</style>

</head>

<body>

<a href="?logout=1" style="float:right;">Cerrar sesión</a>

<h1>📊 Panel de Base de Datos</h1>

<?php if (!$selectedTable): ?>

    <h2>Tablas disponibles</h2>

    <ul>

    <?php

    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_NUM);

    foreach ($tables as $t) {

        echo "<li><a href='?tabla=" . urlencode($t[0]) . "'>" . htmlspecialchars($t[0]) . "</a></li>";

    }

    ?>

    </ul>

<?php else: ?>

    <a href="?">⬅ Volver</a>

    <h2>Tabla: <?= htmlspecialchars($selectedTable) ?></h2>

    <form method="get" style="margin-bottom: 10px;">

        <input type="hidden" name="tabla" value="<?= htmlspecialchars($selectedTable) ?>">

        <input type="text" name="search" placeholder="🔍 Buscar..." value="<?= htmlspecialchars($search) ?>">

        <button type="submit">Buscar</button>

        <a href="?tabla=<?= urlencode($selectedTable) ?>&export=1">📤 Exportar CSV</a>

    </form>

    <?php

    $columns = $db->query("SHOW COLUMNS FROM `$selectedTable`")->fetchAll(PDO::FETCH_ASSOC);

    $pk = getPrimaryKey($db, $selectedTable);

    $sql = "SELECT * FROM `$selectedTable`";

    $params = [];

    if ($search !== '') {

        $sql .= " WHERE " . implode(" OR ", array_map(fn($col) => "`{$col['Field']}` LIKE :search", $columns));

        $params[':search'] = "%$search%";

    }

    if ($export) {

        $stmt = $db->prepare($sql);

        $stmt->execute($params);

        $all = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header("Content-Type: text/csv");

        header("Content-Disposition: attachment; filename=$selectedTable.csv");

        $f = fopen('php://output', 'w');

        if (count($all) > 0) fputcsv($f, array_keys($all[0]));

        foreach ($all as $r) fputcsv($f, $r);

        fclose($f);

        exit;

    }

    $total = $db->prepare(str_replace("SELECT *", "SELECT COUNT(*)", $sql));

    $total->execute($params);

    $count = $total->fetchColumn();

    $pages = ceil($count / $limit);

    $sql .= " LIMIT $limit OFFSET $offset";

    $stmt = $db->prepare($sql);

    $stmt->execute($params);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ?>

    <table>

        <tr>

            <?php foreach ($columns as $col): ?>

                <th><?= htmlspecialchars($col['Field']) ?></th>

            <?php endforeach; ?>

            <th>Acciones</th>

        </tr>

        <?php foreach ($rows as $row): ?>

            <tr>

                <?php foreach ($columns as $col): ?>

                    <td><?= htmlspecialchars($row[$col['Field']]) ?></td>

                <?php endforeach; ?>

                <td>

                    <a href="?tabla=<?= urlencode($selectedTable) ?>&edit=<?= urlencode($row[$pk]) ?>">✏️</a>

                    <a href="?tabla=<?= urlencode($selectedTable) ?>&delete=<?= urlencode($row[$pk]) ?>" onclick="return confirm('¿Borrar fila?')">🗑️</a>

                </td>

            </tr>

        <?php endforeach; ?>

    </table>

    <!-- Paginación -->

    <div class="pagination">

        <?php for ($p = 1; $p <= $pages; $p++): ?>

            <a href="?tabla=<?= urlencode($selectedTable) ?>&search=<?= urlencode($search) ?>&page=<?= $p ?>" <?= $p == $page ? 'style="font-weight:bold;"' : '' ?>>[<?= $p ?>]</a>

        <?php endfor; ?>

    </div>

    <!-- Añadir nueva fila -->

    <h3>➕ Añadir nueva fila</h3>

    <form method="post">

        <input type="hidden" name="add_row" value="1">

        <input type="hidden" name="table" value="<?= htmlspecialchars($selectedTable) ?>">

        <table>

            <tr>

            <?php foreach ($columns as $col): ?>

                <td>

                    <?= $col['Extra'] === 'auto_increment' ? '<i>auto</i>' : '<input name="'.$col['Field'].'" placeholder="'.$col['Field'].'">' ?>

                </td>

            <?php endforeach; ?>

                <td><button class="btn">➕</button></td>

            </tr>

        </table>

    </form>

    <!-- Editar fila -->

    <?php if ($editId):

        $stmt = $db->prepare("SELECT * FROM `$selectedTable` WHERE `$pk` = :id");

        $stmt->execute([':id' => $editId]);

        $editRow = $stmt->fetch(PDO::FETCH_ASSOC);

    ?>

        <h3>✏️ Editar fila</h3>

        <form method="post">

            <input type="hidden" name="update_row" value="1">

            <input type="hidden" name="table" value="<?= htmlspecialchars($selectedTable) ?>">

            <input type="hidden" name="pk" value="<?= $pk ?>">

            <input type="hidden" name="pk_val" value="<?= htmlspecialchars($editId) ?>">

            <table>

                <tr>

                <?php foreach ($columns as $col): ?>

                    <td><input name="<?= $col['Field'] ?>" value="<?= htmlspecialchars($editRow[$col['Field']]) ?>"></td>

                <?php endforeach; ?>

                    <td><button class="btn">💾</button></td>

                </tr>

            </table>

        </form>

    <?php endif; ?>

    <!-- Modificar estructura de la tabla -->

    <h3>⚙️ Modificar estructura de la tabla</h3>

    <table>

        <tr>

            <th>Columna</th>

            <th>Tipo</th>

            <th>Null</th>

            <th>Default</th>

            <th>Extra</th>

            <th>Acciones</th>

        </tr>

        <?php foreach ($columns as $col): ?>

        <tr>

            <td><?= htmlspecialchars($col['Field']) ?></td>

            <td><?= htmlspecialchars($col['Type']) ?></td>

            <td><?= $col['Null'] ?></td>

            <td><?= htmlspecialchars($col['Default']) ?></td>

            <td><?= htmlspecialchars($col['Extra']) ?></td>

            <td>

                <!-- Eliminar columna -->

                <form method="post" style="display:inline" onsubmit="return confirm('¿Eliminar columna <?= htmlspecialchars($col['Field']) ?>?')">

                    <input type="hidden" name="alter_table" value="1">

                    <input type="hidden" name="table" value="<?= htmlspecialchars($selectedTable) ?>">

                    <input type="hidden" name="action" value="drop">

                    <input type="hidden" name="col_name" value="<?= htmlspecialchars($col['Field']) ?>">

                    <button type="submit" class="btn btn-red">Eliminar</button>

                </form>

                <!-- Modificar columna -->

                <button onclick="document.getElementById('modify-<?= htmlspecialchars($col['Field']) ?>').style.display='block'; this.style.display='none';">Modificar</button>

                <form method="post" id="modify-<?= htmlspecialchars($col['Field']) ?>" style="display:none; margin-top:5px;">

                    <input type="hidden" name="alter_table" value="1">

                    <input type="hidden" name="table" value="<?= htmlspecialchars($selectedTable) ?>">

                    <input type="hidden" name="action" value="modify">

                    <input type="hidden" name="old_name" value="<?= htmlspecialchars($col['Field']) ?>">

                    <input type="text" name="col_name" value="<?= htmlspecialchars($col['Field']) ?>" required>

                    <input type="text" name="col_type" value="<?= htmlspecialchars($col['Type']) ?>" required>

                    <label><input type="checkbox" name="nullable" <?= $col['Null'] === 'YES' ? 'checked' : '' ?>> Nullable</label>

                    <input type="text" name="default" placeholder="Default" value="<?= htmlspecialchars($col['Default']) ?>">

                    <input type="text" name="extra" placeholder="Extra (e.g. auto_increment)" value="<?= htmlspecialchars($col['Extra']) ?>">

                    <button type="submit" class="btn">Guardar</button>

                    <button type="button" onclick="this.form.style.display='none'; this.form.previousElementSibling.style.display='inline-block';">Cancelar</button>

                </form>

            </td>

        </tr>

        <?php endforeach; ?>

    </table>

    <h4>➕ Añadir nueva columna</h4>

    <form method="post">

        <input type="hidden" name="alter_table" value="1">

        <input type="hidden" name="table" value="<?= htmlspecialchars($selectedTable) ?>">

        <input type="hidden" name="action" value="add">

        <input type="text" name="col_name" placeholder="Nombre columna" required>

        <input type="text" name="col_type" placeholder="Tipo (ej. VARCHAR(255))" required>

        <label><input type="checkbox" name="nullable"> Nullable</label>

        <input type="text" name="default" placeholder="Valor por defecto (opcional)">

        <input type="text" name="extra" placeholder="Extra (ej. auto_increment)">

        <button type="submit" class="btn">Añadir columna</button>

    </form>

<?php endif; ?>

</body>

</html>

<html>
<body>
      <a class="button" href="dbSQL.php">Consola SQL</a>
  
        <a class="button" href="dbtab.php">Administrar Tablas</a>

</body>
</html>
