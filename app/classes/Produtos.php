<?php 
namespace App\Classes;

use PDO;

class Produtos {
    private $conexao;

    public function __construct() {
        $conexao = new Conexao();
        $this->conexao = $conexao->CONEXAO;
    }

    public function getProduto($cdProd){
        $sql = "SELECT * FROM produtos WHERE cdProd = :cdProd";
        $stmt = $this->conexao->query($sql);
        $stmt->bindParam(':cdProd', $cdProd, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarProdutos() {
        $sql = "SELECT * FROM produtos";
        $stmt = $this->conexao->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarUnidadesArmazenagem($cdProd) {
        $sql = "SELECT * FROM tb_unProdutos WHERE cd_produto = :cdProd";
        $stmt = $this->conexao->prepare($sql);
        $stmt->bindParam(':cdProd', $cdProd, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarUnidadePrincipal($cdProd) {
        $sql = "SELECT * FROM tb_unProdutos WHERE cd_produto = :cdProd AND priority = 'PRINCIPAL'";
        $stmt = $this->conexao->prepare($sql);
        $stmt->bindParam(':cdProd', $cdProd, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            return [
                'qtd' => 100,
                'tipo' => 'PALLET'
            ];
        }

        return $result;
    }

}
