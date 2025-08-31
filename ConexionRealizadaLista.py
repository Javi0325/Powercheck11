import network
import uasyncio as asyncio
import framebuf
import time
import urequests
import json
from machine import Pin, I2C
from lib.logos import corazon, angulo, dibujar_icono, img_data
from time import ticks_us, ticks_diff
from sh1106 import sh1106
from lib.bmp280.bmp280 import BMP280
from max30102 import MAX30102

# ---------- CONFIG RED / API (AJUSTA ESTO) ----------
WIFI_SSID = "COMTECO-N4751624"
WIFI_PASS = "MPYER74585"
API_BASE  = "http://192.168.1.84:8000"   # Host/IP de tu Laravel
ATHLETE_ID = None                        # lo setea el heartbeat

# ---------- I2C / Sensores ----------
i2c = I2C(0, scl=Pin(22), sda=Pin(21))
pantalla = sh1106.SH1106_I2C(128, 64, i2c)
pulsometro = MAX30102(i2c)
sensor = BMP280(i2c, sea_level=101325)
motor = Pin(5, Pin.OUT)

# ---------- Parámetros generales ----------
MAX_HISTORY = 15
history = []
beats_history = []
beat = False
beats = 0.0
t_start = ticks_us()
valor_anterior = None

# ---------- Repeticiones ----------
SEA_LEVEL = 101325
THRESH_DOWN = -0.30
THRESH_UP   = -0.05
ALPHA       = 0.4
state = "normal"
count = 0
alt_filt = 0

# ---------- Buffer imagen ----------
buffer = bytearray(img_data)
fb = framebuf.FrameBuffer(buffer, 128, 64, framebuf.MONO_VLSB)
titulo = ""

# ---------- Botones ----------
boton_modo = Pin(18, Pin.IN, Pin.PULL_UP)
boton_inicio = Pin(17, Pin.IN, Pin.PULL_UP)
boton_conectar = Pin(16, Pin.IN, Pin.PULL_UP)  # <- ESTE DISPARA WIFI + PING + HEARTBEAT
boton_enviar = Pin(15, Pin.IN, Pin.PULL_UP)

medicion_activa = False
tareas = []
modo_actual = 0  # 0 = menú principal, 1 a 3 = ejercicios
ejecucion_activa = False
iniciar = 0
estado_boton_anterior = 1
tarea_sensor = None
tarea_repeticiones = None
tarea_mostrar = None
tarea_heartbeat = None

# ---------- Helpers de RED / API ----------
async def wifi_connect_if_needed(ssid=WIFI_SSID, password=WIFI_PASS):
    wlan = network.WLAN(network.STA_IF)
    if not wlan.active():
        wlan.active(True)
        await asyncio.sleep_ms(50)
    if not wlan.isconnected():
        print("Conectando a WiFi...")
        try:
            wlan.connect(ssid, password)
        except Exception as e:
            print("Error connect:", e)
        t0 = time.ticks_ms()
        while not wlan.isconnected() and time.ticks_diff(time.ticks_ms(), t0) < 15000:
            await asyncio.sleep_ms(250)   # YIELD (no bloquea)
    if wlan.isconnected():
        print("WiFi OK:", wlan.ifconfig())
        return True
    print("No se pudo conectar a WiFi")
    return False
async def _http_post_json(url, payload, headers=None, timeout_ms=2000):
    # Parser MUY simple para http://host:puerto/ruta
    assert url.startswith("http://")
    s = url[7:]
    if "/" in s:
        hostport, path = s.split("/", 1)
        path = "/" + path
    else:
        hostport, path = s, "/"
    if ":" in hostport:
        host, port = hostport.split(":", 1)
        port = int(port)
    else:
        host, port = hostport, 80

    try:
        reader, writer = await asyncio.open_connection(host, port)
    except Exception as e:
        print("open_connection error:", e)
        return 0, ""

    try:
        body = json.dumps(payload)
        # Construimos la petición HTTP
        req = (
            "POST {path} HTTP/1.1\r\n"
            "Host: {host}\r\n"
            "Content-Type: application/json\r\n"
            "Content-Length: {cl}\r\n"
            "Connection: close\r\n"
        ).format(path=path, host=host, cl=len(body))

        # Headers extra si vinieran
        if headers:
            for k, v in headers.items():
                if k.lower() not in ("host", "content-type", "content-length", "connection"):
                    req += "{}: {}\r\n".format(k, v)
        req += "\r\n" + body

        writer.write(req.encode())
        await writer.drain()

        # Leemos hasta headers completos
        t0 = time.ticks_ms()
        buf = b""
        while b"\r\n\r\n" not in buf:
            if time.ticks_diff(time.ticks_ms(), t0) > timeout_ms:
                raise Exception("timeout headers")
            chunk = await reader.read(128)
            if not chunk:
                break
            buf += chunk

        if b"\r\n\r\n" not in buf:
            # Sin headers completos
            writer.close()
            return 0, ""

        headers_raw, rest = buf.split(b"\r\n\r\n", 1)
        try:
            status_line = headers_raw.split(b"\r\n", 1)[0].decode()
            status_code = int(status_line.split(" ")[1])
        except:
            status_code = 0

        # ¿Content-Length?
        cl = None
        for line in headers_raw.split(b"\r\n")[1:]:
            if line.lower().startswith(b"content-length:"):
                try:
                    cl = int(line.split(b":", 1)[1].strip())
                except:
                    cl = None
                break

        body_bytes = rest
        if cl is not None:
            # lee exactamente Content-Length
            while len(body_bytes) < cl:
                if time.ticks_diff(time.ticks_ms(), t0) > timeout_ms:
                    break
                chunk = await reader.read(cl - len(body_bytes))
                if not chunk:
                    break
                body_bytes += chunk
        else:
            # lee hasta EOF
            while True:
                if time.ticks_diff(time.ticks_ms(), t0) > timeout_ms:
                    break
                chunk = await reader.read(256)
                if not chunk:
                    break
                body_bytes += chunk

        try:
            body_text = body_bytes.decode()
        except:
            body_text = ""

        writer.close()
        try:
            await writer.wait_closed()
        except:
            pass

        return status_code, body_text

    except Exception as e:
        print("http error:", e)
        try:
            writer.close()
        except:
            pass
        return 0, ""
