<?php
$hostname = "localhost";
$username = "root";
$password = "";
$database = "tecnico-administrativo-wirelessdb";

// Usando mysqli_connect como procedimiento
$conn = mysqli_connect($hostname, $username, $password, $database);

if (!$conn) {
	die("Error de conexión: " . mysqli_connect_error());
}

// Establecer el juego de caracteres a utf8mb4 para manejar caracteres especiales y tildes
$conn->set_charset("utf8mb4");
?>