<?php

namespace App\Classes;

use Exception;
use PDO;
class Entradas{
     public $CONEXAO;

     public function __construct()
    {
        $conexao = new Conexao();
        $this->CONEXAO = $conexao->CONEXAO;
    }
    public function listaBinsDisponiveis(){
        $sql = "
            SELECT binName
            FROM tb_bin
            WHERE binName NOT IN (
                SELECT TRIM(BinLocation)
                FROM vw_partbalance1
                WHERE BinLocation IS NOT NULL
                GROUP BY TRIM(BinLocation)
            )
            GROUP BY binName
            ORDER BY 
                CAST(SUBSTRING(binName, 3, 2) AS UNSIGNED) DESC,
                RAND()
         ";
        $bins = $this->CONEXAO->query($sql);
        return $bins->fetchAll(PDO::FETCH_COLUMN); // retorna array com os binNames disponí
        
    }

    public function sugereBin(){
        $bin = $this->listaBinsDisponiveis();
        if(!empty($bin)){   
        return $bin[0];
        }
        echo "Não há Bins Disponíveis";
        return false;
    }
    public function novaSugestaoBin($idSN){
       $binDisponivel = $this->sugereBin();
       $this->atualizaBin($idSN, $binDisponivel);
    }
    public function atualizaBin($idSN, $binName){
        $sql = "UPDATE tb_stockSupplySN SET BinLocation = :binName WHERE idSN = :idSN";
        $stmt = $this->CONEXAO->prepare($sql);
        $stmt->bindParam(':binName', $binName, PDO::PARAM_STR);
        $stmt->bindParam(':idSN', $idSN, PDO::PARAM_INT);
        if ($stmt->execute()) {
            return $idSN;
        } else {
            return false; 
        }
    }