async def ping_api(name="ESP32-Atleta"):
    global ATHLETE_ID
    url = API_BASE + "/api/devices/ping"
    status, txt = await _http_post_json(url, {"name": name})
    print("Ping:", status, txt)
    if 200 <= status < 300:
        try:
            data = json.loads(txt)
            assigned = data.get("assigned_athlete_id", None)
            if assigned:
                ATHLETE_ID = int(assigned)
                print("Asignado a atleta:", ATHLETE_ID)
            # Si quieres limpiar cuando ya no hay sesión:
            # else:
            #     ATHLETE_ID = None
        except Exception as e:
            print("JSON error:", e)
        return True
    return False

async def enviar_metricas(bpm, repeticiones):
    if ATHLETE_ID is None:
        print("Sin atleta asignado; no se envían métricas.")
        return False
    url = API_BASE + "/api/devices/metrics"
    status, txt = await _http_post_json(url, {"bpm": bpm, "repeticiones": repeticiones})
    print("Metrics:", status, txt)
    return 200 <= status < 300

# ---------- Heartbeat periódico ----------
async def heartbeat_loop():
    while True:
        try:
            if await wifi_connect_if_needed():
                await ping_api()
        except Exception as e:
            print("Heartbeat error:", e)
        await asyncio.sleep_ms(10000)  # 10 s

# ---------- Sensores / UI ----------
async def get_max30102_values():
    global history, beats_history, beat, beats, t_start
    pulsometro.wakeup()
    pulsometro.setup_sensor()
    while True:
        pulsometro.check()
        if pulsometro.available():
            red_reading = pulsometro.pop_red_from_storage()
            history.append(red_reading)
            history = history[-MAX_HISTORY:]

            minima, maxima = min(history), max(history)
            threshold_on = minima + (maxima - minima) * 0.6
            threshold_off = minima + (maxima - minima) * 0.4

            if red_reading > 1000:
                if not beat and red_reading > threshold_on:
                    beat = True
                    t_us = ticks_diff(ticks_us(), t_start)
                    bpm_inst = 60 / (t_us / 1_000_000)
                    if bpm_inst < 500:
                        t_start = ticks_us()
                        beats_history.append(bpm_inst)
                        if len(beats_history) > MAX_HISTORY:
                            beats_history.pop(0)
                        beats = round(sum(beats_history) / len(beats_history), 2)
                elif beat and red_reading < threshold_off:
                    beat = False
            else:
                beats = 0.0
            print("BPM:", round(beats))
        await asyncio.sleep_ms(20)

async def mostrarValores():
    global beats, valor_anterior
    while True:
        pantalla.fill_rect(17, 16, 30, 8, 0)
        pantalla.text(str(round(beats)), 17, 16, 1)
        pantalla.show()
        await asyncio.sleep_ms(300)

async def conteoRepeticiones():
    global state, count, alt_filt
    while True:
        alt = sensor.getRelAltitude()
        alt_filt = ALPHA * alt + (1 - ALPHA) * alt_filt

        if state == "normal":
            if alt_filt < THRESH_DOWN:
                state = "bajando"
        elif state == "bajando":
            if alt_filt > THRESH_UP:
                count += 1
                print("Repetición válida:", count)
                state = "normal"
        print(state)
        await asyncio.sleep_ms(100)

async def mostrar_logo(tiempo_ms=1000):
    global fb
    motor.value(1)
    await asyncio.sleep_ms(200)
    motor.value(0)
    pantalla.blit(fb, 0, 0)
    pantalla.show()
    await asyncio.sleep_ms(tiempo_ms)

