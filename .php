<?php
error_reporting(0);
date_default_timezone_set("UTC");

const HOST = "https://teaserfast.ru/";
const TITLE = "TeaserFast Bot";

class Bot {
    private $cookie;
    
    function __construct() {
        echo "=== TEASERFAST BOT ===\n";
        
        // Leer cookie
        $this->cookie = file_get_contents("data/cookie.txt");
        if (!$this->cookie) {
            die("Error: No cookie found. Please set your cookie first.\n");
        }
        
        $this->run();
    }
    
    private function run() {
        $cycle = 0;
        while(true) {
            $cycle++;
            echo "\n=== CYCLE $cycle === " . date('H:i:s') . " ===\n";
            
            // Check balance and withdraw
            $this->checkBalanceAndWithdraw();
            
            // Wait 60 seconds
            echo "Waiting 60 seconds...\n";
            sleep(60);
        }
    }
    
    private function checkBalanceAndWithdraw() {
        // Get dashboard
        $dashboard = $this->getDashboard();
        if (!$dashboard) {
            echo "ERROR: Cannot get dashboard\n";
            return;
        }
        
        $balance = floatval($dashboard['Balance']);
        echo "Balance: " . $dashboard['Balance'] . " RUB\n";
        echo "Username: " . $dashboard['Username'] . "\n";
        
        // Check if we can withdraw (15 RUB minimum)
        if ($balance >= 15.0) {
            echo "Attempting auto withdraw of 15 RUB to Payeer...\n";
            $result = $this->processWithdraw(15.0);
            
            if ($result) {
                echo "SUCCESS: Withdraw completed! 15 RUB sent to Payeer\n";
                
                // Update balance after withdraw
                $newDashboard = $this->getDashboard();
                if ($newDashboard) {
                    echo "New Balance: " . $newDashboard['Balance'] . " RUB\n";
                }
            } else {
                echo "ERROR: Withdraw failed\n";
            }
        } else {
            echo "Balance too low for withdraw (need 15 RUB, have $balance RUB)\n";
        }
    }
    
    private function processWithdraw($amount) {
        // Primero necesitamos cargar la página de withdraw para obtener el token/csrf
        $withdrawPage = $this->getWithdrawPage();
        if (!$withdrawPage) {
            echo "ERROR: Cannot load withdraw page\n";
            return false;
        }
        
        // Preparar datos para el withdraw
        $data = "nwithdraw_sum=" . $amount . "&withdraw_type_h=2&send_widthdraw=submit";
        
        $headers = [
            "Host: teaserfast.ru",
            "Cookie: " . $this->cookie,
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36",
            "Content-Type: application/x-www-form-urlencoded",
            "Referer: " . HOST . "withdraw/",
            "Origin: " . HOST,
            "Cache-Control: max-age=0",
            "Upgrade-Insecure-Requests: 1"
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, HOST . "withdraw/");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_HEADER, 1); // Incluir headers en la respuesta
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo "Withdraw HTTP Code: $httpCode\n";
        
        // Verificar si el withdraw fue exitoso
        if (strpos($response, "Вывод успешно выполнен") !== false ||
            strpos($response, "успешно выполнен") !== false ||
            strpos($response, "successfully") !== false ||
            strpos($response, "Заявка принята") !== false) {
            return true;
        }
        
        // Si hay un error específico, mostrarlo
        if (strpos($response, "Недостаточно средств") !== false) {
            echo "ERROR: Insufficient funds\n";
        } elseif (strpos($response, "Минимальная сумма") !== false) {
            echo "ERROR: Below minimum amount\n";
        } elseif (strpos($response, "error") !== false) {
            echo "ERROR: General error detected\n";
        }
        
        return false;
    }
    
    private function getWithdrawPage() {
        $headers = [
            "Host: teaserfast.ru",
            "Cookie: " . $this->cookie,
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36"
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, HOST . "withdraw/");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return $response;
    }
    
    private function getDashboard() {
        $headers = [
            "Host: teaserfast.ru",
            "Cookie: " . $this->cookie,
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36"
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, HOST);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        // Extraer username
        $user = "";
        if (preg_match('/<div class="main_user_login">([^<]+)</', $response, $matches)) {
            $user = trim($matches[1]);
        }
        
        // Extraer balance
        $balance = "";
        if (preg_match('/<span class="int blue" id="basic_balance" title="[^"]*">([^<]+)</', $response, $matches)) {
            $balance = trim($matches[1]);
        }
        
        if ($user && $balance) {
            return ["Username" => $user, "Balance" => $balance];
        }
        
        return null;
    }
}

// Verificar si existe el directorio data
if (!file_exists("data")) {
    mkdir("data");
}

// Verificar si existe la cookie
if (!file_exists("data/cookie.txt")) {
    echo "Please create data/cookie.txt with your cookie first\n";
    echo "Format: _ym_uid=...; _ym_d=...; user_id=...; pass_id=...; etc...\n";
    exit;
}

// Iniciar bot
new Bot();