    public function geraQuantidadeSeriais($cdProd, $qtd){
        $produtos = new Produtos();
        $unidadePrincipal = $produtos->buscarUnidadePrincipal($cdProd);
        print_r($unidadePrincipal);
        $qtdUnidade = !empty($unidadePrincipal['qtd']) ? $unidadePrincipal['qtd'] : 100;

        $nSeriais = ceil($qtd / $qtdUnidade);
        return [
            'nSeriais' => $nSeriais,
            'maxQtd' => $unidadePrincipal['qtd'],
            'tipo' => $unidadePrincipal['tipo']
        ];
    }
    public function atribuiQtdSeriais($cdProd, $qtd) {
        $dados = $this->geraQuantidadeSeriais($cdProd, $qtd);
        $lotes = [];
        // Para todos os seriais, exceto o último, usa o maxQtd
        for ($i = 0; $i < $dados['nSeriais'] - 1; $i++) {
            $lotes[] = $dados['maxQtd'];
        }
        $soma = array_sum($lotes);
        $sobra = $qtd - $soma;
        
        if ($sobra > 0) {
            $lotes[] = $sobra;
        }
        return [
            'QtdSeriais' => $lotes,
            'nSeriais' => $dados['nSeriais']
        ];
    }
    public function geraPLBR(){
        $upper = implode('', range('A', 'Z')); // ABCDEFGHIJKLMNOPQRSTUVWXYZ
        $nums = implode('', range(0, 9)); // 0123456789

        $alphaNumeric = $upper.$nums; // ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789
        $string = '';
        $len = 11; // numero de chars
        for($i = 0; $i < $len; $i++) {
            $string .= $alphaNumeric[rand(0, strlen($alphaNumeric) - 1)];
        }
        $string = "PLBR-" . $string;

        return $string;
    }
    public function createDockTime($awb, $user){
        $sql = "INSERT INTO tb_dock (AirWayBill, user, docktime) VALUES (:AirWayBill, :user, date('Y-m-d H:i:s')";

        $stmt = $this->CONEXAO->prepare($sql);
        $stmt->bindParam(':AirWayBill', $awb, PDO::PARAM_STR);
        $stmt->bindParam(':user', $user, PDO::PARAM_STR);

        if ($stmt->execute()) {
            return $awb;
        } else {
            return false; 
        }
    }
    public function createStockSupply($data){
        $sql = "INSERT INTO tb_stockSupply (User, Client, ImpDate, NF, ImportDeclaration, AirWayBill, PlaceSSL, Site, TransportMode, InboundRMA, OrderType, Coments) VALUES (:User, :Client, :ImpDate, :NF, :ImportDeclaration, :AirWayBill, :PlaceSSL, :Site, :TransportMode, :InboundRMA, :OrderType, :Coments)";
        $stmt = $this->CONEXAO->prepare($sql);
        $stmt->bindParam(':User', $data['login'], PDO::PARAM_STR);
        $stmt->bindParam(':Client', $data['client'], PDO::PARAM_STR);
        $stmt->bindParam(':ImpDate', $data['ImpDate'], PDO::PARAM_STR);
        $stmt->bindParam(':NF', $data['NF'], PDO::PARAM_STR);
        $stmt->bindParam(':ImportDeclaration', $data['DI'], PDO::PARAM_STR);
        $stmt->bindParam(':AirWayBill', $data['AWB'], PDO::PARAM_STR);
        $stmt->bindParam(':PlaceSSL', $data['SSL'], PDO::PARAM_STR);
        $stmt->bindParam(':Site', $data['Site'], PDO::PARAM_STR);
        $stmt->bindParam(':TransportMode', $data['TransportMode'], PDO::PARAM_STR);
        $stmt->bindParam(':InboundRMA', $data['InboundRMA'], PDO::PARAM_STR);
        $stmt->bindParam(':OrderType', $data['OrderType'], PDO::PARAM_STR);
        $stmt->bindParam(':Coments', $data['Coments'], PDO::PARAM_STR);
        if ($stmt->execute()) {
            return $this->CONEXAO->lastInsertId();
        } else {
            return false; 
        }
    }
    public function validaItensEntrada($itens){
        $erros = [];

        //Obtenho todos os ID's dos Seriais
        $ids = array_column($itens, 'IdSN');

        if (empty($ids)) {
            throw new Exception("Nenhum IdSN informado.");
        }

        // 2) Montar a query dinâmica com IN
        $inQuery = implode(',', array_fill(0, count($ids), '?'));

        $sql = "SELECT Id, PartNumber, Barcode, SerialNumber, Qty 
                FROM tb_stockSupplySN 
                WHERE Id IN ($inQuery)";

        $stmt = $this->CONEXAO->prepare($sql);

        foreach ($ids as $k => $id) {
            $stmt->bindValue(($k + 1), $id, PDO::PARAM_INT);
        }

        $stmt->execute();
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 3) Indexar por IdSN para lookup rápido
        $dadosBanco = [];
        foreach ($resultados as $row) {
            $dadosBanco[$row['Id']] = $row;
        }

        // 4) Validar tudo em memória
        foreach ($itens as $item) {
            $id = $item['IdSN'];

            if (!isset($dadosBanco[$id])) {
                $erros[] = "Item IdSN $id não encontrado na tabela.";
                continue;
            }

            $registro = $dadosBanco[$id];

            if ($registro['PartNumber'] != $item['PartNumber']) {
                $erros[] = "Item IdSN $id: PartNumber diferente. Esperado: {$item['PartNumber']}, Encontrado: {$registro['PartNumber']}";
            }

            if ($registro['Barcode'] != $item['Barcode']) {
                $erros[] = "Item IdSN $id: Barcode diferente. Esperado: {$item['Barcode']}, Encontrado: {$registro['Barcode']}";
            }

            if ($registro['SerialNumber'] != $item['SerialNumber']) {
                $erros[] = "Item IdSN $id: SerialNumber diferente. Esperado: {$item['SerialNumber']}, Encontrado: {$registro['SerialNumber']}";
            }

            if ($registro['Qty'] != $item['Qty']) {
                $erros[] = "Item IdSN $id: Qty diferente. Esperado: {$item['Qty']}, Encontrado: {$registro['Qty']}";
            }
        }

        if (!empty($erros)) {
            throw new Exception(implode("\n", $erros));
        }

        return true;
    }


}