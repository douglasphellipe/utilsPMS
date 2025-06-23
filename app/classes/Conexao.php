<?php 

namespace App\Classes;
use Dotenv\Dotenv;
use PDO;
use Exception;
class Conexao{
    public $servidorbd;
    public $usuariobd;
    public $senhabd;
    public $bancobd;
    public $CONEXAO;

    public function __construct()
    {
  

        $this->servidorbd = getenv('DB_HOST');
        $this->usuariobd = getenv('DB_USER');
        $this->senhabd = getenv('DB_PASS');
        $this->bancobd = getenv('DB_DATABASE');
        date_default_timezone_set('America/Sao_Paulo');
        try {
            $this->CONEXAO = new PDO("mysql:host=$this->servidorbd;dbname=$this->bancobd", $this->usuariobd, $this->senhabd);
        } catch (Exception $erro) {
            echo "Erro conexão: " . $erro->getMessage();
            exit;
        }
    }

}