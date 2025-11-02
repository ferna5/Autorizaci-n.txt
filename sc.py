import requests
import time
import re
import random
import os
from datetime import datetime
from PIL import Image
import io

# === CONFIGURACIÓN ===
BASE = "https://teaserfast.ru"

# === FUNCIONES PARA LEER CONFIGURACIÓN DEL PHP ===
def read_php_config():
    """Leer la configuración de los archivos que usa el PHP"""
    config = {
        'cookie': '',
        'user_agent': ''
    }
    
    try:
        # Leer cookie del archivo data/cookie.txt
        if os.path.exists('data/cookie.txt'):
            with open('data/cookie.txt', 'r', encoding='utf-8') as f:
                config['cookie'] = f.read().strip()
            print("✅ Cookie leída de data/cookie.txt")
        else:
            print("❌ Archivo data/cookie.txt no encontrado")
            
        # Leer user agent del archivo data/user_agent.txt
        if os.path.exists('data/user_agent.txt'):
            with open('data/user_agent.txt', 'r', encoding='utf-8') as f:
                config['user_agent'] = f.read().strip()
            print("✅ User Agent leído de data/user_agent.txt")
        else:
            # User agent por defecto (el mismo que usa el PHP)
            config['user_agent'] = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36"
            print("ℹ️  User Agent por defecto")
            
    except Exception as e:
        print(f"❌ Error leyendo configuración: {e}")
        
    return config

def get_username_from_cookie(cookie):
    """Extraer el nombre de usuario de las cookies para verificación"""
    try:
        # Buscar user_id en las cookies
        if 'user_id=' in cookie:
            user_id_match = re.search(r'user_id=(\d+)', cookie)
            if user_id_match:
                return f"User_{user_id_match.group(1)}"
    except:
        pass
    return "Usuario"

# === INICIALIZACIÓN ===
print("🔧 Cargando configuración desde archivos PHP...")
config = read_php_config()

if not config['cookie']:
    print("❌ No se pudo cargar la cookie. Ejecuta primero el PHP para configurar.")
    exit()

# === SESIÓN ===
session = requests.Session()
session.headers.update({
    "User-Agent": config['user_agent'],
    "Referer": f"{BASE}/check-captcha/",
})

# Configurar cookies desde el archivo
cookies_dict = {}
for c in config['cookie'].split(";"):
    c = c.strip()
    if "=" in c:
        key, value = c.split("=", 1)
        cookies_dict[key] = value
session.cookies.update(cookies_dict)

print(f"👤 Usuario: {get_username_from_cookie(config['cookie'])}")
print(f"🌐 User Agent: {config['user_agent'][:50]}...")

def download_captcha():
    """Descargar captcha con sesión"""
    try:
        captcha_url = f"{BASE}/s2captcha/?rnd={int(time.time()*1000)}"
        response = session.get(captcha_url)
        
        if response.status_code == 200 and len(response.content) > 5000:
            print("✅ Captcha descargado")
            return response.content
        else:
            print(f"❌ Captcha vacío ({len(response.content)} bytes)")
            return None
    except Exception as e:
        print(f"❌ Error: {e}")
        return None

