<?php

namespace App\Classes;

use PDO;
use Exception;
class Faturar
{
  public $servidorbd;
  public $usuariobd;
  public $senhabd;
  public $bancobd;
  public $CONEXAO;

    public function __construct($servidorbd, $usuariobd, $senhabd, $bancobd)
    {
        $this->servidorbd = $servidorbd;
        $this->usuariobd = $usuariobd;
        $this->senhabd = $senhabd;
        $this->bancobd = $bancobd;
        date_default_timezone_set('America/Sao_Paulo');
        try {
            $this->CONEXAO = new PDO("mysql:host=$this->servidorbd;dbname=$this->bancobd", $this->usuariobd, $this->senhabd);
        } catch (Exception $erro) {
            echo "Erro conexão: " . $erro->getMessage();
            exit;
        }
    }
    function gera_pedidoentradaclienteComSerial($id_nfe, $login)
    {
        $sql = "SELECT 
                            temp_nfe_entradas.num_doc AS NF,
                            temp_nfe_entradas.emit_xnome AS `CLIENT`
                             
                    FROM
                            temp_nfe_entradas 
                    WHERE chv_nfe = '" . $id_nfe . "'";
        $res = $this->CONEXAO->query($sql);
        $l = $res->fetch(PDO::FETCH_ASSOC);
        $l['USER'] = $login;
        $l['ImpDate'] = date("Y-m-d H:i:s");
        $l['CLIENT'] = $this->primeironome($l['CLIENT']);

        //$this->debug($l);
        $S = "SELECT 
                            nDI 
                          FROM
                            temp_nfe_entradas_itens 
                          WHERE chv_nfe = '" . $id_nfe . "'  
                          GROUP BY nDi 
                          LIMIT 1";
        $R = $this->CONEXAO->query($S);
        $LD = $R->fetch(PDO::FETCH_ASSOC);
        $l['ImportDeclaration'] = $LD['nDI'];
        //$l['chv_nfe'] = $id_nfe;
      
        $Id = $this->executa_insert($l, $this->campos_tb_stock, 'tb_stockSupply');

        if ($Id > 0) {
          $sql_itens = "SELECT 
                    produtos.PartNumber AS PartNumber,
                    produtos.cd_produto AS Cod_Produto,
                    temp_nfe_entradas_itens.descricao_produto AS Description,
                    participantes.Client AS 'Client',
                    empresas.SSL AS 'SSL',
                    round(temp_nfe_entradas_itens.qtd,0) AS Qty,
                    round(temp_nfe_entradas_itens.vlr_unit,2) AS PriceRS,
                    temp_nfe_entradas_itens.ncm AS NCM,
                    round(temp_nfe_entradas_itens.aliq_icms,0) AS ICMS,
                    round(temp_nfe_entradas_itens.aliq_ipi,0) AS IPI 
                  FROM
                    temp_nfe_entradas_itens 
                    INNER JOIN produtos 
                      ON (
                        temp_nfe_entradas_itens.cod_item = produtos.PartNumber
                      ) 
                    INNER JOIN temp_nfe_entradas
                      ON temp_nfe_entradas_itens.chv_nfe = temp_nfe_entradas.chv_nfe
                    LEFT JOIN participantes
                      ON participantes.cnpj_cpf = temp_nfe_entradas.dest_cpf_cnpj
                    LEFT JOIN empresas
                      ON empresas.cnpj_cpf = temp_nfe_entradas.emit_cnpj_cpf
                  WHERE temp_nfe_entradas_itens.chv_nfe = '" . $id_nfe . "'
                  ORDER BY temp_nfe_entradas_itens.chv_nfe ASC ";
          $res_itens = $this->CONEXAO->query($sql_itens);
          while ($litens = $res_itens->fetch(PDO::FETCH_ASSOC)) {
                $litens['Id'] = $Id;
                $litens['Site'] = 'GOOD';
                if (empty($litens['SSL'])) {
                  $litens['SSL'] = 'VAZIO';
                }
                //$this->debug($litens);
                $idPN = $this->executa_insert($litens, $this->campos_tb_stockitens, 'tb_stockSupplyItens');
                if($idPN > 0){
                  $sqlItens = "
                  SELECT tb_stockSupplyItens.PartNumber, num_lote AS Barcode, data_fab, data_val AS Validity, qtd_lote FROM temp_nfe_entradas 
                  INNER JOIN temp_nfe_entradas_itens ON temp_nfe_entradas_itens.chv_nfe = temp_nfe_entradas.chv_nfe 
                  INNER JOIN tb_stockSupply ON tb_stockSupply.NF = temp_nfe_entradas.num_doc 
                  LEFT JOIN tb_stockSupplyItens ON tb_stockSupplyItens.Id = tb_stockSupply.Id
                  WHERE temp_nfe_entradas_itens.chv_nfe = '" . $id_nfe . "' AND tb_stockSupplyItens.IdPN = " . $idPN ;
                
                  $resPN = $this->CONEXAO->query($sqlItens);
                  while ($produto = $resPN->fetch(PDO::FETCH_ASSOC)) {

                    if (empty($produto['SSL'])) {
                        $produto['SSL'] = 'VAZIO';
                    }
                    if (empty($produto['Barcode'])) {
                        $produto['Barcode'] = '';
                    }
                
                    $produto['IdPN'] = $idPN;
                    $produto['BinLocation'] = 'RECEBIMENTO';
                    $produto['Site'] = 'GOOD';
                
                    // Use 'qtd_lote' ou 'Qty' dependendo do significado correto
                    $produto['SerialNumber'] = $this->geraSN();
                    $this->executa_insert($produto, $this->campos_tb_stockitensSN, 'tb_stockSupplySN');
                  }

                }
            }
              //$up = "UPDATE nfe_cab SET id_Supply = " . $Id . " WHERE id_nfe = " . $id_nfe;
              //$this->CONEXAO->exec($up);
        }
        return $Id;
    }
    public $campos_nfe_cab = array(
      'cd_empresa',
      //'ide_cuf',
      'ide_natOp',
      'ide_mod',
      'ide_serie',
      'ide_nNF',
      'ide_dhEmi',
      'ide_dhSaiEnt',
      'ide_tpNF',
      'ide_idDEst',
      'ide_cMunFG',
      'ide_tp_Imp',
      'ide_tpEmis',
      'ide_tpAmb',
      'ide_finNFe',
      'ide_indFinal',
      'ide_indPres',
      'ide_procEmi',
      'ide_verProc',
      'emit_cnpj_cpf',
      'emit_xNome',
      'emit_xLgr',
      'emit_nro',
      'emit_xBairro',
      'emit_cMun',
      'emit_xMun',
      'emit_UF',
      'emit_CEP',
      'emit_cPais',
      'emit_xPais',
      'emit_fone',
      //  'emit_IE',
      'emit_CRT',
      'dest_xNome',
      'dest_xLgr',
      'dest_nro',
      'dest_xBairro',
      'dest_cMun',
      'dest_xMun',
      'dest_UF',
      //'dest_CEP',
      'dest_cPais',
      'dest_xPais',
      'modFrete',
      'cd_status',
  );
  public $campos_nfe_itens = array(
      'id_nfe',
      'cProd',
      'xProd',
      'infAdProd',
      'ncm',
      'CFOP',
      'uCom',
      'qCom',
      'vUnCom',
      'vProd',
      'uTrib',
      'qTrib',
      'vUnTrib',
      'orig',
      'cst_icms',
      'vBC',
      'pICMS',
      'vICMS',
      'cst_ipi',
      'enq_ipi',
      'vBCIPI',
      'pIPI',
      'vIPI',
      'cst_pis',
      'cst_cofins',
  );
  public $campos_produtos = array(
      'PartNumber',
      'nm_produto',
      'un',
      'orig',
      'ncm',
  );
  public $campos_tb_stock = array(
      'USER',
      'ImpDate',
      'NF',
      'CLIENT',
  );
  public $campos_tb_stockitens = array(
      'Id',
      'Cod_Produto',
      'PartNumber',
      'Description',
      'Client',
      'Site',
      'SSL',
      'Qty',
      'PriceRS',
      'NCM',
      'ICMS',
      'IPI',
  );
public $campos_tb_stockitensSN = array(
    'IdPN',
    'PartNumber',
    'SerialNumber',
    'BinLocation',
    'Qty',
    'SSL',
    'Client',
    'Site',
);
  public $campos_tb_case = array(
      'IdEndClient',
      'WeightTotal',
  );
  public $campos_tb_caseitens = array(
      'Cod_Produto',
      //'PartValue',
      'QTY',
      'PriceRS',
      'ICMS',
      'IPI',
  );
  public $campos_participantes = array(
      'cnpj_cpf',
      //'inscricao_estadual',
      'razao_social',
      'endereco',
      //'numero',
      'cep',
      'cd_cidade',
  );
  public $campos_tb_return = array(
      'ReturnNF',
      'ValorNFS1'
  );
  public $campos_returnitens = array(
      'Idreturn',
      'PartNumber',
      'Qty',
      'Client',
  );
  public $campos_export = array(
      'NF',
      'Total'
  );
  public $campos_exportitens = array(
      'Idinv',
      'PartNumber',
      'Description',
      'Qty',
      'PriceRS',
      'TotalItensRS',
      'Client',
      'NCM',
      'ICMS',
  );

  //==========================================================================
  //  Mostrar array;
  public function debug($array)
  {
      echo "<pre>";
      print_r($array);
      //     echo "<hr>";
      //       var_dump($array);
      exit;
  }

