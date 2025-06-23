<?php

namespace App\Classes;
use PDO;
use Exception;
class Tarefas{
    public $CONEXAO;

     public function __construct()
    {
        $conexao = new Conexao();
        $this->CONEXAO = $conexao->CONEXAO;
    }
    //Gera as tarefas com base nos dados de entrada da NF
    public function geraTarefa($dados) {
    $sql = "INSERT INTO tb_tarefas_recebimento (nf, idSupply, nome, situation, created_at)
            VALUES (:nf, :idSupply, :nome, 'PENDENTE', NOW())";

    $stmt = $this->CONEXAO->prepare($sql);
    $stmt->bindParam(':nf', $dados['nf'], PDO::PARAM_INT);
    $stmt->bindParam(':idSupply', $dados['idSupply'], PDO::PARAM_INT);
    $stmt->bindParam(':nome', $dados['nome'], PDO::PARAM_STR);

        if ($stmt->execute()) {
            return $this->CONEXAO->lastInsertId(); 
        } else {
            return false;
        }
    }
    public function listaTodasTarefas(){
        $sql = "SELECT * FROM tb_tarefas_recebimento";
        $stmt = $this->CONEXAO->query($sql);
        $result = $stmt->fetchAll();
        return $result;
    }
    public function listaTarefas($situation){
        $sql = "SELECT * FROM tb_tarefas_recebimento WHERE situation = :situation";
        $stmt = $this->CONEXAO->prepare($sql);
        $stmt->bindParam(':situation', $situation, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetchAll();

        return $result;
    }
    public function criaItens($idTask, $dados) {
        $sql = "INSERT INTO tb_itens_tarefa_recebimento (idTarefa, idPN, idSN, PartNumber, Lote, `Serial`, dFab, dVal, Qty, situation)
                VALUES (:idTarefa, :idPN, :idSN, :PartNumber, :Lote, :Serial, :dFab, :dVal, :Qty, 'PENDENTE')";

        $stmt = $this->CONEXAO->prepare($sql);
        $lastIds = [];
        foreach ($dados as $item) {
            $stmt->bindParam(':idTarefa', $idTask, PDO::PARAM_INT);
            $stmt->bindParam(':idPN', $item['idPN'], PDO::PARAM_INT);
            $stmt->bindParam(':idSN', $item['idSN'], PDO::PARAM_INT);
            $stmt->bindParam(':PartNumber', $item['PartNumber'], PDO::PARAM_STR);
            $stmt->bindParam(':Lote', $item['Lote'], PDO::PARAM_STR);
            $stmt->bindParam(':Serial', $item['Serial'], PDO::PARAM_STR);
            $stmt->bindParam(':dFab', $item['dFab']);
            $stmt->bindParam(':dVal', $item['dVal']);
            $stmt->bindParam(':Qty', $item['Qty'], PDO::PARAM_INT);
            if ($stmt->execute()) {
            $lastIds[] = $this->CONEXAO->lastInsertId();
            } else {
                return false;
            }
        }
    }
    public function atualizaStatus($idTarefa, $situation){
        $sql = "UPDATE tb_tarefas_recebimento SET situation = :situation WHERE id = :idTarefa";
        $stmt = $this->CONEXAO->prepare($sql);
        $stmt->bindParam(':situation', $situation, PDO::PARAM_STR);
        $stmt->bindParam(':idTarefa', $idTarefa, PDO::PARAM_INT);
        if ($stmt->execute()) {
            return $idTarefa;
        } else {
            return false; 
        }
    }       

}