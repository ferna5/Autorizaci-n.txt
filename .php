<?php    
error_reporting(0);    
date_default_timezone_set("America/Bogota"); // Hora de Colombia
    
// Constantes y configuración    
const    
VERSION = "0.0.1",    
HOST = "https://teaserfast.ru/",    
REFFLINK = "https://teaserfast.ru/a/iewilmaestro",    
YOUTUBE = "https://youtube.com/@iewil",    
TITLE = "TeaserFast Bot";    
    
// Colores para la consola    
const n = "\n";    
const d = "\033[0m";    
const m = "\033[1;31m";    
const h = "\033[1;32m";    
const k = "\033[1;33m";    
const b = "\033[1;34m";    
const u = "\033[1;35m";    
const c = "\033[1;36m";    
const p = "\033[1;37m";    
    
// Clase para mostrar información en consola    
class Display {    
    public static function Clear(){    
        (PHP_OS == "Linux") ? system('clear') : pclose(popen('cls','w'));    
    }    
        
    public static function Ban($title = null, $version = null) {    
        if ($title && $version) {    
            echo "\n\033[1;36m" . str_repeat("=", 50) . "\n";    
            echo "              " . $title . " v" . $version . "\n";    
            echo str_repeat("=", 50) . "\033[0m\n\n";    
        } else {    
            echo "\n\033[1;36m" . str_repeat("=", 50) . "\n";    
            echo "              TEASERFAST BOT\n";    
            echo str_repeat("=", 50) . "\033[0m\n\n";    
        }    
    }    
        
    public static function Cetak($label, $value) {    
        echo self::rata($label, $value) . "\n";    
    }    
        
    public static function Line($len = 50) {    
        echo d . str_repeat('─', $len) . "\n";    
    }    
        
    public static function Error($message) {    
        echo self::rata("warning", $message);    
    }    
        
    public static function Title($text) {    
        echo "\033[1;33m" . str_pad(strtoupper($text), 45, " ", STR_PAD_BOTH) . "\033[0m\n";    
    }    
        
    public static function Menu($index, $text, $last = 0) {    
        if($last && strlen($text) < 11){    
            echo "-[$index] $text\t\t$last\n";    
        }elseif($last){    
            echo "-[$index] $text\t$last\n";    
        }else{    
            echo "-[$index] $text\n";    
        }    
    }    
        
    public static function Isi($text) {    
        return "\033[1;35m" . $text . "\033[0m: ";    
    }    
        
    public static function sukses($text) {    
        echo self::rata("success", $text) . "\n";    
    }    
        
    public static function info($text) {    
        echo self::rata("info", $text) . "\n";    
    }    
        
    public static function waktu($text) {    
        echo k . "[" . date('H:i:s') . "] " . d . $text . "\n";    
    }    
        
    private static function rata($var, $value) {    
        $list_var = [    
            "success" => h . "✓",    
            "warning" => m . "!",    
            "debug"   => k . "?",    
            "info"    => b . "i"    
        ];    
        $len = (in_array($var, array_keys($list_var))) ? 8 : 9;    
        $lenstr = ($len == 8) ? $len - strlen($var) + 1 : $len - strlen($var);    
        $open = ($len == 8) ? $list_var[$var] . " " : "› ";    
        return $open . $var . str_repeat(" ", $lenstr) . p . ":: " . $value;    
    }    
}    
    
// Clase para funciones utilitarias    
class Functions {    
    public static function setConfig($name) {    
        $file = "data/" . $name . ".txt";    
        if (file_exists($file)) {    
            return trim(file_get_contents($file));    
        }    
            
        if ($name == "cookie") {    
            print Display::Isi("Enter Cookie");    
            $value = readline();    
        } elseif ($name == "user_agent") {    
            $value = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36";    
        } elseif ($name == "xevil_apikey") {    
            print Display::Isi("Enter Xevil API Key");    
            print h . "(Get from: t.me/Xevil_check_bot?start=1204538927)\n";    
            $value = readline();    
        }    
            
        if (!empty($value)) {    
            file_put_contents($file, $value);    
            return $value;    
        }    
            
        return "";    
    }    
        
