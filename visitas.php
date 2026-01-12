<?php
// visitas.php

class RegistroVisitas {
    private $arquivo = 'visitas.txt';
    
    public function registrar() {
        // Não registra em localhost
        $ip = $_SERVER['REMOTE_ADDR'];
        if ($ip === '::1' || $ip === '127.0.0.1' || 
            strpos($ip, '192.168.') === 0 || strpos($ip, '10.') === 0) {
            return;
        }
        
        // Ignora favicon e recursos estáticos
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        if (strpos($uri, 'favicon.ico') !== false || 
            preg_match('/\.(css|js|png|jpg|gif|svg)$/', $uri)) {
            return;
        }
        
        $data = date('d/m/Y');
        $hora = date('H:i:s');
        $localizacao = $this->obterLocalizacao($ip);
        $dispositivo = $this->obterDispositivo($_SERVER['HTTP_USER_AGENT'] ?? '');
        
        $linha = "$ip | $data | $hora | $localizacao | $dispositivo" . PHP_EOL;
        
        $conteudo = file_exists($this->arquivo) ? file_get_contents($this->arquivo) : '';
        
        $linhas = explode(PHP_EOL, $conteudo);
        if (!empty($linhas[0]) && strpos($linhas[0], 'TOTAL DE VISITAS:') !== false) {
            array_shift($linhas);
            if (isset($linhas[0]) && trim($linhas[0]) === '') {
                array_shift($linhas);
            }
        }
        
        array_unshift($linhas, $linha);
        
        $total = count(array_filter($linhas, 'trim'));
	echo "<span style='color:blue; position:fixed; right:10px; bottom:10px;z-index:9999 !important;'>".$total."</span>";
        
        $novoConteudo = "TOTAL DE VISITAS: $total" . PHP_EOL . PHP_EOL;
        $novoConteudo .= implode(PHP_EOL, $linhas);
        
        file_put_contents($this->arquivo, $novoConteudo, LOCK_EX);
    }
    
    private function obterLocalizacao($ip) {
        if (!function_exists('curl_init')) {
            return 'Desconhecido';
        }
        
        $ch = curl_init("https://ipapi.co/$ip/json/");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $dados = curl_exec($ch);
        curl_close($ch);
        
        if ($dados) {
            $json = json_decode($dados, true);
            if (isset($json['city'])) {
                return $json['city'];
            }
            if (isset($json['region'])) {
                return $json['region'];
            }
        }
        
        return 'Desconhecido';
    }
    
    private function obterDispositivo($userAgent) {
        if (preg_match('/mobile|android|iphone|ipad|phone/i', $userAgent)) {
            if (strpos($userAgent, 'iPad') !== false) return 'iPad';
            if (strpos($userAgent, 'iPhone') !== false) return 'iPhone';
            if (strpos($userAgent, 'Android') !== false) return 'Android';
            return 'Mobile';
        }
        
        if (preg_match('/tablet/i', $userAgent)) {
            return 'Tablet';
        }
        
        return 'Desktop';
    }
}

$v = new RegistroVisitas();
$v->registrar();
?>