import re
import os
import sys
import json
import time
import base64
from time import sleep
from datetime import datetime
from random import randint, choice
from curl_cffi import requests
from PIL import Image
from io import BytesIO

RESET = "\033[0m"
BRIGHT = "\033[1m"
GRAY = "\033[90m"
RED = "\033[91m"
GREEN = "\033[92m"
YELLOW = "\033[93m"
BLUE = "\033[94m"
MAGENTA = "\033[95m"
CYAN = "\033[96m"
WHITE = "\033[97m"
ORANGE = "\033[38;5;208m"
COLORS = [BLUE, CYAN, RED, GREEN, WHITE, YELLOW, ORANGE, GRAY, MAGENTA]


def load_and_validate_fingerprint(path_file):
    required_keys = [
        "fin[videoCard][vendor]",
        "fin[videoCard][renderer]",
        "fin[viewPort][h]",
        "fin[viewPort][w]",
        "fin[viewPort][hM]",
        "fin[viewPort][wM]",
        "fin[platform]",
        "fin[dpr]",
        "fin[multi][speakers]",
        "fin[multi][micros]",
        "fin[multi][webcams]",
        "fin[multi][devices]",
        "fin[ori][alpha]",
        "fin[ori][beta]",
        "fin[ori][gamma]",
        "fin[ori][is]",
        "fin[v]",
        "fin[cl][x]",
        "fin[cl][y]",
        "fin[webDef]",
        "fin[navName]",
        "fin[touch]",
        "fin[c]",
        "fin[memory]",
        "fin[concur]",
        "fin[en][ar]",
        "fin[en][b]",
        "fin[en][m]",
        "fin[en][p]",
        "fin[en][pv]",
        "fin[bat][charging]",
        "fin[bat][lvl]",
    ]

    try:
        with open(path_file, "r", encoding="utf-8") as f:
            data = json.load(f)
            missing = [k for k in required_keys if k not in data]
            if missing:
                raise ValueError()

            return data
    except Exception as e:
        json.dump({}, open("fingerprint.json", "w"), indent=4)
        print("BAD FINGERPRINT")
        print(str(e))
        exit()


def combined_img(image_b64, icons_b64):
    def fix_img(img_b64):
        img = Image.open(BytesIO(base64.b64decode(img_b64)))

        if not (
            img.mode in ("RGBA", "LA")
            or (img.mode == "P" and "transparency" in img.info)
        ):
            return img.convert("RGB")

        background = Image.new("RGB", img.size, (255, 255, 255))
        background.paste(img, mask=img.split()[-1])
        return background

    image = fix_img(image_b64)
    icons = fix_img(icons_b64)

    combined = Image.new(
        "RGB",
        (max(image.width, icons.width), image.height + icons.height),
        (255, 255, 255),
    )
    combined.paste(image, (0, 0))
    combined.paste(icons, (0, image.height))
    fmt = Image.open(BytesIO(base64.b64decode(image_b64))).format or "PNG"
    buffered = BytesIO()
    combined.save(buffered, format=fmt)
    return base64.b64encode(buffered.getvalue()).decode("utf-8")