    public static function removeConfig($name) {    
        $file = "data/" . $name . ".txt";    
        if (file_exists($file)) {    
            unlink($file);    
        }    
    }    
        
    public static function view() {    
        echo "\n";    
    }    
        
    public static function Tmr($seconds) {    
        $sym = [' ─ ',' / ',' │ ',' \ '];    
        for ($i = $seconds; $i >= 0; $i--) {    
            $index = $seconds - $i;    
            echo $sym[$index % 4] . p . date('H', $i) . ":" . p . date('i', $i) . ":" . p . date('s', $i) . "\r";    
            sleep(1);    
        }    
        echo "\r" . str_repeat(" ", 30) . "\r";    
    }    
        
    public static function clean($filename) {    
        return str_replace([".php", "_", "-"], ["", " ", " "], $filename);    
    }    
        
    public static function cofigApikey() {    
        $apikey = self::setConfig("xevil_apikey");    
            
        return [    
            "provider" => "xevil",    
            "url" => "https://sctg.xyz/",     
            "register" => "t.me/Xevil_check_bot?start=1204538927",     
            "apikey" => $apikey    
        ];    
    }    
}    
    
// Clase para manejar solicitudes HTTP    
class Requests {    
    public static function Curl($url, $headers = [], $post = 0, $data = "", $cookie = 0, $proxy = 0, $skip = 0) {    
        $ch = curl_init();    
        curl_setopt($ch, CURLOPT_URL, $url);    
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);    
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);    
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);    
        curl_setopt($ch, CURLOPT_HEADER, 1);    
            
        if (!empty($headers)) {    
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);    
        }    
            
        if ($post) {    
            curl_setopt($ch, CURLOPT_POST, 1);    
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);    
        }    
            
        if ($cookie) {    
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);    
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);    
        }    
            
        $response = curl_exec($ch);    
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);    
        $header = substr($response, 0, $header_size);    
        $body = substr($response, $header_size);    
            
        curl_close($ch);    
            
        return [$header, $body];    
    }    
}    
    
// Clase para resolver captchas    
class Captcha {    
    protected $url;    
    protected $provider;    
    protected $key;    
        
    public function __construct() {    
        $this->type = Functions::cofigApikey();    
        $this->url = $this->type["url"];    
        $this->provider = $this->type["provider"];    
        $this->key = $this->type["apikey"] . "|SOFTID1204538927";    
    }    
        
    private function in_api($content, $method, $header = 0) {    
        $param = "key=" . $this->key . "&json=1&" . $content;    
        if($method == "GET") return json_decode(file_get_contents($this->url . 'in.php?' . $param), 1);    
            
        $opts['http']['method'] = $method;    
        if($header) $opts['http']['header'] = $header;    
        $opts['http']['content'] = $param;    
        return file_get_contents($this->url . 'in.php', false, stream_context_create($opts));    
    }    
        
    private function res_api($api_id) {    
        $params = "?key=" . $this->key . "&action=get&id=" . $api_id . "&json=1";    
        return json_decode(file_get_contents($this->url . "res.php" . $params), 1);    
    }    
        
    private function solvingProgress($xr, $tmr, $cap) {    
        if($xr < 50) {    
            $wr = h;    
        } elseif($xr >= 50 && $xr < 80) {    
            $wr = k;    
        } else {    
            $wr = m;    
        }    
            
        $xwr = [$wr, p, $wr, p];    
        $sym = [' ‚îÄ ',' / ',' ‚îÇ ',' \ '];    
        $a = 0;    
            
        for($i = $tmr * 4; $i > 0; $i--) {    
            echo $xwr[$a % 4] . " Bypass $cap $xr%" . $sym[$a % 4] . " \r";    
            usleep(100000);    
            if($xr < 99) $xr += 1;    
            $a++;    
        }    
            
        return $xr;    
    }    
        
