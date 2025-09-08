import network
import uasyncio as asyncio
import framebuf
import time
import urequests
import json
from collections import deque
from math import atan2, sqrt, degrees
from machine import Pin, I2C
from lib.logos import corazon, angulo, dibujar_icono, img_data
from time import ticks_us, ticks_diff
from sh1106 import sh1106
from lib.bmp280.bmp280 import BMP280
from max30102 import MAX30102
try:
    from lib.lsm6ds3 import LSM6DS3
    IMU_AVAILABLE = True
except Exception as _e:
    IMU_AVAILABLE = False

# ===== Variables de sesión que se enviarán =====
session_beats = 0.0
session_reps  = 0

# ---------- CONFIG RED / API ----------
WIFI_SSID = "COMTECO-N4751624"
WIFI_PASS = "MPYER74585"
API_BASE  = "http://192.168.1.84:8000"
ATHLETE_ID = None

# ---------- I2C / Sensores ----------
i2c = I2C(0, scl=Pin(22), sda=Pin(21))  # puedes usar freq=100000 si prefieres
imu = LSM6DS3(i2c) if IMU_AVAILABLE else None
i2c_lock = asyncio.Lock()
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

# ========= Parámetros duros =========
FS_HZ            = 40
DT_MS            = int(1000/FS_HZ)

# Filtrado
ALPHA_FAST       = 0.35
ALPHA_SLOW       = 0.02
MED_WIN          = 5

# Detección
VEL_MIN_DESC     = 0.007
VEL_MIN_ASC      = 0.007
DEPTH_MIN_M      = 0.28
UP_HYST_M        = 0.14

# Movimiento global
GATE_WIN         = 16
VEL_RMS_GATE     = 0.0045

# Tiempos
MIN_REP_MS       = 900
MAX_REP_MS       = 7000
REFRACT_MS       = 700

# Anti-deriva
DRIFT_BETA       = 0.0012
QUIET_V_THR      = 0.0018

# ========= Estado global =========
rep_count = 0

# ---------- Buffer imagen ----------
buffer = bytearray(img_data)
fb = framebuf.FrameBuffer(buffer, 128, 64, framebuf.MONO_VLSB)
titulo = ""

# ---------- Botones ----------
boton_modo = Pin(18, Pin.IN, Pin.PULL_UP)
boton_inicio = Pin(17, Pin.IN, Pin.PULL_UP)
boton_conectar = Pin(16, Pin.IN, Pin.PULL_UP)
boton_enviar = Pin(15, Pin.IN, Pin.PULL_UP)

medicion_activa = False
modo_actual = 0  # 0 = menú principal, 1..3 = ejercicios
ejecucion_activa = False
iniciar = 0
estado_boton_anterior = 1
tarea_sensor = None
tarea_repeticiones = None
tarea_mostrar = None
tarea_heartbeat = None

# ---------- Helpers ----------
async def i2c_retry(coro_fn, retries=3, delay_ms=30):
    for k in range(retries):
        try:
            return await coro_fn()
        except OSError as e:
            if getattr(e, 'errno', None) == 19:  # ENODEV
                await asyncio.sleep_ms(delay_ms * (k+1))
            else:
                raise
    raise OSError(19, "ENODEV tras reintentos")

def snapshot_and_reset():
    """Guarda BPM y reps actuales y resetea contadores."""
    global session_beats, session_reps, beats, rep_count, history, beats_history
    session_beats = beats
    session_reps  = rep_count
    beats = 0.0
    rep_count = 0
    try:
        history.clear()
        beats_history.clear()
    except:
        pass

def get_pitch_deg():
    if not imu:
        return None
    ax, ay, az, gx, gy, gz = imu.get_readings()
    denom = max(1.0, sqrt(ay*ay + az*az))
    return degrees(atan2(ax, denom))

async def _oled_show(p):
    async with i2c_lock:
        p.show()

# MAX30102 shutdown protegido
async def _max_shutdown():
    async with i2c_lock:
        try:
            pulsometro.shutdown()
        except:
            pass

# --- Vibración de inicio: 3 pulsos ascendentes ---
async def vibrar_inicio_asc():
    for dur in (200, 300, 400):   # ms
        motor.value(1)
        await asyncio.sleep_ms(dur)
        motor.value(0)
        await asyncio.sleep_ms(200)

# ---------- RED / API ----------
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
            await asyncio.sleep_ms(250)
    if wlan.isconnected():
        print("WiFi OK:", wlan.ifconfig())
        return True
    print("No se pudo conectar a WiFi")
    return False

