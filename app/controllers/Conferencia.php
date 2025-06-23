<?php
namespace App\Controllers;

use App\Classes\Entradas;
use App\Classes\Tarefas;

class Conferencia{
    public function concluiConferencia($idTarefa, $itens){
        $tarefas = new Tarefas();
        $entrada = new Entradas();
        $confereEntrada = $entrada->validaItensEntrada($itens);
        if($confereEntrada){
            $tarefas->atualizaStatus($idTarefa, 'AGUARDANDO ARMAZENAGEM');
        }
    }
    

}