    private function getResult($data, $method, $header = 0) {    
        $cap = $this->filter(explode('&', explode("method=", $data)[1])[0]);    
        $get_res = $this->in_api($data, $method, $header);    
            
        if(is_array($get_res)) {    
            $get_in = $get_res;    
        } else {    
            $get_in = json_decode($get_res, 1);    
        }    
            
        if(!$get_in["status"]) {    
            $msg = $get_in["request"];    
            if($msg) {    
                print Display::Error("in_api @" . $this->provider . " " . $msg . n);    
            } elseif($get_res) {    
                print Display::Error($get_res . n);    
            } else {    
                print Display::Error("in_api @" . $this->provider . " something wrong\n");    
            }    
            return 0;    
        }    
            
        $a = 0;    
        while(true) {    
            echo " Bypass $cap $a% |   \r";    
            $get_res = $this->res_api($get_in["request"]);    
                
            if($get_res["request"] == "CAPCHA_NOT_READY") {    
                $ran = rand(5, 10);    
                $a += $ran;    
                if($a > 99) $a = 99;    
                echo " Bypass $cap $a% ‚îÄ \r";    
                $a = $this->solvingProgress($a, 5, $cap);    
                continue;    
            }    
                
            if($get_res["status"]) {    
                echo " Bypass $cap 100%";    
                sleep(1);    
                echo "\r                              \r";    
                echo h . "[" . p . "‚àö" . h . "] Bypass $cap success";    
                sleep(2);    
                echo "\r                              \r";    
                return $get_res["request"];    
            }    
                
            echo m . "[" . p . "!" . m . "] Bypass $cap failed";    
            sleep(2);    
            echo "\r                              \r";    
            print Display::Error($cap . " @" . $this->provider . " Error\n");    
            return 0;    
        }    
    }    
        
    private function filter($method) {    
        $map = [    
            "userrecaptcha" => "RecaptchaV2",    
            "hcaptcha" => "Hcaptcha",    
            "turnstile" => "Turnstile",    
            "universal" => "Ocr",    
            "base64" => "Ocr",    
            "antibot" => "Antibot",    
            "authkong" => "Authkong",    
            "teaserfast" => "Teaserfast"    
        ];    
    
        return $map[$method] ?? null;    
    }    
        
    public function Teaserfast($main, $small) {    
        $data = http_build_query([    
            "method" => "teaserfast",    
            "main_photo" => $main,    
            "task" => $small    
        ]);    
            
        $ua = "Content-type: application/x-www-form-urlencoded";    
        return $this->getResult($data, "POST", $ua);    
    }    
}    
    
// Clase principal del bot    
class Bot {    
    private $cookie;    
    private $uagent;    
    private $captcha;    
    private $cycle;    
        
    function __construct() {    
        Display::Ban(TITLE, VERSION);    
            
        cookie:    
        Display::Cetak("Register", REFFLINK);    
        Display::Line();    
                
        $this->cookie = Functions::setConfig("cookie");    
        $this->uagent = Functions::setConfig("user_agent");    
        Functions::view();    
            
        $this->captcha = new Captcha();    
        $this->cycle = 0;    
            
        Display::Ban(TITLE, VERSION);    
            
        $r = $this->Dashboard();    
        if(!$r['Username']) {    
            Functions::removeConfig("cookie");    
            print Display::Error("Cookie Expired\n");    
            goto cookie;    
        }    
            
        Display::Cetak("Username", $r['Username']);    
        Display::Cetak("Balance", $r['Balance']);    
        Display::Line();    
    
        $status = 0;    
        while(true) {    
            $this->cycle++;    
            echo "\n" . k . "=== CYCLE " . $this->cycle . " === " . date('H:i:s') . " ===" . d . "\n";    
                
            // Check balance and auto withdraw once per day    
            $dashboard = $this->Dashboard();    
            if ($dashboard) {    
                $balance = floatval($dashboard['Balance']);    
                if ($balance >= 15.0) {    
                    $this->checkBalanceAndWithdrawOnceADay();    
                } else {    
                    Display::waktu("Balance: " . $dashboard['Balance'] . " RUB - Too low for withdraw (need 15 RUB)");    
                    Display::Line();    
                }    
            }    
                
            // Continuar con el flujo normal del bot    
            if($this->Claim()) {    
                Functions::removeConfig("cookie");    
                goto cookie;    
            }    
            $status = $this->Extensions($status);    
            Functions::Tmr(30);    
        }    
    }    
    
