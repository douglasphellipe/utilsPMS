<?php
require_once 'vendor/autoload.php';
use App\Classes\Faturar;
 
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();


$servidor = getenv('DB_HOST');
$usuario = getenv('DB_USER');
$senha = getenv('DB_PASS');
$banco = getenv('DB_DATABASE');

$faturar = new Faturar($servidor, $usuario, $senha, $banco);

$id = '35250532681217000109550010000011661250155798';
$login = 'dphellipe';

$resultado = $faturar->gera_pedidoentradaclienteComSerial($id, $login);

echo 'Resultado: ' . $resultado;

?>