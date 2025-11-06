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
YELLOW = "\033[1;33m"
MAGENTA = "\033[1;35m"

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

def run_auto_withdraw():
    """Ejecuta auto.php para retiro automático"""
    print(f"\n{MAGENTA}[💰]{WHITE} Balance alcanzó 15 RUB - Ejecutando retiro automático...{RESET}")
    print(MAGENTA + "──────────────────────────────────────────────────" + RESET)
    
    result = subprocess.run(
        ["php", "auto.php"], 
        capture_output=True, 
        text=True
    )
    
    # Mostrar salida del auto.php
    for line in result.stdout.split('\n'):
        if line.strip():
            print(f"{MAGENTA}[AUTO]{WHITE} {line}{RESET}")
    
    if result.stderr:
        for line in result.stderr.split('\n'):
            if line.strip():
                print(f"{MAGENTA}[AUTO-ERROR]{WHITE} {line}{RESET}")
    
    print(MAGENTA + "──────────────────────────────────────────────────" + RESET)
    print(f"{MAGENTA}[✔]{WHITE} Retiro automático completado{RESET}")
    time.sleep(2)

def print_separator():
    print(GREEN + "──────────────────────────────────────────────────" + RESET)

def extract_balance(line):
    """Extrae el valor del balance de una línea de texto"""
    # Buscar patrones comunes de balance
    patterns = [
        r'Balance:\s*([\d.]+)\s*RUB',
        r'Balance\s*[:\-]\s*([\d.]+)\s*RUB',
        r'([\d.]+)\s*RUB',
        r'Баланс:\s*([\d.]+)'
    ]
    
    for pattern in patterns:
        match = re.search(pattern, line, re.IGNORECASE)
        if match:
            try:
                return float(match.group(1))
            except ValueError:
                continue
    return None

def main():
    clear_console()
    print(f"{CYAN}🚀 Ejecutando run.php y monitoreando salida...{RESET}")
    print(f"{YELLOW}[ℹ]{WHITE} Auto-retiro activado - Se ejecutará al alcanzar 15 RUB{RESET}")
    time.sleep(1)
    
    last_balance_check = 0
    balance_check_interval = 30  # Verificar balance cada 30 segundos
    withdraw_threshold = 15.0
    last_known_balance = 0

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
                    sys.stdout.write(f"\r{GREEN}[⏳]{WHITE} {countdown_value} | Balance: {last_known_balance:.2f} RUB   ")
                    sys.stdout.flush()
                    last_update = now
                continue

            # Detectar captcha o reinicio
            if "https://teaserfast.ru/check-captcha" in line or "Captcha detected" in line:
                process.kill()
                run_solver()
                break

            # Verificar balance periódicamente
            current_time = time.time()
            if current_time - last_balance_check >= balance_check_interval:
                balance = extract_balance(line)
                if balance is not None:
                    last_known_balance = balance
                    last_balance_check = current_time
                    
                    # Actualizar display del contador con el balance actual
                    if countdown_value:
                        sys.stdout.write(f"\r{GREEN}[⏳]{WHITE} {countdown_value} | Balance: {balance:.2f} RUB   ")
                        sys.stdout.flush()
                    
                    # Ejecutar auto retiro si alcanza el umbral
                    if balance >= withdraw_threshold:
                        process.kill()
                        run_auto_withdraw()
                        # Esperar un poco después del retiro antes de continuar
                        time.sleep(5)
                        break

            # Omitir encabezados innecesarios
            if any(skip in line for skip in [
                "TeaserFast Bot v",
                "Register ::",
                "Starting Extension Popup",
                "===",
                "Auto withdraw",
                "Withdraw completed",
            ]):
                continue

            # Evitar repetir la misma línea
            if line == last_line:
                continue
            last_line = line

            # Extraer balance de líneas relevantes
            balance = extract_balance(line)
            if balance is not None:
                last_known_balance = balance
                # Mostrar balance actualizado
                print(f"{YELLOW}[💰]{WHITE} Balance actual: {balance:.2f} RUB{RESET}")

            # Bloques especiales de salida (earn / Balance)
            if "earn" in line.lower() or "balance" in line.lower():
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