class Xevil:
    def __init__(self):
        self.xv_session = requests.Session()
        self.apikey = files("XEVIL")
        self.fingerprint = load_and_validate_fingerprint("fingerprint.json")
        self.base_url = "https://api.sctg.xyz"
        self.resolution_times = []
        self.pending_task = {}
        self.tokens_spent = 0
        self.tokens_failed = 0
        self.resolver_attemps = 0
        self.current_task_id = None
        self.is_reusing_task = False
        self.all_errors = {
            "ERROR_WRONG_USER_KEY": "API key incorrecto o no existe",
            "ERROR_ZERO_BALANCE": "Balance insuficiente en la cuenta",
            "ERROR_KEY_DOES_NOT_EXIST": "API key incorrecto o sin balance",
            "ERROR_METHOD_DOES_NOT_EXIST": "Método de captcha no soportado",
            "WRONG_METHOD": "Método de captcha no especificado",
            "ERROR_BAD_DATA": "Parámetros faltantes para el captcha",
            "ERROR_BAD_REQUEST": "Error en request, parámetro requerido faltante",
            "WRONG_COUNT_IMG": "Muy pocas imágenes para método antibot",
            "WRONG_REQUESTS_LINK": "Enlace de solicitud incorrecto",
            "WRONG_LOAD_PAGEURL": "Formato de pageurl incorrecto",
            "ERROR_SITEKEY": "Sitekey incorrecto",
            "SITEKEY_IS_INCORRECT": "Sitekey inválido",
            "HCAPTCHA_NOT_FOUND": "HCaptcha no encontrado en la página",
            "TURNSTILE_NOT_FOUND": "Turnstile no encontrado en la página",
            "WRONG_RESULT": "El servicio no pudo resolver el captcha",
            "ERROR_CAPTCHA_UNSOLVABLE": "Service could not solve the captcha",
            "WRONG_CAPTCHA_ID": "Captcha con este ID no encontrado",
            "ERROR_WRONG_CAPTCHA_ID": "The captcha ID does not exist",
            "CAPCHA_NOT_READY": "Solución del captcha no está lista aún",
        }

    def create_status(self, captcha):
        return (
            f"{WHITE}{self.bal_xv} - [ "
            f"{RED}{self.tokens_failed}{WHITE} / "
            f"{GREEN}{self.tokens_spent}{WHITE} ]  "
            f"| {captcha.upper()} |"
        )

    def getbalance(self):
        while True:
            try:
                response = requests.get(
                    f"{self.base_url}/res.php?key={self.apikey}&action=getbalance",
                    timeout=30,
                )
                balance = float(response.text)
                return balance
            except (requests.exceptions.RequestException, ValueError):
                sleep(1)
                continue

    def create_task(self, **kwargs):
        method_key = self.method.lower()

        if method_key not in self.pending_task:
            self.pending_task[method_key] = []

        self.bal_xv = self.getbalance()

        if self.bal_xv < 0.0001:
            print(
                f"{BRIGHT}{RED}INSUFFICIENT BALANCE{BRIGHT}{RED}:{WHITE} {self.bal_xv}{RESET}"
            )
            sleep(5)
            return None

        if self.pending_task[method_key]:
            self.current_task_id = self.pending_task[method_key].pop(0)
            self.is_reusing_task = True
            return self.current_task_id

        params = {"key": self.apikey, "method": self.method, "body": kwargs["body"]}

        try:
            response = requests.post(
                f"{self.base_url}/in.php", data=params, timeout=30
            ).text

            if "OK|" in response:
                self.current_task_id = response.split("|")[1]
                self.is_reusing_task = False
                return self.current_task_id

            for error_code, error_desc in self.all_errors.items():
                if error_code in response:
                    if error_code in [
                        "ERROR_ZERO_BALANCE",
                        "ERROR_KEY_DOES_NOT_EXIST",
                        "ERROR_WRONG_USER_KEY",
                        "ERROR_METHOD_DOES_NOT_EXIST",
                    ]:
                        remaining(
                            5, text=f"CRITICAL ERROR: {error_code}", counter=False
                        )
                        return None

                    return None

            return None

        except requests.exceptions.RequestException:
            return None

    def result(self, task_id):
        if not task_id:
            return None

        max_attempts = 70
        if len(self.resolution_times) >= 10:
            avg_time = sum(self.resolution_times[-10:]) / 10
            max_attempts = min(70, max(10, int(avg_time)))

        captcha_type = self.method.upper()

        while self.resolver_attemps < max_attempts:
            try:
                response = requests.get(
                    f"{self.base_url}/res.php?key={self.apikey}&id={task_id}&action=get",
                    timeout=30,
                ).text

                if "CAPCHA_NOT_READY" in response:
                    self.resolver_attemps += 1
                    remaining(
                        1,
                        text=f"CAPTCHA NOT READY [{self.resolver_attemps}/{max_attempts}]",
                        counter=False,
                    )
                    continue

                elif "OK|" in response:
                    if self.current_task_id in self.pending_task.get(
                        self.method.lower(), []
                    ):
                        self.pending_task[self.method.lower()].remove(
                            self.current_task_id
                        )

                    self.tokens_spent += 1
                    self.resolver_attemps = 0
                    self.resolution_times.append(self.resolver_attemps + randint(5, 10))
                    self.is_reusing_task = False

                    remaining(2, text=f"RESULT {captcha_type} RECEIVED", counter=False)
                    self.bal_xv = self.getbalance()

                    token = response.split("|")[1].strip()
                    return token

                error_found = False
                for error_code, error_desc in self.all_errors.items():
                    if error_code in response:
                        error_found = True
                        if (
                            not self.is_reusing_task
                            and self.current_task_id
                            and error_code
                            in [
                                "WRONG_RESULT",
                                "ERROR_CAPTCHA_UNSOLVABLE",
                                "WRONG_CAPTCHA_ID",
                                "ERROR_WRONG_CAPTCHA_ID",
                            ]
                        ):
                            self.pending_task[self.method.lower()].append(
                                self.current_task_id
                            )
                        break

                if not error_found:
                    pass

                self.resolver_attemps = 0
                self.is_reusing_task = False
                return None

            except requests.exceptions.RequestException:
                sleep(2)
                continue

        if not self.is_reusing_task and self.current_task_id:
            self.pending_task[self.method.lower()].append(self.current_task_id)

        self.resolver_attemps = 0
        self.resolution_times.append(max_attempts + randint(5, 10))
        remaining(3, text="MAX ATTEMPTS REACHED - TIMEOUT", counter=False)
        return None

    def resolver_challenge(self, method, **kwargs):
        self.method = method
        captcha_name = "WORKCASH"
        remaining(2, text=f"SOLVING {captcha_name}")
        task_id = self.create_task(**kwargs)
        result = self.result(task_id)
        return result