def analyze_captcha_simple(image_data):
    """Análisis simple del captcha"""
    try:
        image = Image.open(io.BytesIO(image_data))
        width, height = image.size
        print(f"📐 Tamaño: {width}x{height}")
        
        # Guardar para análisis
        image.save('captcha_actual.jpg')
        
        # Convertir a escala de grises
        gray_image = image.convert('L')
        pixels = gray_image.load()
        
        # Buscar áreas con texto
        dark_areas = []
        block_size = 10
        
        for y in range(0, height - block_size, block_size):
            for x in range(0, width - block_size, block_size):
                dark_count = 0
                total_pixels = 0
                
                for dy in range(block_size):
                    for dx in range(block_size):
                        if y + dy < height and x + dx < width:
                            pixel = pixels[x + dx, y + dy]
                            if pixel < 128:
                                dark_count += 1
                            total_pixels += 1
                
                if total_pixels > 0 and dark_count / total_pixels > 0.3:
                    center_x = x + block_size // 2
                    center_y = y + block_size // 2
                    dark_areas.append((center_x, center_y, dark_count))
        
        print(f"🔍 Áreas oscuras: {len(dark_areas)}")
        
        if not dark_areas:
            return None
        
        # Ordenar por densidad
        dark_areas.sort(key=lambda area: area[2], reverse=True)
        top_areas = dark_areas[:5]
        
        if len(top_areas) >= 3:
            # Buscar área más central
            image_center_x = width // 2
            image_center_y = height // 2
            most_central = min(top_areas, 
                             key=lambda area: abs(area[0] - image_center_x) + abs(area[1] - image_center_y))
            coords = f"{most_central[0]}:{most_central[1]}"
            print(f"📍 Coordenadas: {coords}")
        else:
            darkest = top_areas[0]
            coords = f"{darkest[0]}:{darkest[1]}"
            print(f"📍 Coordenadas: {coords}")
        
        return coords
        
    except Exception as e:
        print(f"❌ Error analizando: {e}")
        return None

def submit_solution_and_check(coords):
    """Enviar solución y verificar CORRECTAMENTE si tuvo éxito"""
    print(f"📤 Enviando: {coords}")
    
    post_data = {
        "captha_r": coords,
        "js_on": str(datetime.now().year),
        "captha_rc": "",
        "captha_m": "159",
        "captha_s": "159", 
        "captha_o": "164",
        "captha_nm": "9170", 
        "captha_mmm": "9170",
        "cptha_mn": "190",
        "ctptha_nm": "196",
        "submit_captha": "submit"
    }
    
    try:
        headers = {
            "Content-Type": "application/x-www-form-urlencoded",
            "Origin": BASE,
            "Referer": f"{BASE}/check-captcha/",
            "User-Agent": config['user_agent']
        }
        
        resp = session.post(f"{BASE}/check-captcha/", data=post_data, headers=headers, allow_redirects=True)
        
        # === DETECCIÓN MEJORADA DE ÉXITO ===
        
        # 1. Verificar si fue redireccionado FUERA del captcha
        if "check-captcha" not in resp.url:
            print("✅ ¡ÉXITO! Redireccionado fuera del captcha")
            return True
        
        # 2. Verificar si la página principal carga (publicidad activa)
        if "Реклама" in resp.text and "Основной счет" in resp.text:
            print("✅ ¡ÉXITO! Página principal detectada")
            return True
        
        # 3. Verificar si el mensaje de captcha desapareció
        if "найдите заданное двузначное число" not in resp.text:
            print("✅ ¡ÉXITO! Mensaje de captcha desapareció")
            return True
        
        # 4. Verificar si estamos en el dashboard principal
        if 'class="main_user_login"' in resp.text:
            print("✅ ¡ÉXITO! Dashboard principal detectado")
            return True
        
        # 5. Verificar intentos (si NO aparece el mensaje de intentos = éxito)
        attempts_match = re.search(r'Осталось попыток: (\d+)', resp.text)
        if not attempts_match:
            print("✅ ¡ÉXITO! No hay mensaje de intentos (captcha superado)")
            return True
        
        # Si hay mensaje de intentos, verificar cuántos quedan
        attempts = attempts_match.group(1)
        print(f"📊 Intentos restantes: {attempts}")
        
        if attempts == "3":
            print("✅ ¡ÉXITO! Intentos se mantienen en 3")
            return True
        
        return False
        
    except Exception as e:
        print(f"❌ Error: {e}")
        return False