async def _http_post_json(url, payload, headers=None, timeout_ms=2000):
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
        req = (
            "POST {path} HTTP/1.1\r\n"
            "Host: {host}\r\n"
            "Content-Type: application/json\r\n"
            "Content-Length: {cl}\r\n"
            "Connection: close\r\n"
        ).format(path=path, host=host, cl=len(body))

        if headers:
            for k, v in headers.items():
                if k.lower() not in ("host", "content-type", "content-length", "connection"):
                    req += "{}: {}\r\n".format(k, v)
        req += "\r\n" + body

        writer.write(req.encode())
        await writer.drain()

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
            writer.close()
            return 0, ""

        headers_raw, rest = buf.split(b"\r\n\r\n", 1)
        try:
            status_line = headers_raw.split(b"\r\n", 1)[0].decode()
            status_code = int(status_line.split(" ")[1])
        except:
            status_code = 0

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
            while len(body_bytes) < cl:
                if time.ticks_diff(time.ticks_ms(), t0) > timeout_ms:
                    break
                chunk = await reader.read(cl - len(body_bytes))
                if not chunk:
                    break
                body_bytes += chunk
        else:
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
        await asyncio.sleep_ms(10000)

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
            threshold_on  = minima + (maxima - minima) * 0.6
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
    global beats
    while True:
        pantalla.fill_rect(17, 16, 30, 8, 0)
        pantalla.text(str(round(beats)), 17, 16, 1)
        pantalla.fill_rect(100, 48, 30, 8, 0)
        pantalla.text(str(rep_count), 100, 48, 1)
        await i2c_retry(lambda: _oled_show(pantalla))
        await asyncio.sleep_ms(300)

# ===== Conteo de repeticiones con BMP =====
FS_HZ            = 30
DT_MS            = int(1000/FS_HZ)
ALPHA_FAST       = 0.35
ALPHA_SLOW       = 0.02
MED_WIN          = 5
VEL_MIN_DESC     = 0.0035
VEL_MIN_ASC      = 0.0035
DEPTH_MIN_M      = 0.12
UP_HYST_M        = 0.06
GATE_WIN         = 16
VEL_RMS_GATE     = 0.0020
MIN_REP_MS       = 700
MAX_REP_MS       = 7000
REFRACT_MS       = 700
DRIFT_BETA       = 0.0010
QUIET_V_THR      = 0.0018

rep_count = 0

async def _bmp_get_rel_alt(sensor):
    async with i2c_lock:
        return sensor.getRelAltitude()