class LuckyWatch(Xevil):
    def __init__(self):
        super().__init__()
        self.session = requests.Session(impersonate="chrome116")
        self.cookies = files("Cookies")
        self.cookies = {
            pair[0].strip(): pair[1].strip()
            for pair in (cookie.split("=") for cookie in self.cookies.split("; "))
        }
        self.session.cookies.update(self.cookies)
        self.claims = 0

    def get_user_info(self):
        response = json.loads(
            curl(
                "https://luckywatch.pro/api/user/",
                method="POST",
                data={"method": "getCurrentUser"},
                session=self.session,
            ).text
        )

        status = response["status"]
        data = response.get("data", {})
        message = response.get("message", "")

        if status == "ok" and data:
            return response["data"]
        elif status == "error":
            if "limitInHour" in message or "limitInDay" in message:
                remaining(3650, text=message.upper(), counter=True)
                return None

        print(response)
        exit()

    def get_task_info(self):
        remaining(text="GETTING TASK INFO")
        response = json.loads(
            curl(
                "https://luckywatch.pro/api/user/tasks/",
                method="POST",
                data={"method": "get", "mac": "0"},
                session=self.session,
            ).text
        )

        status = response["status"]
        data = response.get("data", {})
        message = response.get("message", "")

        if status == "ok" and data:
            return response["data"]
        elif status == "error":
            if "limitInHour" in message or "limitInDay" in message:
                remaining(3650, text=message.upper(), counter=True)
                return None

        print(response)
        exit()

    def start_task(self, TaskId, Duration):
        remaining(text=f"STARTING TASK ID:{TaskId}")

        payload = {"TaskId": TaskId}
        payload.update(self.fingerprint)

        curl(
            "https://luckywatch.pro/api/user/tasks/start/",
            method="POST",
            data=payload,
            session=self.session,
        )

        remaining(Duration, text=f"VIEWING VIDEO ID: {TaskId}", counter=True)

    def claim_view_video(self):
        while True:
            remaining(text="GETTING VIDEOS INFO")

            data = self.get_task_info()

            if data is None:
                continue

            self.start_task(str(data["id"]), int(data["duration"]))

            response = json.loads(
                curl(
                    "https://luckywatch.pro/api/user/captcha/check/",
                    method="POST",
                    data={"refreshTask": "0"},
                    session=self.session,
                ).text
            )

            if response["status"] == "data":
                result = self.resolver_challenge(
                    method="workcash",
                    **{
                        "body": combined_img(
                            response["data"]["image"], response["data"]["queue"]
                        )
                    },
                )

                if result is None:
                    continue

                result = result.replace("coordinate:", "").strip().split(";")

                if len(result) != 3:
                    remaining(text="BAD CAPTCHA")
                    continue

                payload = {}
                str_coors = ""

                for index, pair in enumerate(result):
                    x, y = pair.split(",")
                    x_val = x.split("=")[1].strip()
                    y_val = y.split("=")[1].strip()
                    str_coors += f"x:{x_val}y:{y_val}"

                    if index != len(result) - 1:
                        str_coors += " | "

                    payload[f"coor[{index}][x]"] = x_val
                    payload[f"coor[{index}][y]"] = y_val

                response = json.loads(
                    curl(
                        "https://luckywatch.pro/api/user/captcha/check/",
                        method="POST",
                        data=payload,
                        session=self.session,
                    ).text
                )

                if response["status"] != "ok":
                    print("❌ CLAIM FAILED BY CAPTCHA")
                    print(f"Payload: {payload}")
                    exit()

                now = get_time()

                print(
                    f"{now} {CYAN}| "
                    f"{RED}CAPTCHA DETECTED! {CYAN}| "
                    f"{YELLOW}COORS{RED}: {WHITE}{str_coors}{RESET}"
                )

            elif response["status"] != "ok":
                print(response)
                exit()

            remaining(2, text="SUCCESSFULLY CLAIMED VIDEO")

            now = get_time()
            self.claims += 1

            reward = response["data"]["reward"]
            balance = self.get_user_info()["balance"]

            print(
                f"{now} {CYAN}| "
                f"{MAGENTA}{self.claims} {CYAN}| "
                f"{YELLOW}REWARD{RED}: {WHITE}{reward} {GREEN}USD{CYAN} | "
                f"{YELLOW}BALANCE{RED}: {WHITE}{balance} {GREEN}USD{RESET}"
            )


