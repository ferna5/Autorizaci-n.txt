import subprocess
import os
import sys
import time
import re

# === COLORES ===
GREEN = "\033[1;32m"
WHITE = "\033[1;37m"
RESET = "\033[0m"
CYAN = "\033[1;36m"

def colorize_text(text):
    """Pinta caracteres especiales en verde y texto normal en blanco"""
    return ''.join(
        f"{GREEN}{c}{RESET}" if c in "[]|/\\─—=><:!-" else f"{WHITE}{c}{RESET}"
        for c in text
    )

def clear_console():
    os.system('cls' if os.name == 'nt' else 'clear')

def run_php():
    """Ejecuta run.php limpiando formato ANSI"""
    return subprocess.Popen(
        ["php", "run.php"],
        stdout=subprocess.PIPE,
        stderr=subprocess.STDOUT,
        text=True,
        bufsize=1
    )

def run_solver():
    """Ejecuta sc.py con formato bonito"""
    clear_console()
    print(GREEN + "──────────────────────────────────────────────────" + RESET)
    subprocess.run(["python", "sc.py"], text=True)
    print(f"{GREEN}[✔]{WHITE} Bypass Captcha success")
    print(f"{GREEN}---[✔]{WHITE} CAPTCHA SOLVED! Ads active")
    print(GREEN + "──────────────────────────────────────────────────" + RESET)
    time.sleep(2)

def print_separator():
    print(GREEN + "──────────────────────────────────────────────────" + RESET)

def main():
    clear_console()
    print(f"{CYAN}🚀 Ejecutando run.php y monitoreando salida...{RESET}")
    time.sleep(1)

    while True:
        process = run_php()
        last_line = ""
        last_update = 0
        countdown_value = None

        for raw_line in iter(process.stdout.readline, ''):
            # Limpia los códigos ANSI del PHP
            line = re.sub(r'\x1B\[[0-9;]*[A-Za-z]', '', raw_line).strip()
            if not line:
                continue

            # Detectar contador tipo 00:00:00
            match = re.search(r"\b\d{2}:\d{2}:\d{2}\b", line)
            if match:
                countdown_value = match.group(0)
                now = time.time()

                if countdown_value == "00:00:00":
                    # Borra la línea del contador al llegar a cero
                    sys.stdout.write("\r\033[K")
                    sys.stdout.flush()
                    continue

                # Mostrar cuenta regresiva solo si pasa 1 segundo
                if now - last_update >= 1:
                    sys.stdout.write(f"\r{GREEN}[⏳]{WHITE} {countdown_value}   ")
                    sys.stdout.flush()
                    last_update = now
                continue

            # Detectar captcha o reinicio
            if "https://teaserfast.ru/check-captcha" in line or "Captcha detected" in line:
                process.kill()
                run_solver()
                break

            # Omitir encabezados innecesarios
            if any(skip in line for skip in [
                "TeaserFast Bot v",
                "Register ::",
                "Starting Extension Popup",
                "===",
                "===",
            ]):
                continue

            # Evitar repetir la misma línea
            if line == last_line:
                continue
            last_line = line

            # Bloques especiales de salida (earn / Balance)
            if "earn" in line or "Balance" in line:
                print(GREEN + "───────────────────────────────────────────────" + RESET)
                print(colorize_text(line))
                print(GREEN + "───────────────────────────────────────────────" + RESET)
                continue

            # Username con línea decorativa
            if "Username" in line:
                print_separator()
                print(colorize_text(line))
                continue

            # Mostrar todo lo demás
            print(colorize_text(line))

        time.sleep(1)

if __name__ == "__main__":
    main()