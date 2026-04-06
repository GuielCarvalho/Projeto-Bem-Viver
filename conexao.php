<?php

    define('HOST', 'localhost');
    define('USER', 'root');
    define('PASS', '#10531832gjnc'); // Altere para a sua senha do MySQL
    define('BASE', 'dbbemvivernovo');

    $conn = new mysqli(HOST, USER, PASS, BASE);

    if ($conn->connect_error) {
        die("Erro na conexão: " . $conn->connect_error);
    }
?>