def mostrar_menu():
    pantalla.fill(0)
    pantalla.fill_rect(0, 0, 128, 11, 1)
    pantalla.text("POWERCHECK", 15, 2, 0)
    pantalla.fill_rect(105, 1, 15, 9, 0)
    pantalla.fill_rect(120, 2, 3, 7, 0)
    pantalla.fill_rect(106, 2, 13, 7, 1)
    pantalla.fill_rect(119, 3, 3, 5, 1)
    pantalla.text("1 SENTADILLA", 0, 16, 1)
    pantalla.text("2 PRESS BANCA", 0, 32, 1)
    pantalla.text("3 PESO MUERTO", 0, 48, 1)
    pantalla.show()

def mostrar_pantalla_ejercicio(modo):
    global titulo
    if modo == 1:
        titulo = "SENTADILLA"
    elif modo == 2:
        titulo = "PRESS BANCA"
    elif modo == 3:
        titulo = "PESO MUERTO"
    pantalla.fill(0)
    pantalla.fill_rect(0, 0, 128, 11, 1)
    pantalla.text(titulo, 2, 2, 0)
    pantalla.fill_rect(105, 1, 15, 9, 0)
    pantalla.fill_rect(120, 2, 3, 7, 0)
    pantalla.fill_rect(106, 2, 13, 7, 1)
    pantalla.fill_rect(119, 3, 3, 5, 1)
    dibujar_icono(pantalla, corazon, 0, 16)
    dibujar_icono(pantalla, angulo, 0, 32)
    pantalla.text("BPM", 50, 16, 1)
    pantalla.text("REPETICIONES", 0, 48, 1)
    pantalla.show()

# Mantengo tu función original por si la usas en otro lado, pero no la llamamos desde el botón 16
def conectar_wifi(ssid, password):
    wlan = network.WLAN(network.STA_IF)
    wlan.active(True)
    wlan.connect(ssid, password)
    print("Conectando a WiFi...")
    while not wlan.isconnected():
        time.sleep(0.5)
    ip = wlan.ifconfig()[0]
    print("Conectado:", wlan.ifconfig())

    # Registro original (ya no se usa si tienes /api/devices/ping)
    url = API_BASE + "/api/devices"
    headers = {"Content-Type": "application/json"}
    data = {"name": "ESP32-Atleta", "ip": ip}
    try:
        r = urequests.post(url, data=json.dumps(data), headers=headers)
        print("Registro Laravel:", r.text)
        r.close()
    except Exception as e:
        print("Error registrando dispositivo:", e)

# ---------- Control de botones ----------
async def checar_botones():
    global modo_actual, ejecucion_activa, iniciar, estado_boton_anterior
    global tarea_sensor, tarea_repeticiones, tarea_mostrar, tarea_heartbeat

    while True:
        # Cambio de modo
        if not boton_modo.value():
            modo_actual = (modo_actual + 1) % 4
            await mostrar_logo(500)
            if modo_actual == 0:
                mostrar_menu()
            else:
                mostrar_pantalla_ejercicio(modo_actual)
            await asyncio.sleep_ms(300)

        # Botón iniciar ON/OFF
        estado_actual = boton_inicio.value()

        if estado_boton_anterior == 1 and estado_actual == 0 and modo_actual in [1, 2, 3]:
            iniciar = 1 - iniciar
            print("Iniciar:", iniciar)

            if iniciar == 1:
                ejecucion_activa = True
                print("Iniciando sensores...")
                tarea_sensor = asyncio.create_task(get_max30102_values())
                tarea_repeticiones = asyncio.create_task(conteoRepeticiones())
                tarea_mostrar = asyncio.create_task(mostrarValores())
            else:
                ejecucion_activa = False
                print("Apagando sensores...")
                for tarea in [tarea_sensor, tarea_repeticiones, tarea_mostrar]:
                    if tarea:
                        tarea.cancel()
                        print("Tarea cancelada")
                        pulsometro.shutdown()
            await asyncio.sleep_ms(300)  # anti-rebote

        estado_boton_anterior = estado_actual

        # -------- Botón 16: CONECTAR + PING + ARRANCAR HEARTBEAT --------
        if not boton_conectar.value():  # presionado
            if wifi_connect_if_needed():
                ok = ping_api()  # POST /api/devices/ping
                # Arranca heartbeat si no está
                if (tarea_heartbeat is None) or getattr(tarea_heartbeat, "cancelled", lambda: False)():
                    tarea_heartbeat = asyncio.create_task(heartbeat_loop())
                # Feedback
                try:
                    pantalla.fill_rect(0, 54, 128, 10, 0)
                    pantalla.text("PING OK" if ok else "PING FAIL", 0, 54, 1)
                    pantalla.show()
                    motor.value(1); time.sleep(0.1); motor.value(0)
                except:
                    pass
            await asyncio.sleep_ms(300)

        # -------- Botón 15: ENVIAR MÉTRICAS --------
        if not boton_enviar.value():
            ok = enviar_metricas(beats, count)
            try:
                pantalla.fill_rect(64, 54, 64, 10, 0)
                pantalla.text("SEND OK" if ok else "SEND FAIL", 64, 54, 1)
                pantalla.show()
            except:
                pass
            await asyncio.sleep_ms(300)

        await asyncio.sleep_ms(100)

# ---------- Main ----------
async def main():
    await mostrar_logo(2000)
    mostrar_menu()
    await checar_botones()

asyncio.run(main())
