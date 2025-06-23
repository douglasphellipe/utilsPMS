<?php

namespace App\classes\CCT;

use Exception;

class CCT
{
    const TIPO_AMBIENTE_HOMOLOGACAO = 1;
    const TIPO_AMBIENTE_PRODUCAO    = 2;

    public $url_homologacao;
    public $url_producao;

    protected $url;
    protected $token;
    protected $csrfToken;

    private $cartificadoCaminho;
    private $certificadoSenha;

    public function __construct(int $tipo_ambiente, string $cartificadoCaminho, string $certificadoSenha)
    {
        $this->defineUrl($tipo_ambiente);
		
        $this->cartificadoCaminho = $cartificadoCaminho;
        $this->certificadoSenha   = $certificadoSenha;
    }

    protected function defineUrl(int $tipo_ambiente)
    {
        if ($tipo_ambiente == 1) {
            $this->url = "https://val.portalunico.siscomex.gov.br";

            return;
        }

        return $this->url = "https://portalunico.siscomex.gov.br";
    }

    public function autenticacao()
    {

        $url = "{$this->url}/portal/api/autenticar";

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Role-Type: AGECARGA",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_SSLCERT, $this->cartificadoCaminho);
        curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $this->certificadoSenha);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([]));

        $result = curl_exec($ch);
        $info   = curl_getinfo($ch);

        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch));
        }

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header      = substr($result, 0, $header_size);
        $body        = substr($result, $header_size);
        $headers     = explode("\r\n", $header);
        $token       = '';
        $csrfToken   = '';

        foreach ($headers as $h) {
            if (stripos($h, 'set-token:') === 0) {
                $token = trim(substr($h, strlen('set-token:')));
            }

            if (stripos($h, 'x-csrf-token:') === 0) {
                $csrfToken = trim(substr($h, strlen('x-csrf-token:')));
            }
        }

        if (empty($token)) {
            throw new Exception("Token não identificado na requsição de retorno");
        }

        if (empty($csrfToken)) {
            throw new Exception("CSRF não identificado na requsição de retorno");
        }

        curl_close($ch);

        $csrfToken = json_decode($body, true);
		
        $this->setToken($token);
        $this->setCsrf($csrfToken['token']);

        return [
			'token'  => $token,
			'csrf'   => $csrfToken['token'],
			'status' => $info
		];
    }

    public function setToken(string $token)
    {
        $this->token = $token;
		
    }

    public function setCsrf(string $csrfToken)
    {
        $this->csrfToken = $csrfToken;
    }

    public function consulta(string $numeroConhecimento)
    {
        $queryParams = [];
        $url         = "{$this->url}/ccta/api/ext/conhecimentos";

        $queryParams['numeroConhecimento'] = $numeroConhecimento;

        $url .= '?' . http_build_query($queryParams);

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Role-Type: AGECARGA",
            "Content-Type: application/json",
            "Authorization: " . $this->token,
            "X-CSRF-Token: " . $this->csrfToken
        ]);
        curl_setopt($ch, CURLOPT_SSLCERT, $this->cartificadoCaminho);
        curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $this->certificadoSenha);
        curl_setopt($ch, CURLOPT_HEADER, true);

        $result = curl_exec($ch);
        $info   = curl_getinfo($ch);

        if ($info['http_code'] == 401) {
            $header_size  = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $body         = substr($result, $header_size);
            $responseBody = json_decode($body, true);

            $mensagem = "Erro: {$responseBody['message']} - Code: {$responseBody['code']}";

            throw new Exception($mensagem, $info['http_code']);
        }

        if (curl_errno($ch)) {
            $responseData = 'Erro na consulta: ' . curl_error($ch);

            throw new Exception($responseData);
        }

        $header_size  = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header       = substr($result, 0, $header_size);
        $body         = substr($result, $header_size);
        $responseBody = json_decode($body, true);

        if (empty($responseBody)) {
            throw new Exception("Body não retornado");
        }

        return $responseBody;
    }
	public function criarHawb(string $xmlContent, string $cnpj)
	{
		// URL para associar a HouseWaybill
		$url = "{$this->url}/ccta/api/ext/incoming/xfzb?cnpj={$cnpj}";  // CNPJ na URL como parâmetro de consulta
		// Inicializa o cURL
		$ch = curl_init($url);

		// Configura as opções do cURL para envio de XML no corpo
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			"Authorization: " . $this->token, // Token JWT
			"X-CSRF-Token: " . $this->csrfToken, // Token CSRF
			"Content-Type: application/xml", // Tipo de conteúdo XML
		]);
		curl_setopt($ch, CURLOPT_SSLCERT, $this->cartificadoCaminho); // Caminho do certificado
		curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $this->certificadoSenha); // Senha do certificado
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlContent); // Enviar o XML no corpo

		// Executa a requisição e captura a resposta
		$result = curl_exec($ch);
		$info   = curl_getinfo($ch);

		// Verifica se houve erro na requisição
		if (curl_errno($ch)) {
			$responseData = 'Erro na requisição: ' . curl_error($ch);
			throw new Exception($responseData);
		}

		// Verifica o código HTTP de resposta
		if ($info['http_code'] != 200) {
			throw new Exception("Erro na API, código de resposta: " . $info['http_code']);
		}

		// Fecha a conexão cURL
		curl_close($ch);

		// Retorna a resposta da API
		return $result;
	}
	
	function associarHawb(string $xmlContent, string $cnpj)
	{
		// URL para associar a HouseWaybill
		$url = "{$this->url}/ccta/api/ext/incoming/xfhl?cnpj={$cnpj}";  // CNPJ na URL como parâmetro de consulta
		
		// Inicializa o cURL
		$ch = curl_init($url);

		// Configura as opções do cURL para envio de XML no corpo
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			"Authorization: " . $this->token, // Token JWT
			"X-CSRF-Token: " . $this->csrfToken, // Token CSRF
			"Content-Type: application/xml", // Tipo de conteúdo XML
		]);
		curl_setopt($ch, CURLOPT_SSLCERT, $this->cartificadoCaminho); // Caminho do certificado
		curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $this->certificadoSenha); // Senha do certificado
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlContent); // Enviar o XML no corpo

		// Executa a requisição e captura a resposta
		$result = curl_exec($ch);
		$info   = curl_getinfo($ch);

		// Verifica se houve erro na requisição
		if (curl_errno($ch)) {
			$responseData = 'Erro na requisição: ' . curl_error($ch);
			throw new Exception($responseData);
		}

		// Verifica o código HTTP de resposta
		if ($info['http_code'] != 200) {
			throw new Exception("Erro na API, código de resposta: " . $info['http_code']);
		}

		// Fecha a conexão cURL
		curl_close($ch);

		// Retorna a resposta da API
		return $result;
	}


}





?>