    private function checkBalanceAndWithdrawOnceADay() {    
        $today = date('Y-m-d');    
        $file = "data/last_withdraw.txt";    
        $last = file_exists($file) ? trim(file_get_contents($file)) : '';    
            
        if ($last === $today) {    
            Display::waktu("Already withdrawn today. Skipping withdraw.");    
            return false;    
        }    
    
        if ($this->checkBalanceAndWithdraw()) {    
            file_put_contents($file, $today);    
        }    
    }    
    
    private function checkBalanceAndWithdraw() {    
        $dashboard = $this->Dashboard();    
        if (!$dashboard) {    
            Display::waktu("ERROR: Cannot get dashboard");    
            return false;    
        }    
            
        $balance = floatval($dashboard['Balance']);    
        Display::Cetak("Balance", $dashboard['Balance'] . " RUB");    
        Display::Cetak("Username", $dashboard['Username']);    
            
        // Check if we can withdraw (15 RUB minimum)    
        if ($balance >= 15.0) {    
            Display::waktu("Attempting auto withdraw of 15 RUB to Payeer...");    
            $result = $this->processWithdraw(15.0);    
                
            if ($result) {    
                Display::sukses("Withdraw completed! 15 RUB sent to Payeer");    
                    
                // Update balance after withdraw    
                $newDashboard = $this->Dashboard();    
                if ($newDashboard) {    
                    Display::Cetak("New Balance", $newDashboard['Balance'] . " RUB");    
                }    
                return true;    
            } else {    
                Display::Error("Withdraw failed");    
                return false;    
            }
} else {    
            Display::waktu("Balance too low to withdraw.");    
            return false;    
        }    
    }    
    
    private function processWithdraw($amount) {    
        // Simulación de la petición de withdraw    
        $url = HOST . "system/ajax.php";    
        $headers = [    
            "Content-Type: application/x-www-form-urlencoded",    
            "User-Agent: " . $this->uagent,    
            "Cookie: " . $this->cookie    
        ];    
        $postData = "action=withdraw&amount=" . $amount . "&currency=RUB&method=payeer";    
            
        list($header, $body) = Requests::Curl($url, $headers, 1, $postData);    
        $json = json_decode($body, true);    
            
        if (isset($json['status']) && $json['status'] == "success") {    
            return true;    
        }    
        return false;    
    }    
    
    private function Dashboard() {    
        $url = HOST . "system/ajax.php?action=getDashboard";    
        $headers = [    
            "User-Agent: " . $this->uagent,    
            "Cookie: " . $this->cookie    
        ];    
            
        list($header, $body) = Requests::Curl($url, $headers);    
        $data = json_decode($body, true);    
            
        if (!isset($data['Username'])) {    
            return false;    
        }    
            
        return [    
            "Username" => $data['Username'],    
            "Balance" => $data['Balance']    
        ];    
    }    
    
    private function Claim() {    
        // Aquí se ejecuta la lógica de claim del bot    
        Display::waktu("Claiming rewards...");    
        // Simulación de claim, reemplazar con la lógica real    
        sleep(2);    
        Display::sukses("Claim successful!");    
        return false;    
    }    
    
    private function Extensions($status) {    
        // Aquí se pueden colocar extensiones o tareas adicionales    
        return $status;    
    }    
}    
    
new Bot();