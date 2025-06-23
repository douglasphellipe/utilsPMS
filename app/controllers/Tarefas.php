<?php
namespace App\Controllers;
use App\Classes\Faturar;
use App\Classes\Tarefas as ClassesTarefas;
use Exception;

class Tarefas{
    public function index(){
        $tarefas = new ClassesTarefas();
        return json_encode($tarefas->listaTodasTarefas());
    }
    public function tarefasPendentes(){
        $tarefas = new ClassesTarefas();
        return json_encode($tarefas->listaTarefas('PENDENTE'));
    }
    public function criaTarefa($dadosTarefa, $itens){
        $tarefas = new ClassesTarefas();
         try {
            $tarefas->CONEXAO->beginTransaction();
            $task = $tarefas->geraTarefa($dadosTarefa);
            if (!$task) {
                throw new Exception("Erro ao criar tarefa.");
            }
            foreach ($itens as $item) {
                $result = $tarefas->criaItens($task, $item);
                if (!$result) {
                    throw new Exception("Erro ao inserir item da tarefa.");
                }
            }
            $tarefas->CONEXAO->commit();
            return "Nova tarefa gerada com Sucesso!";
        } catch (Exception $e) {
            $tarefas->CONEXAO->rollBack();
            echo "Erro ao criar tarefa: " . $e->getMessage();
            return false;
        }
    }
    

}