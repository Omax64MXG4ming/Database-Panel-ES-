<?php

session_start();

include 'incl/lib/.panel_pass.php';

if (!isset($_SESSION['panel_autenticado'])) {

    header("Location: db.php");

    exit;

}

include 'incl/lib/db_only.php';

?>

<!DOCTYPE html>

<html lang="es">

<head>

  <meta charset="UTF-8">

  <title>Consola SQL</title>

  <style>

    body { background:#111; color:#eee; font-family:sans-serif; padding:20px; }

    textarea, input[type=text] { width:100%; background:#222; color:#fff; border:1px solid #444; padding:10px; font-family:monospace; }

    button { background:#4caf50; color:white; border:none; padding:10px 20px; margin-top:10px; cursor:pointer; }

    table { border-collapse:collapse; width:100%; margin-top:20px; background:#222; }

    th, td { border:1px solid #444; padding:8px; text-align:left; }

    th { background:#333; }

    a { color:#80cbc4; text-decoration:none; }

  </style>

</head>

<body>

<a href="db.php">⬅ Volver al panel</a>

<h2>🖥️ Consola SQL</h2>

<form method="post">

  <textarea name="sql" rows="6" placeholder="Escribe tu consulta SQL aquí..."><?= htmlspecialchars($_POST['sql'] ?? '') ?></textarea>

  <button type="submit">Ejecutar</button>

</form>

<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sql'])) {

    $sql = trim($_POST['sql']);

    if ($sql) {

        try {

            $stmt = $db->query($sql);

            if (stripos($sql, 'SELECT') === 0) {

                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if ($result) {

                    echo "<table><tr>";

                    foreach (array_keys($result[0]) as $col) {

                        echo "<th>" . htmlspecialchars($col) . "</th>";

                    }

                    echo "</tr>";

                    foreach ($result as $row) {

                        echo "<tr>";

                        foreach ($row as $cell) {

                            echo "<td>" . htmlspecialchars($cell) . "</td>";

                        }

                        echo "</tr>";

                    }

                    echo "</table>";

                } else {

                    echo "<p>Consulta ejecutada pero sin resultados.</p>";

                }

            } else {

                echo "<p>Consulta ejecutada correctamente.</p>";

            }

        } catch (PDOException $e) {

            echo "<p style='color:red;'>Error SQL: " . htmlspecialchars($e->getMessage()) . "</p>";

        }

    }

}

?>

</body>

</html>