  public function geraSN(){
    $upper = implode('', range('A', 'Z')); // ABCDEFGHIJKLMNOPQRSTUVWXYZ
    $lower = implode('', range('a', 'z')); // ABCDEFGHIJKLMNOPQRSTUVWXYZ
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

  //==========================================================================
  //Dados da empresa
  public function get_empresa($cd_empresa)
  {

      $sql = "SELECT 
                          empresas.cnpj_cpf,
                          empresas.inscricao_estadual,
                          empresas.razao_social,
                          empresas.fantasia,
                          empresas.endereco,
                          empresas.numero,
                          empresas.bairro,
                          empresas.cep,
                          empresas.fone,
                          empresas.cd_cidade,
                          cidades.nm_cidade,
                          cidades.cd_estado,
                          estados.uf,
                          estados.nm_estado,
                          estados.cd_pais,
                          paises.nm_pais,
                          empresas.email,
                          empresas.crt,
                          empresas.email_smtp,
                          empresas.email_usuario,
                          empresas.email_senha,
                          empresas.email_porta,
                          empresas.tokenIBPT,
                          empresas.tpAmb,
                          empresas.fusohorario 
                        FROM
                          empresas 
                          INNER JOIN cidades 
                            ON (
                              empresas.cd_cidade = cidades.cd_cidade
                            ) 
                          INNER JOIN estados 
                            ON (
                              cidades.cd_estado = estados.cd_estado
                            ) 
                          INNER JOIN paises 
                            ON (estados.cd_pais = paises.cd_pais) 
                        WHERE empresas.cd_empresa =  " . $cd_empresa;
      $res = $this->CONEXAO->query($sql);
      $nr = $res->rowCount();
      if ($nr == 0) {
          echo "<h4>Empresa não localizada! $cd_empresa</h4>";
      }
      return $res->fetch(PDO::FETCH_ASSOC);
  }

  //=========================================================================
  //  Monta temp_nfe_entradas => nfe_cab
  public function temp_entrada($chave, $rotina_origem = NULL)
  {

      $sql = "SELECT 
                      cd_empresa AS cd_empresa,
                      ind_oper AS ide_tpNF,
                      serie AS ide_serie,
                      num_doc AS ide_nNF,
                      idDest  AS ide_idDEst,
                      indFinal   AS ide_indFinal,     
                      dt_doc AS ide_dhEmi,
                      dt_e_s AS ide_dhSaiEnt,
                      vl_doc AS vNF,
                      vl_desc AS vDesc,
                      vl_merc AS vProd,
                      vl_frt AS vFrete,
                      vl_seg AS vSeg,
                      vl_out_da AS vOutro,
                      vl_bc_icms AS vBC,
                      vl_icms AS vICMS,
                      vl_bc_icms_st AS vBCST,
                      vl_icms_st AS vST,
                      vl_ipi AS vIPI,
                      vl_pis AS vPIS,
                      vl_cofins AS vCOFINS,
                      resumo_cfop AS ide_natOp,
                      qvol AS vol_qVol,
                      esp AS vol_esp,
                      marca AS vol_marca,
                      nvol AS vol_nVol,
                      pesol AS vol_pesoL,
                      pesob AS vol_pesoB,
                      inf_fisco AS infAdFisco,
                      inf_comp AS infCpl,
                      cd_transportador AS id_transp,
                      emit_cnpj_cpf AS emit_cnpj_cpf,
                      emit_xnome AS emit_xNome,
                      emit_xfant AS emit_xFant,
                      emit_xlgr AS emit_xLgr,
                      emit_nro AS emit_nro,
                      emit_cnae AS emit_CNAE,
                      emit_xbairro AS emit_xBairro,
                      emit_cmun AS emit_cMun,
                      emit_xmun AS emit_xMun,
                      emit_uf AS emit_UF,
                      emit_cep AS emit_CEP,
                      emit_cpais AS emit_cPais,
                      emit_xpais AS emit_xPais,
                      emit_fone AS emit_fone,
                      emit_ie AS emit_IE,
                      emit_crt AS emit_CRT,
                      transp_cnpj AS transp_cnpj_cpf,
          transp_xNome AS transp_xNome,
          transp_IE AS transp_IE,
          transp_xEnder AS transp_xEnder,
          transp_xMun AS transp_xMun,
          transp_UF AS transp_UF,
                      placa AS veic_placa,
                      modfrete AS modFrete,
                      dest_cpf_cnpj AS dest_cnpj_cpf,
                      dest_IE AS dest_IE,
                      dest_xNome AS dest_xNome,
                      dest_xLgr AS dest_xLgr,
                      dest_nro AS dest_nro,
                      dest_xBairro AS dest_xBairro,
                      dest_cMun AS dest_cMun,
                      dest_xMun AS dest_xMun,
                      dest_UF AS dest_UF,
                      dest_cPais AS dest_cPais,
                      dest_xPais AS dest_xPais,
                      dest_indIEDest AS dest_indIEDest,
                      dest_CEP AS dest_CEP,
                      dest_fone AS dest_fone,
                      dest_email AS dest_email,
                      '1' AS cd_status  ,
                      '55' AS ide_mod,
          vii
                      FROM
                              temp_nfe_entradas 
                      WHERE chv_nfe = '" . $chave . "'
                         LIMIT 1";
      $res = $this->CONEXAO->query($sql);
      $nr = $res->rowCount(); //Conta números de registros
      $lcab = $res->fetch(PDO::FETCH_ASSOC);
      //$this->debug($lcab);
      if ($nr == 0) {
          echo "<h4>Nota não encontrada Chave: " . $chave . "</h4>";
          exit;
      }
      //$this->debug($lcab);
      if ($lcab['cd_empresa'] == 0) {
          echo "<h4>Empresa $cd_empresa não localizada!</h4>";
          exit;
      }
      $empresa = $this->get_empresa($lcab['cd_empresa']);
      $lcab['ide_tpNF'] = '0'; //0=Entrada; 1=Saída
      $lcab['ide_cMunFG'] = $lcab['emit_cMun'];
      $lcab['ide_tp_Imp'] = '1'; //1=DANFE normal, Retrato; 2=DANFE normal, Paisagem;
      $lcab['ide_tpEmis'] = '1';
      $lcab['ide_tpAmb'] = $empresa['tpAmb'];
      $lcab['ide_finNFe'] = '1'; // 1=NF-e normal; 2=NF-e complementar; 3=NF-e de ajuste; 4=Devolução de mercadoria.
      //$lcab['ide_indFinal'] = 0;
      // $lcab['ide_idDEst'] = $this->Acha_idDest($lcab['emit_UF'], $lcab['dest_UF'], $lcab['emit_cPais']);
      if ($lcab['dest_indIEDest'] == '3') {
          $lcab['dest_cMun'] = '9999999';
          $lcab['dest_xMun'] = 'EXTERIOR';
          $lcab['dest_UF'] = 'EX';
      }
      $lcab['ide_indPres'] = '9';
      $lcab['ide_procEmi'] = '0'; //0=Emissão de NF-e com aplicativo do contribuinte;
      $lcab['ide_verProc'] = 'Place 4.00';
      if ($lcab['dest_nro'] == '') {
          $lcab['dest_nro'] = 'SN';
      }
      if ($rotina_origem != '') {
          $lcab['rotina_origem'] = $rotina_origem;
      }

      $lcab['ide_tpAmb'] = $empresa['tpAmb'];
      $lcab['vii'] = $lcab['vii'];
  
      $ret = $this->acha_serie($lcab['cd_empresa'], '55', $lcab['ide_tpAmb'], $lcab['ide_serie']);
      $lcab['ide_nNF']  = $ret['doc'];
  
      //$this->debug($lcab); 
      $id_nfe = $this->executa_insert($lcab, $this->campos_nfe_cab, 'nfe_cab');

      if ($id_nfe > 0) {
          //itens
          $this->temp_nfe_entradas_itens($chave, $id_nfe, $lcab['dest_xNome']);
      }
      $empresa = $this->get_empresa($lcab['cd_empresa']);
      $this->atualiza_nrnf($lcab['cd_empresa'], '55', $lcab['ide_nNF'], $empresa['tpAmb'], $lcab['ide_serie']);

      return $id_nfe;
  }

  //=======================================================================
  public function resposta($var, $tabela, $array_dados)
  {
      echo "<h4>Erro validar campos $tabela </h4>";
      foreach ($var as $value) {
          echo $value . "<br>";
      }
      $this->debug($array_dados);
      exit;
  }

  //=========================================================================
  public function valida_obrigatorios($array_dados, $campos_obrigatorios)
  {
      $erros = array();
      foreach ($campos_obrigatorios as $key => $value) {
          if (!array_key_exists($value, $array_dados)) {
              array_push($erros, $value . " - obrigatório!");
          } else {
              if ($array_dados[$value] == '' and $array_dados[$value] != '0') {
                  array_push($erros, $value . " - vazio!");
              }
          }
      }

      return $erros;
  }

  //========================================================================
  function executa_insert($array_dados, $campos_obrigatorios, $tabela)
  {
      $erros = $this->valida_obrigatorios($array_dados, $campos_obrigatorios);
  
      if (count($erros) > 0) {
          $this->resposta($erros, $tabela, $array_dados);
      }
      foreach ($array_dados as $key => $value) {
          if ($value == '' and $value != 0) {
              unset($array_dados[$key]);
          }
      }
      $insert_fields = array();
      foreach ($array_dados as $key1 => $value1) {
          $insert_fields[$key1] = "'" . $value1 . "'";
      }
      
        $insert_sql = 'INSERT INTO ' . $tabela
        . ' (' . implode(', ', array_keys($insert_fields)) . ')'
        . ' VALUES (' . implode(', ', array_values($insert_fields)) . ')';
        echo "<p>" . $insert_sql . "</p>";
      
      return $this->insert($tabela, $array_dados);
  }

  //==========================================================================
  public function insert($insert_table, $insert_fields)
  {
    $columns = array_map(function($col) {
      return "`" . $col . "`";
  }, array_keys($insert_fields));
  
  $placeholders = array_map(function($col) {
      return ":" . $col;
  }, array_keys($insert_fields));
  
  $insert_sql = "INSERT INTO `" . $insert_table . "`"
  . " (" . implode(", ", $columns) . ")"
  . " VALUES (" . implode(", ", $placeholders) . ")";

  $id = 0;
  try {
    $this->CONEXAO->beginTransaction();

    $exec = $this->CONEXAO->prepare($insert_sql);
    $ok = $exec->execute($insert_fields);

    if (!$ok) {
      // Se o insert falhou mas não lançou exceção
      $errorInfo = $exec->errorInfo();
      echo "<h4>Erro ao executar insert na tabela $insert_table</h4>";
      echo "<pre>";
      echo "Mensagem PDO:\n";
      print_r($errorInfo);
      echo "SQL:\n$insert_sql\n";
      echo "Parâmetros:\n";
      print_r($insert_fields);
      echo "</pre>";
      $this->CONEXAO->rollBack();
      exit;
    }

    $res = $this->CONEXAO->query("SELECT LAST_INSERT_ID() AS id");
    $l = $res->fetch(PDO::FETCH_ASSOC);
    $id = $l['id'];

    $this->CONEXAO->commit();

  } catch (Exception $erro) {
    echo "<h4>Exceção ao inserir Tabela $insert_table => " . $erro->getMessage() . "</h4>";
    echo "<pre>";
    echo "SQL:\n$insert_sql\n";
    echo "Parâmetros:\n";
    print_r($insert_fields);
    echo "</pre>";
    $this->CONEXAO->rollBack();
    exit;
  }

  return $id;
}

  //==========================================================================
  //Monta temp_nfe_entradas_itens => nfe_itens
  public function temp_nfe_entradas_itens($chave, $id_nfe, $dest_xNome)
  {

      $sql = "SELECT 
                          cod_item AS cProd,
                          descricao_produto AS xProd,
                          descr_compl AS infAdProd,
                          ncm AS ncm,
                          cest AS cest,
                          cfop AS CFOP,
                          unid AS uCom,
                          qtd AS qCom,
                          vlr_unit AS vUnCom,
                          vl_item AS vProd,
                          ean AS cEANTrib,
                          vfrete AS vFrete,
                          vl_desc AS vSeg,
                          vseg AS vSeg,
                          despesa_acessoria AS vOutro,
                          xPed AS xPed,
                          nItemPed AS nItemPed,
                          orig AS orig,
                          cst_icms AS cst_icms,
                          mod_bc AS modBC_icms,
                          vl_bc_icms AS vBC,
                          aliq_icms AS pICMS,
                          vl_icms AS vICMS,
                          cst_ipi AS cst_ipi,
                          cod_enq AS enq_ipi,
                          vl_bc_ipi AS vBCIPI,
                          aliq_ipi AS pIPI,
                          vl_ipi AS vIPI,
                          cst_pis AS cst_pis,
                          vl_bc_pis AS vBCPIS,
                          aliq_pis AS pPIS,
                          vl_pis AS vPIS,
                          cst_cofins AS cst_cofins,
                          vl_bc_cofins AS vBCCOFINS,
                          aliq_cofins AS pCOFINS,
                          vl_cofins AS vCOFINS,
                          nDI AS nDI,
                          dDI AS dDI,
                          xLocDesemb AS xLocDesemb,
                          UFDesemb AS UFDesemb,
                          dDesemb AS dDesemb,
                          tpViaTransp AS tpViaTransp,
                          vAFRMM AS vAFRMM,
                          tpIntermedio AS tpIntermedio,
                          cExportador AS cExportador,
                          nAdicao AS nAdicao,
                          nSeqAdic AS nSeqAdic,
                          cFabricante AS cFabricante,
            IIvBC,
            vDespAdu,
            vII,
            vIOF
                        FROM
                          temp_nfe_entradas_itens 
                        WHERE chv_nfe = '" . $chave . "' 
                        ORDER BY num_item ";
      $res = $this->CONEXAO->query($sql);
      $nr = $res->rowCount(); //Conta números de registros
      while ($l = $res->fetch(PDO::FETCH_ASSOC)) {
          $l['id_nfe'] = $id_nfe;
          $l['dest_xNome'] = $dest_xNome;
          $l['PartNumber'] = $l['cProd'];
          $l['cProd'] = $this->encontra_produto($l);

          $l['uTrib'] = $l['uCom'];
          $l['uTrib'] = $l['uCom'];
          $l['qTrib'] = $l['qCom'];
          $l['vUnTrib'] = $l['vUnCom'];

          unset($l['dest_xNome']);
          //$this->debug($l);
          $id = $this->executa_insert($l, $this->campos_nfe_itens, 'nfe_itens');
      }
  }

  //==========================================================================
  //Verifica produto
  public function encontra_produto($litens)
  {

      $Prod = array();
      $sql = "SELECT 
                      cd_produto 
                 FROM
                  produtos 
                WHERE PartNumber = '" . $litens['cProd'] . "' ";
      $res = $this->CONEXAO->query($sql);
      $nr = $res->rowCount(); //Conta números de registros
      $l = $res->fetch(PDO::FETCH_ASSOC);
      if ($nr == 0) {
          $Prod['PartNumber'] = $litens['cProd'];
          $Prod['nm_produto'] = $litens['xProd'];
          $Prod['un'] = $litens['uCom'];
          $Prod['orig'] = $litens['orig'];
          $Prod['Client'] = $litens['dest_xNome'];
          $Prod['ncm'] = $litens['ncm'];
          $Prod['ICMS'] = $litens['pICMS'];
          $Prod['IPI'] = $litens['pIPI'];
          //$this->debug($Prod);
          $cd_produto = $this->executa_insert($Prod, $this->campos_produtos, 'produtos');
      } else {
          $cd_produto = $l['cd_produto'];
      }
      return $cd_produto;
  }

  //==========================================================================
  //Gera pedido nfe entrada
  public function gera_pedidoentrada($id_nfe, $login)
  {
      $sql = "SELECT 
                          nfe_cab.ide_nNF AS NF,
                          nfe_cab.dest_xNome AS `CLIENT`,
                          nfe_cab.transp_xNome AS TransportMode 
                  FROM
                          nfe_cab 
                  WHERE id_nfe = " . $id_nfe;
      $res = $this->CONEXAO->query($sql);
      $l = $res->fetch(PDO::FETCH_ASSOC);
      $l['USER'] = $login;
      $l['ImpDate'] = date("Y-m-d H:i:s");
      $l['CLIENT'] = $this->primeironome($l['CLIENT']);
      $S = "SELECT 
                          nDI 
                        FROM
                          nfe_itens 
                        WHERE id_nfe = " . $id_nfe . "  
                        GROUP BY nDi 
                        LIMIT 1";
      $R = $this->CONEXAO->query($S);
      $LD = $R->fetch(PDO::FETCH_ASSOC);
      $l['ImportDeclaration'] = $LD['nDI'];
      $l['id_nfe'] = $id_nfe;
      //$this->debug($l);
      $Id = $this->executa_insert($l, $this->campos_tb_stock, 'tb_stockSupply');

      if ($Id > 0) {
          $sql_itens = "SELECT 
                                  nfe_itens.cProd AS Cod_Produto,
                                  produtos.PartNumber,
                                  nfe_itens.xProd AS Description,
                                  round(nfe_itens.qCom,0) AS Qty,
                                  round(nfe_itens.vUnCom,2) AS PriceRS,
                                  nfe_itens.ncm AS NCM,
                                  round(nfe_itens.pICMS,0) AS ICMS,
                                  round(nfe_itens.pIPI,0) AS IPI 
                                FROM
                                  nfe_itens 
                                  INNER JOIN produtos 
                                    ON (
                                      nfe_itens.cProd = produtos.cd_produto
                                    ) 
                                WHERE nfe_itens.id_nfe = " . $id_nfe . "
                                ORDER BY nfe_itens.idnfe_itens ASC ";
          $res_itens = $this->CONEXAO->query($sql_itens);
          while ($litens = $res_itens->fetch(PDO::FETCH_ASSOC)) {
              $litens['Id'] = $Id;
              //$this->debug($litens);
              $this->executa_insert($litens, $this->campos_tb_stockitens, 'tb_stockSupplyItens');
          }
          $up = "UPDATE nfe_cab SET id_Supply = " . $Id . " WHERE id_nfe = " . $id_nfe;
          $this->CONEXAO->exec($up);
      }
      return $Id;
  }

//==========================================================================
  //  Emite NF-e => tb_case
  public function fatura_tb_case($Idcase, $cd_empresa, $modFrete, $serie)
  {
      $id_nfe = 0;
      $sql = "SELECT 
                      IdEndClient AS cd_participante,
                      VolumeTotal AS vol_qVol,
                      WeightTotal AS vol_pesoB,
                      WeightNet AS vol_pesoL,
                      tb_siteClient.depositorRegime AS depositorRegime,
                      DelivType AS transp_xNome,
                      participantes.cnpj_cpf AS dest_cnpj_cpf,
                      participantes.razao_social AS dest_xNome,
                      participantes.endereco AS dest_xLgr,
                      participantes.numero AS dest_nro,
                      participantes.complemento AS dest_xCpl,
                      participantes.bairro AS dest_xBairro,
                      participantes.cd_cidade AS dest_cMun,
          participantes.tipoFaturamento,
                      cidades.nm_cidade AS dest_xMun,
                      cidades.cd_estado AS dest_UF,
                      estados.uf AS ide_cuf,
                      participantes.cep AS dest_CEP,
                      estados.cd_pais AS dest_cPais,
                      paises.nm_pais AS dest_xPais,
                      participantes.fone AS dest_fone,
                      participantes.indIEDest AS dest_indIEDest,
                      participantes.inscricao_estadual AS dest_IE,
                      participantes.email AS dest_email,
                      tb_case.cfop AS CFOP,
                      cfop.nm_cfop AS ide_natOp
                    FROM
                      tb_case 
                      INNER JOIN participantes 
                        ON (
                          tb_case.IdEndClient = participantes.cd
                        ) 
          INNER JOIN tb_siteClient
           ON (
             participantes.Client = tb_siteClient.Client
           )
                      INNER JOIN cidades 
                        ON (
                          participantes.cd_cidade = cidades.cd_cidade
                        ) 
                      INNER JOIN estados 
                        ON (
                          cidades.cd_estado = estados.cd_estado
                        ) 
                      INNER JOIN paises 
                        ON (estados.cd_pais = paises.cd_pais) 
                      INNER JOIN cfop 
                        ON (tb_case.cfop = cfop.cfop)   
                    WHERE Idcase = " . $Idcase;
      $res = $this->CONEXAO->query($sql);
      $nr = $res->rowCount();
      if ($nr == 0) {
          echo "<h4>Cliente ou tb_case não encontrado!</h4>";
          exit;
      }

      $l = $res->fetch(PDO::FETCH_ASSOC);
      // $this->debug($l);
      $l['cd_status'] = 1;
      $l['cd_empresa'] = $cd_empresa;
      $l['ide_mod'] = '55';
      if ($l['dest_nro'] == '') {
          $l['dest_nro'] = 'SN';
      }
      $l['modFrete'] = $modFrete;
      $empresa = $this->get_empresa($cd_empresa);
      $l['emit_cnpj_cpf'] = $empresa['cnpj_cpf'];
      $l['emit_xNome'] = $empresa['razao_social'];
      $l['emit_xFant'] = $empresa['fantasia'];
      $l['emit_xLgr'] = $empresa['endereco'];
      $l['emit_nro'] = $empresa['numero'];
      $l['emit_xBairro'] = $empresa['bairro'];
      $l['emit_cMun'] = $empresa['cd_cidade'];
      $l['emit_xMun'] = $empresa['nm_cidade'];
      $l['emit_UF'] = $empresa['cd_estado'];
      $l['emit_CEP'] = $empresa['cep'];
      $l['emit_cPais'] = $empresa['cd_pais'];
      $l['emit_xPais'] = $empresa['nm_pais'];
      $l['emit_fone'] = $empresa['fone'];
      $l['emit_IE'] = $empresa['inscricao_estadual'];
      $l['emit_CRT'] = $empresa['crt'];
      $l['ide_dhEmi'] = date("Y-m-d H:i:s");
      $l['ide_dhSaiEnt'] = date("Y-m-d H:i:s");
      $l['ide_tpNF'] = '1'; //0=Entrada; 1=Saída
      $l['ide_idDEst'] = '';
      $l['ide_cMunFG'] = $l['emit_cMun'];
      $l['ide_tp_Imp'] = '1'; //1=DANFE normal, Retrato; 2=DANFE normal, Paisagem;
      $l['ide_tpEmis'] = '1';
      $l['ide_tpAmb'] = $empresa['tpAmb'];
      $l['ide_finNFe'] = '1'; // 1=NF-e normal; 2=NF-e complementar; 3=NF-e de ajuste; 4=Devolução de mercadoria.
      $l['ide_indFinal'] = 0;
      $l['ide_idDEst'] = $this->Acha_idDest($l['emit_UF'], $l['dest_UF'], $l['emit_cPais']);
      if ($l['dest_indIEDest'] == '3') {
          $l['dest_cMun'] = '9999999';
          $l['dest_xMun'] = 'EXTERIOR';
          $l['dest_UF'] = 'EX';
      }
      $l['ide_indPres'] = '9';
      $l['ide_procEmi'] = '0'; //0=Emissão de NF-e com aplicativo do contribuinte;
      $l['ide_verProc'] = 'Place 4.00';

      $acha_cfop = $this->acha_serie($cd_empresa, $l['ide_mod'], $empresa['tpAmb'], $serie);
      if ($acha_cfop['nr'] == 0) {
          echo "<h4>Falta parâmetro na tabela series_uso. Empresa: " . $cd_empresa . " Modelo: " . $l['ide_mod'] . " Ambiente " . $empresa['tpAmb'] . "</h4>";
          exit;
      }
      $l['ide_serie'] = $acha_cfop['serie'];
      $l['ide_nNF'] = $acha_cfop['doc'];
      $l['Idcase'] = $Idcase;
      $l['dest_CEP'] = $this->tratar($l['dest_CEP']);
      $CFOP = $l['CFOP'];
      unset($l['CFOP']);
      //$this->debug($empresa);
      $tipoFatura = $l['tipoFaturamento'];
      $tipoDepositario = $l['depositorRegime'];
  if($tipoFatura == 'LOTE' && $tipoDepositario == 'ARMAZEM'){
    $tableRef = 'tb_stockSupplyItens';
  }else{
    $tableRef = 'tb_caseitens';
  }
  unset($l['tipoFaturamento'], $l['depositorRegime']);

      $id_nfe = $this->executa_insert($l, $this->campos_nfe_cab, 'nfe_cab');

      if ($id_nfe > 0) {
          $sql_itens = "SELECT 
                          tb_caseitens.Cod_Produto AS cProd,
             produtos.nm_produto AS base_xProd,
            tb_caseitensSN.Barcode AS barcode,
            tb_caseitensSN.SerialNumber AS Serial,
            tb_caseitensSN.Validity AS Validity,
            tb_stockSupply.NF AS nf_entrada,
                          produtos.PartNumber,
                          produtos.ncm AS ncm,
                          produtos.un AS uCom,
                          produtos.un AS uTrib,
                          produtos.orig,
                          tb_caseitensSN.Qtd AS qCom,
                          tb_caseitensSN.Qtd AS qTrib,
                          $tableRef.PriceRS AS vUnCom,
                          $tableRef.PriceRS AS vUnTrib,
                          ROUND (tb_caseitensSN.Qtd * $tableRef.PriceRS, 2) AS vProd,
                          ROUND (tb_caseitensSN.Qtd * $tableRef.PriceRS, 2) AS vBC,
                          tb_caseitens.ICMS AS pICMS,
                          ROUND (tb_caseitensSN.Qtd * $tableRef.PriceRS * (tb_caseitens.ICMS / 100), 2) AS vICMS,
                          ROUND(tb_caseitensSN.Qtd * $tableRef.PriceRS, 2) AS vBCIPI,
                          tb_caseitens.IPI AS pIPI,
                          ROUND(tb_caseitensSN.Qtd * $tableRef.PriceRS * (tb_caseitens.IPI / 100), 2) AS vIPI
                      FROM tb_caseitens
                      INNER JOIN produtos ON tb_caseitens.Cod_Produto = produtos.cd_produto
                      INNER JOIN tb_caseitensSN ON tb_caseitensSN.IdSN = tb_caseitens.Id
                      LEFT JOIN tb_stockSupplySN ON tb_stockSupplySN.Barcode = tb_caseitensSN.Barcode 
                          AND tb_stockSupplySN.PartNumber = tb_caseitensSN.PartNumber 
                          AND tb_stockSupplySN.SerialNumber = tb_caseitensSN.SerialNumber
                      LEFT JOIN tb_stockSupplyItens ON tb_stockSupplyItens.IdPN = tb_stockSupplySN.IdPN
                      LEFT JOIN tb_stockSupply ON tb_stockSupply.Id = tb_stockSupplyItens.Id
                      WHERE tb_caseitens.Idcase = " . $Idcase . " 
                      ORDER BY tb_caseitens.Id ASC";
          
    
          $res_itens = $this->CONEXAO->query($sql_itens);
    //$this->debug($sql_itens);

          while ($li = $res_itens->fetch(PDO::FETCH_ASSOC)) {
              //$this->debug($li);
      $partesDescricao = [];

      if (!empty($li['nf_entrada'])) {
        $partesDescricao[] = 'NF Entrada: ' . $li['nf_entrada'];
      }

      if (!empty($li['Serial'])) {
        $partesDescricao[] = 'Serial Number: ' . $li['Serial'];
      }

      if (!empty($li['barcode'])) {
        $partesDescricao[] = 'Lote: ' . $li['barcode'];
      }

      if (!empty($li['Validity'])) {
        $partesDescricao[] = 'Validade: ' . date('d/m/Y', strtotime($li['Validity']));
      }

      $descricao = implode(' | ', $partesDescricao);

      
       $li['xProd'] = $li['base_xProd'];
        $li['infAdProd'] = $descricao;
              $li['vBC'] = $li['vBC'] + $li['vIPI'];
              $li['vICMS'] = round(($li['vBC'] * ($li['pICMS'] / 100)), 2);
              $li['id_nfe'] = $id_nfe;
              $li['CFOP'] = $CFOP;
              $li['cst_icms'] = '00';
              $li['cst_pis'] = '07';
              $li['cst_cofins'] = '07';
              $li['cst_ipi'] = '50';
              $li['enq_ipi'] = '999';
      unset($li['base_xProd'], $li['barcode'], $li['Validity'], $li['nf_entrada'], $li['Serial']);
               //$this->inserirItemNFE($li);
      $this->executa_insert($li, $this->campos_nfe_itens, 'nfe_itens');
      

      
          }
          $total = $this->calcula_total($id_nfe);
          $this->CONEXAO->exec("UPDATE tb_case SET 
                                                  id_nfe  = " . $id_nfe . ", 
                                                  NFeValue = '" . $total . "', 
                                                  NFs = '" . $l['ide_nNF'] . "'
                            WHERE Idcase = " . $Idcase);
      }
      if ($id_nfe > 0) {
          $this->atualiza_nrnf($cd_empresa, $l['ide_mod'], $l['ide_nNF'], $empresa['tpAmb'], $serie);
      }
      return $id_nfe;
  }

public function inserirItemNFE(array $dados)
{
  // Verifica se todos os campos obrigatórios estão presentes
  $camposObrigatorios = array_merge($this->campos_nfe_itens, $this->campos_produtos);
  print_r($camposObrigatorios);
  exit;
  $camposFaltando = [];

  foreach ($camposObrigatorios as $campo) {
    if (!isset($dados[$campo]) || $dados[$campo] === '') {
      $camposFaltando[] = $campo;
    }
  }

  if (!empty($camposFaltando)) {
    echo "Erro: Os seguintes campos obrigatórios estão faltando ou vazios: " . implode(', ', $camposFaltando);
    return false;
  }

  // Monta a query dinamicamente
  $colunas = implode(', ', $camposObrigatorios);
  $placeholders = ':' . implode(', :', $camposObrigatorios);

  $sql = "INSERT INTO nfe_itens ($colunas) VALUES ($placeholders)";

  try {
    $stmt = $this->CONEXAO->prepare($sql);

    // Vincula os parâmetros dinamicamente
    foreach ($camposObrigatorios as $campo) {
      $stmt->bindValue(':' . $campo, $dados[$campo]);
    }

    $stmt->execute();
    echo "Item inserido com sucesso.";
    return true;
  } catch (PDOException $e) {
    echo "Erro ao inserir item: " . $e->getMessage();
    return false;
  }
}

  // ======== INICIO FUNCAO FERNANDO  ====================================================================================
  //==========================================================================
  //  Emite NF-e Armazenagem => tb_case

public function fatura_ret_armazenagem($Idcase, $cd_empresa, $modFrete, $serie)
  {
    $id_nfe = 0;
    $sql = "SELECT 
            IdEndClient AS cd_participante,
            VolumeTotal AS vol_qVol,
            WeightTotal AS vol_pesoB,
            WeightNet AS vol_pesoL,
            tb_siteClient.depositorRegime AS depositorRegime,
            DelivType AS transp_xNome,
            NFs       AS ide_nNF,
            Comments AS dest_xCpl,              
            tb_stockSupply.NF AS dest_email,
            participantes.cnpj_cpf AS dest_cnpj_cpf,
            participantes.razao_social AS dest_xNome,
            participantes.endereco AS dest_xLgr,
            participantes.numero AS dest_nro,
            participantes.bairro AS dest_xBairro,
            participantes.cd_cidade AS dest_cMun,
            participantes.tipoFaturamento,
            cidades.nm_cidade AS dest_xMun,
            cidades.cd_estado AS dest_UF,
            estados.uf AS ide_cuf,
            participantes.cep AS dest_CEP,
            estados.cd_pais AS dest_cPais,
            paises.nm_pais AS dest_xPais,
            participantes.fone AS dest_fone,
            participantes.indIEDest AS dest_indIEDest,
            participantes.inscricao_estadual AS dest_IE,
            tb_case.cfop AS CFOP,
            cfop.nm_cfop AS ide_natOp
            FROM
            tb_case 
            INNER JOIN participantes 
              ON ( tb_case.IdEndClient = participantes.cd ) 
            INNER JOIN cidades 
              ON ( participantes.cd_cidade = cidades.cd_cidade) 
            INNER JOIN estados 
              ON ( cidades.cd_estado = estados.cd_estado) 
            INNER JOIN paises 
              ON (estados.cd_pais = paises.cd_pais) 
            INNER JOIN cfop 
              ON (tb_case.cfop = cfop.cfop)
            INNER JOIN tb_siteClient
              ON (
              participantes.Client = tb_siteClient.Client
              )
            INNER JOIN tb_caseitens 
              ON (tb_case.Idcase = tb_caseitens.Idcase)     
            INNER JOIN tb_caseitensSN 
              ON (tb_caseitens.Id = tb_caseitensSN.IdSN)                           
            INNER JOIN tb_stockSupplySN 
              ON (tb_caseitensSN.SerialNumber = tb_stockSupplySN.SerialNumber)                          
            INNER JOIN tb_stockSupplyItens 
              ON (tb_stockSupplySN.IdPN = tb_stockSupplyItens.IdPN)                           
            INNER JOIN tb_stockSupply 
              ON (tb_stockSupplyItens.Id = tb_stockSupply.Id)                             

            WHERE tb_case.Idcase =" . $Idcase;
    $res = $this->CONEXAO->query($sql);
    $l = $res->fetch(PDO::FETCH_ASSOC);
    $l['cd_status'] = 1;
    $l['cd_empresa'] = $cd_empresa;
    $l['ide_mod'] = '55';
    if ($l['dest_nro'] == '') {
      $l['dest_nro'] = 'SN';
    }
    $l['modFrete'] = $modFrete;
    $empresa = $this->get_empresa($cd_empresa);
    $l['emit_cnpj_cpf'] = $empresa['cnpj_cpf'];
    $l['emit_xNome'] = $empresa['razao_social'];
    $l['emit_xFant'] = $empresa['fantasia'];
    $l['emit_xLgr'] = $empresa['endereco'];
    $l['emit_nro'] = $empresa['numero'];
    $l['emit_xBairro'] = $empresa['bairro'];
    $l['emit_cMun'] = $empresa['cd_cidade'];
    $l['emit_xMun'] = $empresa['nm_cidade'];
    $l['emit_UF'] = $empresa['cd_estado'];
    $l['emit_CEP'] = $empresa['cep'];
    $l['emit_cPais'] = $empresa['cd_pais'];
    $l['emit_xPais'] = $empresa['nm_pais'];
    $l['emit_fone'] = $empresa['fone'];
    $l['emit_IE'] = $empresa['inscricao_estadual'];
    $l['emit_CRT'] = $empresa['crt'];
    $l['ide_dhEmi'] = date("Y-m-d H:i:s");
    $l['ide_dhSaiEnt'] = date("Y-m-d H:i:s");
    $l['ide_tpNF'] = '1'; //0=Entrada; 1=Saída
    $l['ide_idDEst'] = '';
    $l['ide_cMunFG'] = $l['emit_cMun'];
    $l['ide_tp_Imp'] = '1'; //1=DANFE normal, Retrato; 2=DANFE normal, Paisagem;
    $l['ide_tpEmis'] = '1';
    $l['ide_tpAmb'] = $empresa['tpAmb'];
    $l['ide_finNFe'] = '1'; // 1=NF-e normal; 2=NF-e complementar; 3=NF-e de ajuste; 4=Devolução de mercadoria.
    $l['infAdFisco'] = 'RETORNO SIMBOLICO DE MERCADORIA DEPOSITADA EM DEPOSITO FECHADO OU ARMAZEM-GERAL - REF ENTRADA NF: ' . $l['dest_email'] . ' - ENTREGA REF A NF: ' . $l['ide_nNF'] . ' - ' . 'RAZAO SOCIAL: ' . $l['dest_xCpl'];
    $l['infCpl'] = 'ICMS: NA REMESSA: NAO INCIDENCIA NOS TERMOS DO ART. 7 INC. I DO DECRETO N.45.490/000- RICMS/SP - IPI: SUSPENSAO DO IPI NOS TERMOS DO ARTIGO 43, INCISO III DO DECRETO 7212 / 2010- RIPI';
    $l['ide_indFinal'] = 0;
    $l['ide_idDEst'] = $this->Acha_idDest($l['emit_UF'], $l['dest_UF'], $l['emit_cPais']);
    if ($l['dest_indIEDest'] == '3') {
      $l['dest_cMun'] = '9999999';
      $l['dest_xMun'] = 'EXTERIOR';
      $l['dest_UF'] = 'EX';
    }
    $l['ide_indPres'] = '9';
    $l['ide_procEmi'] = '0'; //0=Emissão de NF-e com aplicativo do contribuinte;
    $l['ide_verProc'] = 'Place 4.00';

    $acha_cfop = $this->acha_serie($cd_empresa, $l['ide_mod'], $empresa['tpAmb'], $serie);
    if ($acha_cfop['nr'] == 0) {
      echo "<h4>Falta parâmetro na tabela series_uso. Empresa: " . $cd_empresa . " Modelo: " . $l['ide_mod'] . " Ambiente: " . $empresa['tpAmb'] . "</h4>";
      exit;
    }
    $l['ide_serie'] = $acha_cfop['serie'];
    $l['ide_nNF'] = $acha_cfop['doc'];
    $l['Idcase'] = $Idcase;
    $l['dest_CEP'] = $this->tratar($l['dest_CEP']);
    $CFOP = $l['CFOP'];
    unset($l['CFOP']);
    //$this->debug($empresa);
    $tipoFatura = $l['tipoFaturamento'];
    $tipoDepositario = $l['depositorRegime'];
    if($tipoFatura == 'LOTE' && $tipoDepositario == 'ARMAZEM'){
      $tableRef = 'tb_stockSupplyItens';
    }else{
      $tableRef = 'tb_caseitens';
    }
      unset($l['tipoFaturamento'], $l['depositorRegime']);
     //$this->debug($l);

    $id_nfe = $this->executa_insert($l, $this->campos_nfe_cab, 'nfe_cab');

    if ($id_nfe > 0) {

      $sql_itens = "SELECT 
        tb_caseitens.Cod_Produto AS cProd,
        produtos.nm_produto AS base_xProd,
        tb_caseitensSN.Barcode AS barcode,
        tb_caseitensSN.SerialNumber AS Serial,
        tb_caseitensSN.Validity AS Validity,
        tb_stockSupply.NF AS nf_entrada,
        produtos.PartNumber AS PartNumber,
        produtos.ncm AS ncm,
        produtos.un AS uCom,
        produtos.un AS uTrib,
        produtos.orig,
        tb_caseitensSN.Qtd AS qCom,
        tb_caseitensSN.Qtd AS qTrib,
        $tableRef.PriceRS AS vUnCom,
        $tableRef.PriceRS AS vUnTrib,
        ROUND (tb_caseitensSN.Qtd * $tableRef.PriceRS, 2) AS vProd,
        ROUND (tb_caseitensSN.Qtd * $tableRef.PriceRS, 2) AS vBC,
        tb_caseitens.ICMS AS pICMS,
        ROUND (tb_caseitensSN.Qtd * $tableRef.PriceRS * (tb_caseitens.ICMS / 100), 2) AS vICMS,
        ROUND(tb_caseitensSN.Qtd * $tableRef.PriceRS, 2) AS vBCIPI,
        tb_caseitens.IPI AS pIPI,
        ROUND(tb_caseitensSN.Qtd * $tableRef.PriceRS * (tb_caseitens.IPI / 100), 2) AS vIPI
        FROM tb_caseitens
        INNER JOIN produtos ON tb_caseitens.Cod_Produto = produtos.cd_produto
        INNER JOIN tb_caseitensSN ON tb_caseitensSN.IdSN = tb_caseitens.Id
        LEFT JOIN tb_stockSupplySN ON tb_stockSupplySN.Barcode = tb_caseitensSN.Barcode 
          AND tb_stockSupplySN.PartNumber = tb_caseitensSN.PartNumber 
          AND tb_stockSupplySN.SerialNumber = tb_caseitensSN.SerialNumber
        LEFT JOIN tb_stockSupplyItens ON tb_stockSupplyItens.IdPN = tb_stockSupplySN.IdPN
        LEFT JOIN tb_stockSupply ON tb_stockSupply.Id = tb_stockSupplyItens.Id
        WHERE tb_caseitens.Idcase = " . $Idcase . " 
        ORDER BY tb_caseitens.Id ASC";
      $res_itens = $this->CONEXAO->query($sql_itens);
      while ($li = $res_itens->fetch(PDO::FETCH_ASSOC)) {
        //$this->debug($li);
        $partesDescricao = [];

        if (!empty($li['nf_entrada'])) {
          $partesDescricao[] = 'NF Entrada: ' . $li['nf_entrada'];
        }

        if (!empty($li['Serial'])) {
          $partesDescricao[] = 'Serial Number: ' . $li['Serial'];
        }

        if (!empty($li['barcode'])) {
          $partesDescricao[] = 'Lote: ' . $li['barcode'];
        }

        if (!empty($li['Validity'])) {
          $partesDescricao[] = 'Validade: ' . date('d/m/Y', strtotime($li['Validity']));
        }

        $descricao = implode(' | ', $partesDescricao);

        $li['xProd'] = $li['base_xProd'];
            $li['infAdProd'] = $descricao;
        $li['vBC'] = 0;
        $li['vICMS'] = 0;
        $li['id_nfe'] = $id_nfe;
        $li['CFOP'] = $CFOP;
        $li['cst_icms'] = '41';
        $li['cst_pis'] = '07';
        $li['cst_cofins'] = '08';

        $li['pIPI'] = 0;
        $li['vBCIPI'] = 0;
        $li['vIPI'] = 0;
        $li['cst_ipi'] = '53';
        $li['enq_ipi'] = '101';
        unset($li['base_xProd'], $li['barcode'], $li['Validity'], $li['nf_entrada'], $li['Serial']);
        //$this->inserirItemNFE($li);
        //$this->debug($li);
        $res = $this->executa_insert($li, $this->campos_nfe_itens, 'nfe_itens');
      }
      $total = $this->calcula_total($id_nfe);
      $this->CONEXAO->exec("UPDATE tb_case SET 
                          id_nfe  = " . $id_nfe . ", 
                          PublicWhNfeValue = '" . $total . "', 
                          Public_WH_NF = '" . $l['ide_nNF'] . "'
                WHERE Idcase = " . $Idcase);
    }
    if ($id_nfe > 0) {
      $this->atualiza_nrnf($cd_empresa, $l['ide_mod'], $l['ide_nNF'], $empresa['tpAmb'], $serie);
    }
    return $id_nfe;
  }
  

  // ======== FIM FUNCAO FERNANDO  ====================================================================================
  //==========================================================================
  //  Acha idDest
  public function Acha_idDest($uf_emitente, $uf_destino, $cd_pais)
  {
      $idDest = NULL;
      //1=Operação interna;
      //2=Operação interestadual;
      //3=Operação com exterior.
      if ($uf_emitente == $uf_destino) {
          $idDest = '1';
      } else if ($uf_emitente != $uf_destino) {
          $idDest = '2';
      }
      if ($cd_pais != '1058') {
          $idDest = '3';
      }
      return $idDest;
  }

  //==========================================================================
  //Acha serie nota
  public function acha_serie($cd_empresa, $mod, $tpAmb, $serie)
  {

      $sql = "SELECT 
                          serie,
                          (ultimo_doc +1) AS doc
                      FROM
                          series_uso 
                      WHERE cd_doc = '" . $mod . "' 
                          AND cd_empresa = " . $cd_empresa . " 
                          AND tpAmb = '" . $tpAmb . "' 
                          AND serie = '" . $serie . "' ";
      $res = $this->CONEXAO->query($sql);
      $nr = $res->rowCount(); //Conta números de registros
      $serie = NULL;
      $doc = 0;
      if ($nr != 0) {
          $l = $res->fetch(PDO::FETCH_ASSOC);
          $serie = $l['serie'];
          $doc = $l['doc'];
      }
      if ($nr == 0) {
          echo "<h4>Falta parâmetro de serie_uso! Empresa: $cd_empresa Modelo: $mod Ambiente: $tpAmb Serie: $serie</h4>";
          exit;
      }
      return array(
          'nr' => $nr,
          'serie' => $serie,
          'doc' => $doc
      );
  }

  //==========================================================================
  public function calcula_total($id_nfe)
  {

      $sql = "SELECT 
                          IFNULL(ROUND(SUM(nfe_itens.vProd), 2), 0) AS vProd,
                          IFNULL(ROUND(SUM(nfe_itens.vDesc), 2), 0) AS vDesc,
                          IFNULL(ROUND(SUM(nfe_itens.vbc), 2), 0) AS vBC,
                          IFNULL(ROUND(SUM(nfe_itens.vicms), 2),0) AS vICMS,
                          IFNULL(ROUND(SUM(nfe_itens.vipi), 2),0) AS vIPI,
                          IFNULL(ROUND(SUM(nfe_itens.vpis), 2),0) AS vPIS,
                          IFNULL(ROUND(SUM(nfe_itens.vcofins), 2),0) AS vCOFINS,
                          IFNULL(ROUND(SUM(nfe_itens.vBCST), 2),0) AS vBCST,
                          IFNULL(ROUND(SUM(nfe_itens.vICMSST), 2),0) AS vST,
                          IFNULL(ROUND(SUM(nfe_itens.vFrete), 2),0) AS vFrete,
                          IFNULL(ROUND(SUM(nfe_itens.vOutro), 2),0) AS vOutro,
                          IFNULL(ROUND(SUM(nfe_itens.vSeg), 2),0) AS vSeg,
                          IFNULL(ROUND(SUM(nfe_itens.vTotTrib), 2),0) AS vTotTrib 
                  FROM
                          nfe_itens 
                  WHERE id_nfe = " . $id_nfe;
      $res = $this->CONEXAO->query($sql);
      $lt = $res->fetch(PDO::FETCH_ASSOC);
      $vII = 0;
      $vNF = ($lt['vProd'] + $lt['vST'] + $lt['vFrete'] + $lt['vSeg'] + $lt['vOutro'] + $vII + $lt['vIPI'] - ($lt['vDesc']));
      $up = " UPDATE nfe_cab SET vProd = " . $lt['vProd'] . "  ,
                                   vDesc =  " . $lt['vDesc'] . "  ,
                                   vBC  = " . $lt['vBC'] . "  ,
                                   vICMS = " . $lt['vICMS'] . ",
                                   vIPI = " . $lt['vIPI'] . ",
                                  vPIS = " . $lt['vPIS'] . ",
                                  vCOFINS = " . $lt['vCOFINS'] . ",
                                  vBCST = " . $lt['vBCST'] . ",
                                  vST = " . $lt['vST'] . ",
                                  vFrete = " . $lt['vFrete'] . ",
                                  vOutro = " . $lt['vOutro'] . ",
                                  vSeg = " . $lt['vSeg'] . ",
                                  vTotTrib = " . $lt['vTotTrib'] . ",
                                  vNF = " . $vNF . " 
           WHERE id_nfe = " . $id_nfe;

      $this->CONEXAO->exec($up);

      return $vNF;
  }

  //==========================================================================
  public function gera_saida_cliente($cd_empresa, $chave)
  {
      //Case
      $sql = "SELECT 
                              num_doc,
                              vl_doc,
                              pesob,
                              qvol,
                              dest_cpf_cnpj,
                              dest_IE,
                              dest_xNome,
                              dest_xLgr,
                              dest_nro,
                              dest_xBairro,
                              dest_cMun,
                              dest_xMun,
                              dest_UF,
                              dest_cPais,
                              dest_xPais,
                              dest_indIEDest,
                              dest_CEP,
                              dest_fone,
                              dest_email 
                            FROM
                              temp_nfe_entradas 
                            WHERE chv_nfe = '" . $chave . "' ";
      $res = $this->CONEXAO->query($sql);
      $l = $res->fetch(PDO::FETCH_ASSOC);
      $tb['IdEndClient'] = $this->cad_participante($l);
      $tb['VolumeTotal'] = $l['qvol'];
      $tb['WeightTotal'] = $l['pesob'];
      $tb['DelivType'] = $l['dest_cpf_cnpj'];
      $tb['NFs'] = $l['num_doc'];
      $tb['NFeValue'] = $l['vl_doc'];
      $l['dest_xNome'] = $this->primeironome($l['dest_xNome']);
      $Dest_xNome = $l['dest_xNome'];



      $tb['RequestDateTime'] = date("Y-m-d H:i:s");
      //$this->debug($tb);

      $IdCase = $this->executa_insert($tb, $this->campos_tb_case, 'tb_case');

      if ($IdCase > 0) {
          $sql_itens = "SELECT 
                                  cod_item AS PartNumber,
                                  descricao_produto AS nm_produto,
                                  qtd AS QTY,
                                  vlr_unit AS PriceRS,
                                  aliq_icms AS ICMS,
                                  aliq_ipi AS IPI,
                                  orig,
                                  ncm
                                FROM
                                  temp_nfe_entradas_itens 
                                WHERE chv_nfe = '" . $chave . "' 
                                ORDER BY num_item ASC ";
          $res_itens = $this->CONEXAO->query($sql_itens);
          while ($li = $res_itens->fetch(PDO::FETCH_ASSOC)) {
              $prod['cProd'] = $li['PartNumber'];
              $prod['xProd'] = $li['nm_produto'];
              $prod['pICMS'] = $li['ICMS'];
              $prod['pIPI'] = $li['IPI'];
              $prod['uCom'] = 'UN';
              $prod['orig'] = $li['orig'];
              $prod['ncm'] = $li['ncm'];
              $prod['dest_xNome'] = $Dest_xNome;
              //$this->debug($prod);
              $item['Cod_Produto'] = $this->encontra_produto($prod);
              $item['PartNumber'] = $li['PartNumber'];
              $item['QTY'] = $li['QTY'];
              $item['PriceRS'] = $li['PriceRS'];
              $item['ICMS'] = $prod['pICMS'];
              $item['IPI'] = $prod['pIPI'];
              $item['IdCase'] = $IdCase;
              $item['Client'] = $Dest_xNome;
              //$this->debug($item);
              $this->executa_insert($item, $this->campos_tb_caseitens, 'tb_caseitens');
          }
      }

      $this->CONEXAO->exec("UPDATE temp_nfe_entradas SET cd_tipo = 2 WHERE chv_nfe = '" . $chave . "' ");
      return $IdCase;
  }

  //==========================================================================
  // Localiza ou Cadastra Participante
  public function cad_participante($dados)
  {
      $cd = 0;
      $sql = "SELECT 
                              cd 
                       FROM
                              participantes 
                       WHERE cnpj_cpf = '" . $dados['dest_cpf_cnpj'] . "' ";
      $res = $this->CONEXAO->query($sql);
      $nr = $res->rowCount(); //Conta números de registros
      if ($nr != 0) {
          $l = $res->fetch(PDO::FETCH_ASSOC);
          $cd = $l['cd'];
      } else {
          if ($dados['dest_cpf_cnpj'] != '') {
              $forn['cnpj_cpf'] = $dados['dest_cpf_cnpj'];
              //$forn['inscricao_estadual'] = $dados['dest_IE'];
              $forn['razao_social'] = $dados['dest_xNome'];
              $forn['endereco'] = $dados['dest_xLgr'];
              $forn['numero'] = $dados['dest_nro'];
              $forn['bairro'] = $dados['dest_xBairro'];
              $forn['cd_cidade'] = $dados['dest_cMun'];
              $forn['indIEDest'] = $dados['dest_indIEDest'];
              $forn['cep'] = $dados['dest_CEP'];
              $forn['fone'] = $dados['dest_fone'];
              $forn['email'] = $dados['dest_email'];
              $cd = $this->executa_insert($forn, $this->campos_participantes, 'participantes');
          }
      }
      return $cd;
  }

  //==========================================================================
  //`Primeiro nome
  public function primeironome($var)
  {
      $retorno = explode(" ", $var);
      return $retorno['0'];
  }

  //==========================================================================
  //Gera pedido nfe entrada tb_return
  public function gera_pedidodevolucao($chave, $login)
  {
      $Idreturn = 0;
      $sql = "SELECT 
                      num_doc AS ReturnNF,
                      vl_doc AS ValorNFS1,
                      emit_xnome AS Client,
                      transp_cnpj AS TransportMode 
                    FROM
                      temp_nfe_entradas 
                    WHERE chv_nfe = '" . $chave . "' ";
      $res = $this->CONEXAO->query($sql);
      $nr = $res->rowCount(); //Conta números de registros
      if ($nr == 0) {
          echo "<h4>Nota não encontrada!</h4>";
          exit;
      } else {
          $l = $res->fetch(PDO::FETCH_ASSOC);
          $l['Client'] = $this->primeironome($l['Client']);
          $Client = $l['Client'];
          $l['ReturnDate'] = date("Y-m-d H:i:s");
          $l['User'] = $login;
          //$this->debug($l);
          $Idreturn = $this->executa_insert($l, $this->campos_tb_return, 'tb_return');
          if ($Idreturn > 0) {
              $sql_itens = "SELECT 
                                      cod_item AS PartNumber,
                                      descricao_produto AS nm_produto,
                                      qtd AS Qty,
                                      vlr_unit AS PartReturnValue,
                                      ncm,
                                      orig,
                                      aliq_icms AS ICMS,
                                      aliq_ipi AS IPI
                                  FROM
                                      temp_nfe_entradas_itens 
                                  WHERE chv_nfe = '" . $chave . "' 
                                  ORDER BY num_item ASC ";
              $res_itens = $this->CONEXAO->query($sql_itens);
              while ($lit = $res_itens->fetch(PDO::FETCH_ASSOC)) {
                  $lit['Idreturn'] = $Idreturn;
                  $lit['Client'] = $Client;
                  $Prod['cProd'] = $lit['PartNumber'];
                  $Prod['xProd'] = $lit['nm_produto'];
                  $Prod['uCom'] = 'UN';
                  $Prod['orig'] = $lit['orig'];
                  $Prod['dest_xNome'] = $lit['Client'];
                  $Prod['ncm'] = $lit['ncm'];
                  $Prod['pICMS'] = $lit['ICMS'];
                  $Prod['pIPI'] = $lit['IPI'];
                  $lit['Cod_Produto'] = $this->encontra_produto($Prod);
                  $lit['nm_produto'] = NULL;
                  $lit['ncm'] = NULL;
                  $lit['orig'] = NULL;
                  $lit['IPI'] = NULL;
                  $lit['ICMS'] = NULL;
                  $this->executa_insert($lit, $this->campos_returnitens, 'tb_returnitens');
              }
          }
      }
      $this->CONEXAO->exec("UPDATE temp_nfe_entradas SET cd_tipo = 2 WHERE chv_nfe = '" . $chave . "' ");
      return $Idreturn;
  }

  //==========================================================================
  /*      A - tb_export => nfe_cab
    B - tb_ exportItens = > nfe_itens
    Faturar

    Xml temp_nfe => tb_export
   */
  public function gera_tb_export($chave, $login)
  {
      $Idinv = 0;
      $sql = "SELECT 
                      num_doc AS NF,
                      emit_cnpj_cpf,
                      emit_xnome,
                      dest_cpf_cnpj,
                      dest_IE,
                      dest_xNome,
                      dest_xLgr,
                      dest_nro,
                      dest_xBairro,
                      dest_cMun,
                      dest_xMun,
                      dest_indIEDest,
                      dest_CEP,
                      dest_fone,
                      dest_email,
                      vl_doc 
                    FROM
                      temp_nfe_entradas 
                    WHERE chv_nfe = '" . $chave . "' ";
      $res = $this->CONEXAO->query($sql);
      $nr = $res->rowCount(); //Conta números de registros
      if ($nr == 0) {
          echo "<h4>Nota não encontrada!</h4>";
          exit;
      } else {
          $tb = $res->fetch(PDO::FETCH_ASSOC);
          $dados['ExpDate'] = date("Y-m-d H:i:s");
          $dados['User'] = $login;
          $dados['NF'] = $tb['NF'];
          $dados['Bill_To'] = 0;
          if ($tb['dest_cpf_cnpj'] != '') {
              $part['dest_cpf_cnpj'] = $tb['dest_cpf_cnpj'];
              $part['dest_IE'] = $tb['dest_IE'];
              $part['dest_xNome'] = $tb['dest_xNome'];
              $part['dest_xLgr'] = $tb['dest_xLgr'];
              $part['dest_nro'] = $tb['dest_nro'];
              $part['dest_xBairro'] = $tb['dest_xBairro'];
              $part['dest_cMun'] = $tb['dest_cMun'];
              $part['dest_indIEDest'] = $tb['dest_indIEDest'];
              $part['dest_CEP'] = $tb['dest_CEP'];
              $part['dest_fone'] = $tb['dest_fone'];
              $part['dest_email'] = $tb['dest_email'];

              $dados['Bill_To'] = $this->cad_participante($part); //DEstinaEmitente =: empresas
          }
          $dados['Exporter'] = 0;
          //Procura empresa 
          $sql_empresa = "SELECT cd_empresa FROM empresas WHERE cnpj_cpf = '" . $tb['emit_cnpj_cpf'] . "' ";
          $resempresa = $this->CONEXAO->query($sql_empresa);
          $nrempresa = $resempresa->rowCount(); //Conta números de registros
          if ($nrempresa > 0) {
              $lemp = $resempresa->fetch(PDO::FETCH_ASSOC);
              $dados['Exporter'] = $lemp['cd_empresa'];
          }

          $dados['Total'] = $tb['vl_doc'];
          $Idinv = $this->executa_insert($dados, $this->campos_export, 'tb_export');
          if ($Idinv > 0) {
              $sql_itens = "SELECT 
                                      cod_item AS PartNumber,
                                      TRIM(descricao_produto) AS Description,
                                      qtd AS Qty,
                                      vlr_unit AS PriceRS,
                                      vl_item AS TotalItensRS,
                                      orig,
                                      ncm AS NCM,
                                      ROUND(aliq_icms,0) AS ICMS,
                                      ROUND(aliq_ipi,0) AS IPI
                                    FROM
                                      temp_nfe_entradas_itens 
                                    WHERE chv_nfe = '" . $chave . "'";
              $res_itens = $this->CONEXAO->query($sql_itens);
              while ($itens = $res_itens->fetch(PDO::FETCH_ASSOC)) {
                  $Prod['cProd'] = $itens['PartNumber'];
                  $Prod['xProd'] = $itens['Description'];
                  $Prod['uCom'] = 'UN';
                  $Prod['orig'] = $itens['orig'];
                  $Prod['dest_xNome'] = $dados['Bill_To'];
                  $Prod['ncm'] = $itens['NCM'];
                  $Prod['pICMS'] = $itens['ICMS'];
                  $Prod['pIPI'] = $itens['IPI'];
                  $itens['Cod_Produto'] = $this->encontra_produto($Prod);
                  $itens['Client'] = $dados['Bill_To'];
                  $itens['Idinv'] = $Idinv;
                  $itens['orig'] = NULL;
                  //$this->debug($itens);
                  //$Idinvitens = $this->executa_insert($itens, $this->campos_exportitens, 'tb_exportitens');
                  $this->executa_insert($itens, $this->campos_exportitens, 'tb_exportitens');
              }
              $up = "UPDATE tb_export SET cfop = (
                              SELECT 
                                cfop 
                              FROM
                                temp_nfe_entradas_itens 
                              WHERE chv_nfe = '" . $chave . "' 
                              GROUP BY cfop ) WHERE Idinv = " . $Idinv;
              $this->CONEXAO->exec($up);
          }
      }
      return $Idinv;
  }

  //==========================================================================
  //tb_export
  public function fatura_tb_export($Idinv, $cd_empresa, $login, $modFrete, $serie)
  {
      $id_nfe = 0;
      $sql = "SELECT 
                          tb_export.Bill_To as cd_participante,
                          participantes.cnpj_cpf AS dest_cnpj_cpf,
                          participantes.razao_social AS dest_xNome,
                          participantes.endereco AS dest_xLgr,
                          participantes.numero AS dest_nro,
                          participantes.complemento AS dest_xCpl,
                          participantes.bairro AS dest_xBairro,
                          participantes.cd_cidade AS dest_cMun,
                          cidades.nm_cidade AS dest_xMun,
                          cidades.cd_estado AS dest_UF,
                          estados.uf AS ide_cuf,
                          participantes.cep AS dest_CEP,
                          estados.cd_pais AS dest_cPais,
                          paises.nm_pais AS dest_xPais,
                          participantes.fone AS dest_fone,
                          participantes.indIEDest AS dest_indIEDest,
                          participantes.inscricao_estadual AS dest_IE,
                          participantes.email AS dest_email,
                          tb_export.cfop,
                          cfop.nm_cfop AS ide_natOp 
                        FROM
                          tb_export 
                          INNER JOIN participantes 
                            ON (
                              tb_export.Bill_To = participantes.cd
                            ) 
                          INNER JOIN cidades 
                            ON (
                              participantes.cd_cidade = cidades.cd_cidade
                            ) 
                          INNER JOIN estados 
                            ON (
                              cidades.cd_estado = estados.cd_estado
                            ) 
                          INNER JOIN paises 
                            ON (estados.cd_pais = paises.cd_pais) 
                          LEFT JOIN cfop 
                            ON (tb_export.cfop = cfop.cfop) 
                        WHERE tb_export.Idinv = " . $Idinv . " 
                        ORDER BY Idinv DESC ";
      $res = $this->CONEXAO->query($sql);
      $nr = $res->rowCount(); //Conta números de registros
      if ($nr == 0) {
          echo "<h4>tb_export não encontrada! $Idinv </h4>";
          exit;
      } else {
          $l = $res->fetch(PDO::FETCH_ASSOC);
          $l['cd_status'] = 1;
          $l['cd_empresa'] = $cd_empresa;
          $l['ide_mod'] = '55';
          if ($l['dest_nro'] == '') {
              $l['dest_nro'] = 'SN';
          }
          $l['modFrete'] = $modFrete;
          $empresa = $this->get_empresa($cd_empresa);
          $l['emit_cnpj_cpf'] = $empresa['cnpj_cpf'];
          $l['emit_xNome'] = $empresa['razao_social'];
          $l['emit_xFant'] = $empresa['fantasia'];
          $l['emit_xLgr'] = $empresa['endereco'];
          $l['emit_nro'] = $empresa['numero'];
          $l['emit_xBairro'] = $empresa['bairro'];
          $l['emit_cMun'] = $empresa['cd_cidade'];
          $l['emit_xMun'] = $empresa['nm_cidade'];
          $l['emit_UF'] = $empresa['cd_estado'];
          $l['emit_CEP'] = $empresa['cep'];
          $l['emit_cPais'] = $empresa['cd_pais'];
          $l['emit_xPais'] = $empresa['nm_pais'];
          $l['emit_fone'] = $empresa['fone'];
          $l['emit_IE'] = $empresa['inscricao_estadual'];
          $l['emit_CRT'] = $empresa['crt'];
          $l['ide_dhEmi'] = date("Y-m-d H:i:s");
          $l['ide_dhSaiEnt'] = date("Y-m-d H:i:s");
          $l['ide_tpNF'] = '1'; //0=Entrada; 1=Saída
          $l['ide_idDEst'] = '';
          $l['ide_cMunFG'] = $l['emit_cMun'];
          $l['ide_tp_Imp'] = '1'; //1=DANFE normal, Retrato; 2=DANFE normal, Paisagem;
          $l['ide_tpEmis'] = '1';
          $l['ide_tpAmb'] = $empresa['tpAmb'];
          $l['ide_finNFe'] = '1'; // 1=NF-e normal; 2=NF-e complementar; 3=NF-e de ajuste; 4=Devolução de mercadoria.
          $l['ide_indFinal'] = 1;
          $l['ide_idDEst'] = $this->Acha_idDest($l['emit_UF'], $l['dest_UF'], $l['dest_xPais']);
          if ($l['dest_indIEDest'] == '3') {
              $l['dest_cMun'] = '9999999';
              $l['dest_xMun'] = 'EXTERIOR';
              $l['dest_UF'] = 'EX';
          }
          $l['ide_indPres'] = '9';
          $l['ide_procEmi'] = '0'; //0=Emissão de NF-e com aplicativo do contribuinte;
          $l['ide_verProc'] = 'Place 4.00';


          $acha_serie = $this->acha_serie($cd_empresa, $l['ide_mod'], $empresa['tpAmb'], $serie);
          if ($acha_serie['nr'] == 0) {
              echo "<h4>Falta parâmetro na tabela series_uso. Empresa: " . $cd_empresa . " Modelo: " . $l['ide_mod'] . " Ambiente: " . $empresa['tpAmb'] . "</h4>";
              exit;
          }
          $l['ide_serie'] = $acha_serie['serie'];
          $l['ide_nNF'] = $acha_serie['doc'];
          $l['Idinv'] = $Idinv;
          $CFOP = $l['cfop'];
          unset($l['cfop']);
          //$this->debug($empresa);
          // $this->debug($l);

          $id_nfe = $this->executa_insert($l, $this->campos_nfe_cab, 'nfe_cab');

          if ($id_nfe > 0) {
              //Faturar Itens
              $sql_itens = "SELECT 
                                          tb_exportitens.Cod_Produto AS cProd,
                                          produtos.PartNumber,
                                          produtos.nm_produto AS xProd,
                                          produtos.comp_nm_produto AS infAdProd,
                                          produtos.un AS uCom,
                                          produtos.un AS uTrib,
                                          produtos.ncm,
                                          produtos.orig ,
                                          tb_exportitens.QTY AS qCom,
                                          tb_exportitens.QTY AS qTrib,
                                          tb_exportitens.PriceRS AS vUnCom,
                                          tb_exportitens.PriceRS AS vUnTrib,
                                          ROUND((tb_exportitens.QTY * tb_exportitens.PriceRS),2) AS vProd,
                                          ROUND((tb_exportitens.QTY * tb_exportitens.PriceRS),2) AS vBC,
                                          tb_exportitens.ICMS AS pICMS,
                                          ROUND((ROUND((tb_exportitens.QTY * tb_exportitens.PriceRS),2) * (tb_exportitens.ICMS / 100)),2) AS vICMS,
                                          ROUND((tb_exportitens.QTY * tb_exportitens.PriceRS),2) AS vBCIPI,
                                          tb_exportitens.IPI AS pIPI,
                                          ROUND((ROUND((tb_exportitens.QTY * tb_exportitens.PriceRS),2) * (tb_exportitens.IPI / 100)),2)AS vIPI
                                        FROM
                                          tb_exportitens 
                                          INNER JOIN produtos 
                                            ON (
                                              tb_exportitens.Cod_Produto = produtos.cd_produto
                                            ) 
                                        WHERE tb_exportitens.Idinv = " . $Idinv . " 
                                        ORDER BY Idinvitens ASC ";
              $res_itens = $this->CONEXAO->query($sql_itens);
              while ($li = $res_itens->fetch(PDO::FETCH_ASSOC)) {
                  //$this->debug($li);
                  $li['id_nfe'] = $id_nfe;
                  $li['CFOP'] = $CFOP;

                  if ($li['pICMS'] == 0) {
                      $li['vBC'] = 0;
                      $li['vICMS'] = 0;
                      $li['cst_icms'] = '41';
                  } else {
                      $li['cst_icms'] = '00';
                  }
                  $li['cst_pis'] = '07';
                  $li['cst_cofins'] = '07';

                  if ($li['pIPI'] == 0) {
                      $li['vBCIPI'] = 0;
                      $li['vIPI'] = 0;
                      $li['cst_ipi'] = '53';
                  } else {
                      $li['cst_ipi'] = '50';
                  }

                  $li['enq_ipi'] = '999';

                  //$this->debug($li);
                  $this->executa_insert($li, $this->campos_nfe_itens, 'nfe_itens');
              }
              $total = $this->calcula_total($id_nfe);
              $this->CONEXAO->exec("UPDATE tb_export SET 
                                                  id_nfe  = " . $id_nfe . ", 
                                                  Total = '" . $total . "', 
                                                  NF = '" . $l['ide_nNF'] . "'
                            WHERE Idinv = " . $Idinv);
          }
          if ($id_nfe > 0) {
              $this->atualiza_nrnf($cd_empresa, $l['ide_mod'], $l['ide_nNF'], $empresa['tpAmb'], $serie);
          }

          return $id_nfe;
      }
  }

  //==========================================================================
  //Atualiza uso de numero de nfe
  public function atualiza_nrnf($cd_empresa, $mod, $num_doc, $tpAmb, $serie)
  {
      $up = "UPDATE 
                      series_uso 
                    SET
                      ultimo_doc = " . $num_doc . " 
                    WHERE cd_empresa = " . $cd_empresa . "  
                      AND cd_doc = '" . $mod . "' 
                      AND  tpAmb = '" . $tpAmb . "'    
                      AND  serie = '" . $serie . "' ";
      $this->CONEXAO->exec($up);
  }

  //=========================================================================
  function tratar($var)
  {
      $var = strtr(strtoupper($var), array(
          "à" => "A",
          "À" => "A",
          "è" => "E",
          "È" => "E",
          "ì" => "I",
          "Ì" => "I",
          "ò" => "O",
          "Ò" => "O",
          "ù" => "U",
          "Ù" => "U",
          "á" => "A",
          "Á" => "A",
          "ã" => "A",
          "Ã" => "A",
          "é" => "E",
          "É" => "E",
          "í" => "I",
          "Í" => "I",
          "ó" => "O",
          "ó" => "O",
          "ú" => "U",
          "Ú" => "U",
          "â" => "A",
          "Â" => "A",
          "ê" => "E",
          "Ê" => "E",
          "î" => "I",
          "Î" => "I",
          "ô" => "O",
          "Ô" => "O",
          "û" => "U",
          "Û" => "U",
          "Ç" => "C",
          "ç" => "C",
          "º" => NULL,
          "#" => NULL,
          "&" => "E",
          '"' => NULL,
          "'" => NULL,
          "´" => NULL,
          "`" => NULL,
          "¨" => NULL,
          "*" => NULL,
          "|" => NULL,
          "," => NULL,
          ";" => NULL,
          "&" => NULL,
          "%" => NULL,
          "?" => NULL,
          "½" => NULL,
          "¿" => NULL,
          "Ï" => NULL,
          "ª" => NULL,
          "/" => NULL,
          "-" => NULL,
          "." => NULL,
          "$" => NULL
      ));
      return $var;
    }
  }
?>