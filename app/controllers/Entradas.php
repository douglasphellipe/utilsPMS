<?php
namespace App\Controllers;
use App\Classes\Faturar;

class Entradas{
    public function index($chvNFE, $login){
        $dados = new Faturar();
        $criaEntrada = $dados->gera_pedidoentradaclienteComSerial($chvNFE, $login);

        return $criaEntrada;
    }

}