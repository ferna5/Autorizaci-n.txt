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
    
    // Nueva función para controlar retiros diarios
    public static function canWithdrawToday() {
        $file = "data/last_withdraw.txt";
        if (!file_exists($file)) {
            return true; // Primer retiro
        }
        
        $lastWithdraw = file_get_contents($file);
        $today = date('Y-m-d');
        
        return $lastWithdraw !== $today;
    }
    
    // Nueva función para registrar el retiro del día
    public static function markWithdrawDone() {
        $file = "data/last_withdraw.txt";
        file_put_contents($file, date('Y-m-d'));
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
            echo "\n" . k . "=== CYCLE " . $this->cycle . " === " . date('H:i:s') . " (COL) ===" . d . "\n";
            
            // Check balance and auto withdraw (solo 1 por día)
            $this->checkBalanceAndWithdraw();
            
            if($this->Claim()) {
                Functions::removeConfig("cookie");
                goto cookie;
            }
            $status = $this->Extensions($status);
            Functions::Tmr(30);
        }
    }
    
    private function checkBalanceAndWithdraw() {
        $dashboard = $this->Dashboard();
        if (!$dashboard) {
            Display::waktu("ERROR: Cannot get dashboard");
            return;
        }
        
        $balance = floatval($dashboard['Balance']);
        Display::Cetak("Balance", $dashboard['Balance'] . " RUB");
        Display::Cetak("Username", $dashboard['Username']);
        
        // Check if we can withdraw (15 RUB minimum) y solo 1 vez por día
        if ($balance >= 15.0 && Functions::canWithdrawToday()) {
            Display::waktu("Attempting auto withdraw of 15 RUB to Payeer...");
            $result = $this->processWithdraw(15.0);
            
            if ($result) {
                Display::sukses("Withdraw completed! 15 RUB sent to Payeer");
                Functions::markWithdrawDone(); // Registrar que ya se hizo el retiro hoy
                
                // Update balance after withdraw
                $newDashboard = $this->Dashboard();
                if ($newDashboard) {
                    Display::Cetak("New Balance", $newDashboard['Balance'] . " RUB");
                }
            } else {
                Display::Error("Withdraw failed");
            }
        } elseif ($balance >= 15.0 && !Functions::canWithdrawToday()) {
            Display::waktu("Withdraw already done today. Next withdraw available tomorrow.");
        } else {
            Display::waktu("Balance too low for withdraw (need 15 RUB, have $balance RUB)");
        }
        
        Display::Line();
    }
    
    private function processWithdraw($amount) {
        // Primero necesitamos cargar la página de withdraw para obtener el token/csrf
        $withdrawPage = $this->getWithdrawPage();
        if (!$withdrawPage) {
            Display::waktu("ERROR: Cannot load withdraw page");
            return false;
        }
        
        // Preparar datos para el withdraw
        $data = "nwithdraw_sum=" . $amount . "&withdraw_type_h=2&send_widthdraw=submit";
        
        $headers = [
            "Host: teaserfast.ru",
            "Cookie: " . $this->cookie,
            "User-Agent: " . $this->uagent,
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
        $httpCode = curl_get_info($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        Display::waktu("Withdraw HTTP Code: $httpCode");
        
        // Verificar si el withdraw fue exitoso
        if (strpos($response, "Вывод успешно выполнен") !== false ||
            strpos($response, "успешно выполнен") !== false ||
            strpos($response, "successfully") !== false ||
            strpos($response, "Заявка принята") !== false) {
            return true;
        }
        
        // Si hay un error específico, mostrarlo
        if (strpos($response, "Недостаточно средств") !== false) {
            Display::waktu("ERROR: Insufficient funds");
        } elseif (strpos($response, "Минимальная сумма") !== false) {
            Display::waktu("ERROR: Below minimum amount");
        } elseif (strpos($response, "error") !== false) {
            Display::waktu("ERROR: General error detected");
        }
        
        return false;
    }
    
    private function getWithdrawPage() {
        $headers = [
            "Host: teaserfast.ru",
            "Cookie: " . $this->cookie,
            "User-Agent: " . $this->uagent
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
    
    private function getExt() {
        $data = "extension=1&version=124&get=submit";
        return json_decode(Requests::Curl(HOST . "extn/get/", $this->headers(), 1, $data)[1], 1);
    }
    
    private function ExtPopup($hash) {
        $data = "hash=" . $hash . "&popup=submit";
        return json_decode(Requests::Curl(HOST . "extn/popup/", $this->headers(), 1, $data)[1], 1);
    }
    
    private function ClaimPopup($hash) {
        $data = "hash=" . $hash;
        return json_decode(Requests::Curl(HOST . "extn/popup-check/", $this->headers(), 1, $data)[1], 1);
    }
    
    private function ExtTeas($hash) {
        $data = "hash=" . $hash;
        return json_decode(Requests::Curl(HOST . "extn/status/", $this->headers(), 1, $data)[1], 1);
    }
    
    private function Extensions($status = 0) {
        $r = $this->getExt();
        if(isset($r['popup'])) {
            $status = "Ext Popup";
            $timer = $r['time_out'] / 1000;
            $hash = explode('/?tzpha=', $r['url'])[1];
            Display::waktu("Starting Extension Popup");
            $r = $this->ExtPopup($hash);
            Functions::Tmr($timer);
            $hash = $r['hash'];
            $r = $this->ClaimPopup($hash);
        } elseif(isset($r['hash'])) {
            $status = "Ext Ads";
            $timer = $r['timer'];
            Display::waktu("Starting Extension Ads");
            Functions::Tmr($timer);
            $hash = $r['hash'];
            $r = $this->ExtTeas($hash);
        } elseif(isset($r['captcha'])) {
            Display::waktu("Captcha detected, please solve manually");
            print Display::Error("Captcha: https://teaserfast.ru/check-captcha\n");
        } else {
            if(!$status) {
                Display::waktu("Waiting for tasks...");
            }
            Functions::Tmr(30);
            if(!$status) {
                Display::Line();
                return 1;
            }
        }

        if($r['success']) {
            Display::waktu("Completed $status - Earned " . $r['earn']);
            $r = Requests::Curl(HOST, $this->headers())[1];
            $bal = explode('</span>', explode('">', explode('<span class="int blue" id="basic_balance" title="', $r)[1])[1])[0];
            Display::Cetak("Balance", $bal);
            Display::Line();
            return;
        }
    }
    
    private function headers($xml = 0) {
        $h[] = "Host: " . parse_url(HOST)['host'];
        $h[] = "cookie: " . $this->cookie;
        if($xml) {
            $h[] = "X-Requested-With: XMLHttpRequest";
        }
        $h[] = "user-agent: " . $this->uagent;
        return $h;
    }											

    public function Dashboard() {
        $r = Requests::Curl(HOST, $this->headers())[1];
        $user = explode('</div>', explode('<div class="main_user_login">', $r)[1])[0];
        $bal = explode('</span>', explode('">', explode('<span class="int blue" id="basic_balance" title="', $r)[1])[1])[0];
        return ["Username" => $user, "Balance" => $bal];
    }

    private function Claim() {
        $r = Requests::Curl(HOST . 'task/', $this->headers())[1];
        
        $ids = explode('<div class="it_task task_youtube">', $r);
        if(isset($ids[1])) {
            $ids = explode('<a href="/task/', $ids[1]);
        } else {
            return;
        }
        
        foreach($ids as $a => $idc) {
            if($a == 0) continue;
            $id = explode('">', $idc)[0];
            Display::waktu("Starting YouTube task: $id");
            
            $r = Requests::Curl(HOST . 'task/' . $id, $this->headers())[1];
            if(preg_match('/Задание не найдено или в данный момент недоступно./', $r)) {
                Display::waktu("Task $id not available, skipping");
                continue;
            }
            
            $code = explode("'", explode("data: {dt: '", $r)[1])[0];
            $hd = explode("'", explode("hd: '", $r)[1])[0];
            $rc = explode("'", explode(" rc: '", $r)[1])[0];
            $tmr = explode(';', explode('var timercount = ', $r)[1])[0];
            
            Display::waktu("Waiting $tmr seconds for task");
            Functions::Tmr($tmr);

            $data = "dt=" . $code;
            $r = json_decode(Requests::Curl(HOST . 'captcha-start/', $this->headers(1), 1, $data)[1], 1);
            if(!isset($r['success'])) {
                Display::waktu("Failed to start captcha");
                break;
            }
            
            while(true) {
                $data = "yd=$id&hd=$hd&rc=$rc";
                $r = json_decode(Requests::Curl(HOST . 'captcha-youtube/', $this->headers(1), 1, $data)[1], 1);
                if(!isset($r['success'])) {
                    Display::waktu("Failed to get captcha data");
                    break;
                }
                
                if($r['сaptcha'] && $r['small']) {
                    Display::waktu("Solving captcha...");
                    $cap = $this->captcha->Teaserfast($r['сaptcha'], $r['small']);
                    
                    if (!$cap) {
                        Display::waktu("Captcha solving failed");
                        break;
                    }
                    
                    $x = explode(',', explode('=', $cap)[1])[0];
                    $y = explode('=', $cap)[2];
                    $cap = "$x:$y";
                } else {
                    Display::waktu("No captcha data received");
                    continue;
                }

                $data = "crxy=" . $cap . "&dt=" . $code;
                $r = json_decode(Requests::Curl(HOST . 'check-youtube/', $this->headers(1), 1, $data)[1], 1);
                if(isset($r['captcha'])) {
                    Display::waktu("Captcha verification failed, retrying...");
                    sleep(3);
                } else {
                    $desc = $r['desc'];
                    if($desc == "Время на прохождение каптчи истекло.") {
                        Display::waktu("Captcha time expired");
                        break;
                    }
                    
                    Display::waktu("Task completed: $desc");
                    $r = Requests::Curl(HOST, $this->headers())[1];
                    $bal = explode('</span>', explode('">', explode('<span class="int blue" id="basic_balance" title="', $r)[1])[1])[0];
                    Display::Cetak("Balance", $bal);
                    Display::Line();
                    break;
                }
            }
        }
    }
}

// Crear directorio data si no existe
if(!file_exists("data")) {
    mkdir("data");
    Display::sukses("Successfully created 'data' folder");
    Display::Line();
}

// Iniciar el bot
new Bot();
?>