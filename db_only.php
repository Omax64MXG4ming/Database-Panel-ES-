<?php

error_reporting(1);

require dirname(__FILE__)."/../../config/connection.php";

// Crear conexión simple sin IP checks ni dashboard

try {

    $db = new PDO("mysql:host=$servername;port=$port;dbname=$dbname", $username, $password, array(PDO::ATTR_PERSISTENT => true));

    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (Exception $e) {

    die("Error de conexión: " . $e->getMessage());

} 

?>