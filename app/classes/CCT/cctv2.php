<?php

namespace app\classes\CCT;

use Exception;
use PDO;

class cctv2
{
    public $servidorbd;
    public $usuariobd;
    public $senhabd;
    public $bancobd;
    public $CONEXAO;

    public function __construct($dados)
    {
        $this->servidorbd = $dados['BD_SERVIDOR'];
        $this->usuariobd = $dados['BD_USUARIO'];
        $this->senhabd = $dados['BD_SENHA'];
        $this->bancobd = $dados['BD_BANCO'];
		
		date_default_timezone_set('America/Sao_Paulo');
        try {
            $this->CONEXAO = new PDO("mysql:host=$this->servidorbd;dbname=$this->bancobd", $this->usuariobd, $this->senhabd);
        } catch (Exception $erro) {
            echo "Erro conexão: " . $erro->getMessage();
            exit;
        }
    }

    public function getData()
    {
        $sql = "SELECT id, certBlob, passKey, ambient FROM cct_config ORDER BY 1 DESC LIMIT 1";

        $res = $this->CONEXAO->query($sql);
        $data = $res->fetch(PDO::FETCH_ASSOC);

        $ambient = $data['ambient'];
        $certBlob = $data['certBlob'];
        $password = $data['passKey'];
        $id = $data['id'];

        $certPath = tempnam(sys_get_temp_dir(), 'cert_');


        $tempPathPem = $certPath . '.pem';
        rename($certPath, $tempPathPem);

        file_put_contents($tempPathPem, $certBlob);

        return [
            'ambient' => $ambient,
            'password' => $password,
            'certificate' => $tempPathPem,
            'id' => $id
        ];
    }
	


    public function updateToken($id, $token)
    {
        $update = "UPDATE cct_token SET token = '{$token['token']}', csrf = '{$token['csrf']}' WHERE id = $id";
        
        $this->CONEXAO->exec($update);
    }
}