def _median5(buf):
    n = len(buf)
    if n == 0:
        return 0.0
    arr = sorted(buf)
    return arr[n//2]

async def conteoRepeticiones(sensor, pantalla=None):
    global rep_count
    # Calibración
    try:
        base_samples = []
        for _ in range(20):
            try:
                alt = await i2c_retry(lambda: _bmp_get_rel_alt(sensor))
                base_samples.append(alt)
            except:
                pass
            await asyncio.sleep_ms(20)
        baseline = (sum(base_samples)/len(base_samples)) if base_samples else 0.0
    except Exception as e:
        print("[conteo] fallo calibrando:", e)
        baseline = 0.0

    med_buf = deque([], MED_WIN)
    vel_buf = deque([], GATE_WIN)
    ema_fast = 0.0
    ema_slow = 0.0

    state = "TOP"
    armed_descent = False
    max_hp = 0.0
    min_hp = 0.0
    last_hp = 0.0
    last_event_ms = ticks_us() // 1000
    last_count_ms = 0

    while True:
        try:
            alt_raw = await i2c_retry(lambda: _bmp_get_rel_alt(sensor))
            alt_rel = alt_raw - baseline

            med_buf.append(alt_rel)
            alt_med = _median5(med_buf)

            ema_fast = ALPHA_FAST*alt_med + (1.0-ALPHA_FAST)*ema_fast
            ema_slow = ALPHA_SLOW*alt_med + (1.0-ALPHA_SLOW)*ema_slow
            hp = ema_fast - ema_slow

            v = hp - last_hp
            last_hp = hp

            vel_buf.append(v)
            if len(vel_buf) >= 4:
                vrms = (sum(x*x for x in vel_buf)/len(vel_buf)) ** 0.5
            else:
                vrms = 0.0

            now_ms = ticks_us() // 1000

            if state == "TOP" and abs(v) < QUIET_V_THR and abs(hp) < (UP_HYST_M*0.5):
                baseline += DRIFT_BETA * (-(alt_med))

            if state == "TOP":
                max_hp = hp
                min_hp = hp
                armed_descent = False
                if vrms >= VEL_RMS_GATE and v <= -VEL_MIN_DESC:
                    state = "DESC"
                    armed_descent = True

            elif state == "DESC":
                if hp < min_hp:
                    min_hp = hp
                if v >= VEL_MIN_ASC and armed_descent:
                    state = "ASC"
                    last_event_ms = now_ms

            elif state == "ASC":
                dt = now_ms - last_event_ms
                depth = abs(min_hp - max_hp)
                risen = hp - min_hp

                if (vrms >= VEL_RMS_GATE and
                    depth >= DEPTH_MIN_M and
                    risen >= UP_HYST_M and
                    MIN_REP_MS <= dt <= MAX_REP_MS and
                    (now_ms - last_count_ms) >= REFRACT_MS):
                    rep_count += 1
                    last_count_ms = now_ms
                    print("✔ Rep:", rep_count, "depth=%.2f" % depth)

                    if pantalla:
                        try:
                            pantalla.fill_rect(80, 48, 48, 10, 0)
                            pantalla.text(str(rep_count), 80, 48, 1)
                            await i2c_retry(lambda: _oled_show(pantalla))
                        except Exception as e:
                            print("[conteo] oled err:", e)

                    state = "TOP"
                    max_hp = hp
                    min_hp = hp
                    armed_descent = False

                elif dt > MAX_REP_MS*2:
                    state = "TOP"
                    max_hp = hp
                    min_hp = hp
                    armed_descent = False

        except Exception as e:
            print("[conteo] EXCEPTION:", e)
            print(" state=", state,
                  " len(med_buf)=", len(med_buf),
                  " vrms≈", (sum(x*x for x in vel_buf)/len(vel_buf))**0.5 if vel_buf else 0.0)
            await asyncio.sleep_ms(50)

        await asyncio.sleep_ms(DT_MS)

async def mostrar_logo(tiempo_ms=1000):
    global fb
    motor.value(1)
    await asyncio.sleep_ms(200)
    motor.value(0)
    pantalla.blit(fb, 0, 0)
    await i2c_retry(lambda: _oled_show(pantalla))
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
    asyncio.create_task(i2c_retry(lambda: _oled_show(pantalla)))

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
    asyncio.create_task(i2c_retry(lambda: _oled_show(pantalla)))

# ---------- Control de botones ----------
async def checar_botones():
    global modo_actual, ejecucion_activa, iniciar, estado_boton_anterior
    global tarea_sensor, tarea_repeticiones, tarea_mostrar, tarea_heartbeat
    global session_beats, session_reps

    while True:
        # Cambio de modo
        if not boton_modo.value():
            if iniciar == 1:
                # Apaga tareas
                iniciar = 0
                ejecucion_activa = False
                for tarea in [tarea_sensor, tarea_repeticiones, tarea_mostrar]:
                    if tarea:
                        try:
                            tarea.cancel()
                        except:
                            pass
                # Apaga MAX y guarda/reset
                try:
                    await _max_shutdown()
                except:
                    pass
                snapshot_and_reset()
                # Refleja reset
                try:
                    pantalla.fill_rect(17, 16, 30, 8, 0)
                    pantalla.text("0", 17, 16, 1)
                    pantalla.fill_rect(80, 48, 48, 10, 0)
                    pantalla.text("0", 80, 48, 1)
                    await i2c_retry(lambda: _oled_show(pantalla))
                except:
                    pass

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
                # Vibración de cuenta atrás antes de arrancar tareas
                await vibrar_inicio_asc()
                # Arranca tareas
                tarea_sensor = asyncio.create_task(get_max30102_values())
                tarea_repeticiones = asyncio.create_task(conteoRepeticiones(sensor, pantalla))
                tarea_mostrar = asyncio.create_task(mostrarValores())
            else:
                ejecucion_activa = False
                print("Apagando sensores...")
                for tarea in [tarea_sensor, tarea_repeticiones, tarea_mostrar]:
                    if tarea:
                        try:
                            tarea.cancel()
                            print("Tarea cancelada")
                        except:
                            pass
                try:
                    await _max_shutdown()
                except:
                    pass
                # Guarda y resetea
                snapshot_and_reset()
                # Refleja reset
                try:
                    pantalla.fill_rect(17, 16, 30, 8, 0)
                    pantalla.text("0", 17, 16, 1)
                    pantalla.fill_rect(80, 48, 48, 10, 0)
                    pantalla.text("0", 80, 48, 1)
                    await i2c_retry(lambda: _oled_show(pantalla))
                except:
                    pass
            await asyncio.sleep_ms(300)  # anti-rebote

        estado_boton_anterior = estado_actual

        # -------- Botón 16: CONECTAR + PING + HEARTBEAT --------
        if not boton_conectar.value():
            if await wifi_connect_if_needed():
                ok = await ping_api()
                if (tarea_heartbeat is None) or getattr(tarea_heartbeat, "cancelled", lambda: False)():
                    tarea_heartbeat = asyncio.create_task(heartbeat_loop())
                try:
                    pantalla.fill_rect(0, 54, 128, 10, 0)
                    pantalla.text("PING OK" if ok else "PING FAIL", 0, 54, 1)
                    await i2c_retry(lambda: _oled_show(pantalla))
                    motor.value(1); time.sleep(0.1); motor.value(0)
                except:
                    pass
            await asyncio.sleep_ms(300)

        # -------- Botón 15: ENVIAR MÉTRICAS (usa snapshot) --------
        if not boton_enviar.value():
            print("[SEND] BPM=%s reps=%s" % (str(session_beats), str(session_reps)))
            ok = await enviar_metricas(session_beats, session_reps)
            try:
                pantalla.fill_rect(64, 54, 64, 10, 0)
                pantalla.text("SEND OK" if ok else "SEND FAIL", 64, 54, 1)
                await i2c_retry(lambda: _oled_show(pantalla))
            except:
                pass
            # (Opcional) limpiar snapshot si se envió bien:
            # if ok:
            #     session_beats = 0.0
            #     session_reps  = 0
            await asyncio.sleep_ms(300)

        await asyncio.sleep_ms(100)

# ---------- Main ----------
async def main():
    await mostrar_logo(2000)
    mostrar_menu()
    await checar_botones()

asyncio.run(main())
