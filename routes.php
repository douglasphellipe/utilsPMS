<?php

use App\Classes\Entradas;
use App\Controllers\Entradas as ControllersEntradas;
use Bramus\Router\Router;
use App\Controllers\GeraEntrada;
$router = new Router();

// Defina suas rotas

$router->get('/', function() {
    (new ControllersEntradas)->index('35250602793452000101550010001029261534440419', 'dphellipe'); 
});
$router->post('/altera-sugestao-bin', function($idSN) {
    (new Entradas)->novaSugestaoBin($idSN); 
});
$router->post('/atualiza-bin', function($idSN, $binName){
    (new Entradas)->atualizaBin($idSN, $binName);
});
$router->post('/valida-entrada', function($idSN, $binName){
    (new Entradas)->atualizaBin($idSN, $binName);
});
return $router;