def solve_captcha_optimized():
    """Solución optimizada - se detiene al primer éxito"""
    print("🎯 SOLUCIÓN OPTIMIZADA - SE DETIENE AL ÉXITO")
    print("=" * 50)
    
    # Verificar estado inicial
    print("🔍 Verificando estado inicial...")
    initial = session.get(f"{BASE}/check-captcha/")
    
    # Si ya no estamos en la página de captcha, ¡ya está resuelto!
    if "check-captcha" not in initial.url:
        print("✅ ¡CAPTCHA YA ESTÁ RESUELTO!")
        return True
    
    # Verificar si la sesión es válida
    if "main_user_login" not in initial.text and "user_id" not in config['cookie']:
        print("❌ Sesión inválida o cookie expirada")
        return False
    
    attempts_match = re.search(r'Осталось попыток: (\d+)', initial.text)
    if attempts_match:
        attempts = int(attempts_match.group(1))
        print(f"📊 Intentos disponibles: {attempts}")
        if attempts == 0:
            print("🚫 Sin intentos")
            return False
    
    # ESTRATEGIA 1: Análisis de imagen (SOLO 1 INTENTO)
    print("\n🎯 Estrategia 1: Análisis inteligente")
    captcha_data = download_captcha()
    
    if captcha_data:
        coords = analyze_captcha_simple(captcha_data)
        if coords and submit_solution_and_check(coords):
            return True  # ¡ÉXITO! Se detiene aquí
    
    # ESTRATEGIA 2: Coordenadas de alta probabilidad (SOLO 1 INTENTO)
    print("\n🎯 Estrategia 2: Coordenadas inteligentes")
    high_probability_coords = [
        "150:150", "140:140", "160:160", "130:130", 
        "120:120", "180:180", "100:100", "200:200"
    ]
    
    # Probar SOLO la mejor coordenada
    best_coord = high_probability_coords[0]
    print(f"📍 Mejor coordenada: {best_coord}")
    
    if submit_solution_and_check(best_coord):
        return True  # ¡ÉXITO! Se detiene aquí
    
    print("💔 No se pudo resolver en 2 intentos optimizados")
    return False

# === PROGRAMA PRINCIPAL ===
print("🚀 SOLUCIÓN OPTIMIZADA - MÁXIMA EFICIENCIA")
print("⭐ Solo 2 intentos máximo")
print("=" * 60)

# Verificar dependencias
try:
    from PIL import Image
    print("✅ Dependencias OK")
except ImportError:
    print("❌ Instala: pip install pillow")
    print("❌ O en Termux: pkg install python-pillow")
    exit()

# Verificar sesión
try:
    test = session.get(BASE, timeout=10)
    username = get_username_from_cookie(config['cookie'])
    
    # Verificación más flexible de sesión
    if test.status_code == 200 and ("main_user_login" in test.text or "Основной счет" in test.text):
        print(f"✅ Sesión activa - {username}")
    else:
        print("❌ Sesión inválida o expirada")
        print("💡 Ejecuta primero php run.php para configurar las cookies")
        exit()
        
except Exception as e:
    print(f"❌ Error de conexión: {e}")
    exit()

# Ejecutar solución OPTIMIZADA
success = solve_captcha_optimized()

# Verificación final
print("\n" + "=" * 60)
if success:
    print("🎉 ¡CAPTCHA RESUELTO EXITOSAMENTE!")
    print("✅ Publicidad activada")
    print("✅ Puedes continuar navegando")
    
    # Verificar balance actual
    try:
        dashboard = session.get(BASE)
        balance_match = re.search(r'id="basic_balance[^>]*title="[^"]*">([^<]+)</span>', dashboard.text)
        if balance_match:
            print(f"💰 Balance actual: {balance_match.group(1)}")
    except:
        pass
else:
    print("💔 No se pudo resolver el captcha automáticamente")
    print("📸 Revisa 'captcha_actual.jpg' para análisis manual")
    print("🔗 Resuelve manualmente en: https://teaserfast.ru/check-captcha")

print("=" * 60)
