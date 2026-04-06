<?php
session_unset();
session_destroy();
require_once('conexao.php');
header('Location: index.php?page=login');
exit;
?>