def curl(url, method="GET", **kwargs):
    session = kwargs["session"]
    data = kwargs.get("data")
    headers = {
        "User-Agent": "Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36"
    }

    while True:
        try:
            response = session.request(method.upper(), url, headers=headers, data=data)
            return response
        except requests.exceptions.Timeout:
            remaining(5, text="TIMEOUT REACHED")
            continue
        except requests.exceptions.HTTPError:
            remaining(10, text=f"CLIENT ERROR: {response.status_code}")
        except requests.exceptions.ConnectionError:
            remaining(10, text="RECONNECTING...")
            continue
        except requests.exceptions.RequestException as e:
            print(str(e))
        except Exception as e:
            print(str(e))


def remaining(seconds=None, text=None, counter=False, stop=False):
    spin_chars, spin_idx = ["|", "/", "-", "\\"], 0
    sys.stdout.write("\033[?25l")
    sys.stdout.flush()

    if seconds is None:
        seconds = 1

    seconds = 1 if text and seconds <= 0 else int(seconds)
    while seconds > 0:
        for _ in range(10):
            h, m, s = seconds // 3600, (seconds % 3600) // 60, seconds % 60
            time_part = f"{CYAN}{m:02d}m{choice(COLORS)}:{choice(COLORS)}{s:02d}s"
            if h > 0:
                time_part = f"{CYAN}{h}h{choice(COLORS)}:{time_part}"

            rnd_color = choice(COLORS)
            spinner = f"{rnd_color}[{spin_chars[spin_idx]}{rnd_color}]{WHITE}"

            if text is None:
                display_text = f"{BRIGHT}{YELLOW}REMAINING{WHITE}: {rnd_color}({time_part}{rnd_color})"
            else:
                text_part = f"{rnd_color}{text}"
                display_text = (
                    f"{BRIGHT}{rnd_color}({time_part}{rnd_color}) {text_part}"
                    if counter
                    else text_part
                )

            sys.stdout.write(f"\r{spinner} {display_text}{RESET}\033[K")
            sys.stdout.flush()
            spin_idx = (spin_idx + 1) % len(spin_chars)
            sleep(0.1)

        seconds -= 1

    sys.stdout.write("\n" if stop else "\033[2K\r\033[?25h\033[K")
    sys.stdout.flush()
    if stop:
        exit()


def get_time():
    now = datetime.now()
    day = now.strftime("%d")
    month = now.strftime("%b")
    year = now.strftime("%Y")
    current_time = now.strftime("%H:%M:%S")

    return (
        f"{WHITE}{day}{RESET}"
        f"{RED}/{RESET}"
        f"{YELLOW}{month}{RESET}"
        f"{RED}/{RESET}"
        f"{WHITE}{year}{RESET}"
        f"{WHITE} {RESET}"
        f"{WHITE}{current_time.replace(':', f'{RED}:{WHITE}')}{RESET}"
    )


def files(file):
    try:
        with open(file, "r") as f:
            return f.read().strip()
    except FileNotFoundError:
        content = input(f"{YELLOW}{file}: {RESET}").strip()
        with open(file, "w") as f:
            f.write(content)
        print(f"File {file} created")
        return content


if __name__ == "__main__":
    bot = LuckyWatch()
    os.system("clear" if os.name == "posix" else "cls")
    bot.claim_view_video()
