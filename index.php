<?php

use Dotenv\Dotenv;

require_once 'vendor/autoload.php';

$dotenv = new Dotenv(__DIR__);
$dotenv->load();


$servidor = getenv('DB_HOST');
$usuario = getenv('DB_USER');
$senha = getenv('DB_PASS');
$banco = getenv('DB_DATABASE');

$credencialBase = [
    'BD_SERVIDOR'   => $servidor,
    'BD_USUARIO'    => $usuario,
    'BD_SENHA'      => $senha,
    'BD_BANCO'      => $banco,
];

