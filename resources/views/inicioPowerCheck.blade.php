<!DOCTYPE html>
<html lang="es" class="scroll-smooth">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>PowerCheck</title>

    <!-- Tailwind CSS via CDN (sin Vite) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#111827'
                        }
                    },
                    boxShadow: {
                        soft: '0 10px 30px rgba(2, 6, 23, 0.08)'
                    }
                }
            }
        }
    </script>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] {
            display: none
        }
    </style>
</head>

<body class="antialiased bg-white text-slate-800 dark:bg-gray-900 dark:text-slate-100">

    <!-- NAVBAR -->
    <nav x-data="{ open: false }" class="fixed top-0 z-30 w-full text-white shadow start-0 bg-primary-900">
        <div class="flex flex-wrap items-center justify-between max-w-screen-xl p-4 mx-auto">
            <a href="{{ url('/') }}" class="flex items-center gap-3">
                <img src="{{ asset('image/powercheckLogo2.png') }}" class="h-8" alt="Logo">
                <span class="text-2xl font-semibold">PowerCheck</span>
            </a>
            <div class="flex items-center gap-3 md:order-2">
                <a href="{{ route('filament.powerCheck.auth.login') }}"
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300">Iniciar
                    Sesión</a>
                <button @click="open=!open" type="button"
                    class="inline-flex items-center justify-center w-10 h-10 p-2 text-sm rounded-lg md:hidden hover:bg-primary-800 focus:outline-none focus:ring-2 focus:ring-primary-600">
                    <span class="sr-only">Abrir menú</span>
                    <svg class="w-5 h-5" viewBox="0 0 17 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M1 1h15M1 7h15M1 13h15" />
                    </svg>
                </button>
            </div>
            <div class="items-center hidden w-auto md:flex md:order-1" id="navbar-sticky">
                <ul class="flex items-center gap-8 text-[15px] font-medium">
                    <li><a href="#inicio" class="py-2 hover:text-primary-300">Inicio</a></li>
                    <li><a href="#about" class="py-2 hover:text-primary-300">Powerlifting/ABP</a></li>
                    <li><a href="#gyms" class="py-2 hover:text-primary-300">Gyms</a></li>
                </ul>
            </div>
        </div>
        <div x-show="open" x-transition.opacity.duration.200ms class="border-t md:hidden border-primary-800">
            <ul class="flex flex-col p-4 space-y-3 bg-primary-900">
                <li><a @click="open=false" href="#inicio"
                        class="block px-3 py-2 rounded hover:bg-primary-800">Inicio</a></li>
                <li><a @click="open=false" href="#about"
                        class="block px-3 py-2 rounded hover:bg-primary-800">Powerlifting/ABP</a></li>
                <li><a @click="open=false" href="#gyms" class="block px-3 py-2 rounded hover:bg-primary-800">Gyms</a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- MAIN (sin padding-top para quitar línea blanca bajo el header) -->
    <main id="inicio" class="pt-0">

        <!-- HERO con carrusel (fade automático) -->
        <section x-data="heroCarousel()" x-init="init()"
            class="relative min-h-[100vh] flex items-center isolate overflow-hidden">
            <!-- Slides -->
            <template x-for="(slide, idx) in slides" :key="idx">
                <div class="absolute inset-0 transition-opacity duration-1000"
                    :class="current === idx ? 'opacity-100 z-10' : 'opacity-0 z-0'">
                    <img :src="slide" alt="Slide" class="object-cover w-full h-full" loading="eager">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/30 to-black/10"></div>
                </div>
            </template>

            <!-- Contenido -->
            <div class="relative z-20 max-w-screen-xl px-4 pt-24 pb-16 mx-auto md:pt-28 sm:pb-20">
                <div class="max-w-3xl">
                    <h1 class="text-4xl font-extrabold tracking-tight text-white sm:text-5xl">PowerCheck</h1>
                    <p class="mt-4 text-xl text-white/90">Una herramienta de seguimiento de rutinas para entrenadores y
                        atletas</p>
                    <p class="mt-2 text-lg text-white/80">Crea rutinas personalizadas y realiza seguimiento de la
                        técnica mediante la cámara de tu teléfono celular.</p>
                    <div class="flex gap-3 mt-8">
                        <a href="#about"
                            class="px-5 py-3 font-semibold text-white rounded-xl bg-primary-600 hover:bg-primary-700 shadow-soft">Conocer
                            más</a>
                        <a href="#gyms"
                            class="px-5 py-3 font-semibold text-gray-900 rounded-xl bg-white/90 hover:bg-white shadow-soft">Ver
                            gimnasios</a>
                    </div>
                    <div class="flex items-center gap-2 mt-8">
                        <template x-for="(slide, i) in slides" :key="i">
                            <button @click="go(i)" class="w-6 h-2 rounded-full"
                                :class="current === i ? 'bg-white' : 'bg-white/40 hover:bg-white/60'"></button>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Controles -->
            <button @click="prev()"
                class="absolute z-20 grid w-10 h-10 text-white -translate-y-1/2 rounded-full left-2 top-1/2 place-items-center bg-black/40 hover:bg-black/60"
                aria-label="Anterior">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
            <button @click="next()"
                class="absolute z-20 grid w-10 h-10 text-white -translate-y-1/2 rounded-full right-2 top-1/2 place-items-center bg-black/40 hover:bg-black/60"
                aria-label="Siguiente">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </section>


        <!-- Controles -->
        <button @click="prev()"
            class="absolute grid w-10 h-10 text-white -translate-y-1/2 rounded-full left-2 top-1/2 place-items-center bg-black/40 hover:bg-black/60"
            aria-label="Anterior">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </button>
        <button @click="next()"
            class="absolute grid w-10 h-10 text-white -translate-y-1/2 rounded-full right-2 top-1/2 place-items-center bg-black/40 hover:bg-black/60"
            aria-label="Siguiente">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </button>
        </section>


        <!-- Filosofía & Qué es (con imágenes) -->
        <section id="about" class="py-20 bg-white dark:bg-gray-900 scroll-mt-[88px] md:scroll-mt-[96px]">
            <div class="grid max-w-screen-xl grid-cols-1 gap-10 px-4 mx-auto md:grid-cols-2">
                <article
                    class="p-6 border border-gray-100 sm:p-8 rounded-2xl dark:border-gray-800 bg-gray-50 dark:bg-gray-800/40 shadow-soft">
                    <img src="{{ asset('image/filosofia.jpg') }}" alt="Filosofía de la Asociación"
                        class="object-cover w-full mb-4 h-44 rounded-xl">
                    <h2 class="mb-3 text-3xl font-semibold">Filosofía de la Asociación</h2>
                    <p class="text-lg leading-relaxed">La Asociación de Powerlifting Bolivia está comprometida con el
                        crecimiento y desarrollo del powerlifting a nivel nacional, promoviendo la integración y el
                        fortalecimiento físico de todos sus miembros.</p>
                </article>
                <article
                    class="p-6 border border-gray-100 sm:p-8 rounded-2xl dark:border-gray-800 bg-gray-50 dark:bg-gray-800/40 shadow-soft">
                    <img src="{{ asset('image/powerlifting.jpg') }}" alt="¿Qué es Powerlifting?"
                        class="object-cover w-full mb-4 h-44 rounded-xl">
                    <h2 class="mb-3 text-3xl font-semibold">¿Qué es Powerlifting?</h2>
                    <p class="text-lg leading-relaxed">El powerlifting es un deporte de fuerza en el que los atletas
                        levantan el máximo peso posible en tres movimientos: sentadilla, press de banca y deadlift.
                        Requiere técnica, constancia y progresión inteligente de cargas.</p>
                </article>
            </div>
        </section>

        <!-- Cómo funciona (3 pasos) -->
        <section id="como-funciona" class="py-16 bg-gray-50 dark:bg-gray-800/30 scroll-mt-[88px] md:scroll-mt-[96px]">
            <div class="max-w-screen-xl px-4 mx-auto">
                <h2 class="text-3xl font-semibold text-center">¿Cómo funciona?</h2>
                <p class="mt-2 text-center text-slate-600 dark:text-slate-300">De la planificación al análisis técnico
                    en minutos.</p>
                <div class="grid grid-cols-1 gap-6 mt-10 md:grid-cols-3">
                    <div
                        class="p-6 bg-white border border-gray-100 rounded-2xl dark:bg-gray-900 dark:border-gray-800 shadow-soft">
                        <div class="text-4xl font-extrabold">01</div>
                        <h3 class="mt-2 text-xl font-semibold">Crea la rutina</h3>
                        <p class="mt-1 text-slate-600 dark:text-slate-300">Define ejercicios, series y progresiones
                            personalizadas por atleta.</p>
                    </div>
                    <div
                        class="p-6 bg-white border border-gray-100 rounded-2xl dark:bg-gray-900 dark:border-gray-800 shadow-soft">
                        <div class="text-4xl font-extrabold">02</div>
                        <h3 class="mt-2 text-xl font-semibold">Registra la sesión</h3>
                        <p class="mt-1 text-slate-600 dark:text-slate-300">Carga pesos y RPE y graba clips desde el
                            móvil.</p>
                    </div>
                    <div
                        class="p-6 bg-white border border-gray-100 rounded-2xl dark:bg-gray-900 dark:border-gray-800 shadow-soft">
                        <div class="text-4xl font-extrabold">03</div>
                        <h3 class="mt-2 text-xl font-semibold">Analiza la técnica</h3>
                        <p class="mt-1 text-slate-600 dark:text-slate-300">Revisa ángulos y consistencia para ajustar
                            la programación.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Métricas / KPIs (3 tarjetas) -->
        <section class="py-10 bg-white dark:bg-gray-900 scroll-mt-[88px] md:scroll-mt-[96px]">
            <div class="grid max-w-screen-xl grid-cols-2 gap-6 px-4 mx-auto text-center sm:grid-cols-3">
                <div>
                    <div class="text-3xl font-bold">+120</div>
                    <div class="text-sm text-slate-600 dark:text-slate-300">Atletas</div>
                </div>
                <div>
                    <div class="text-3xl font-bold">+15</div>
                    <div class="text-sm text-slate-600 dark:text-slate-300">Gyms</div>
                </div>
                <div>
                    <div class="text-3xl font-bold">+12</div>
                    <div class="text-sm text-slate-600 dark:text-slate-300">Entrenadores</div>
                </div>
            </div>
        </section>

        <!-- Gyms -->
        <section id="gyms" class="bg-gray-50 dark:bg-gray-800/30 py-20 scroll-mt-[88px] md:scroll-mt-[96px]">
            <div class="max-w-screen-xl px-4 mx-auto">
                <div class="flex items-end justify-between mb-8">
                    <h2 class="text-3xl font-semibold">Gyms Registrados</h2>
                    <a href="#"
                        class="hidden sm:inline-flex text-primary-700 hover:text-primary-800 dark:text-primary-300">Ver
                        todos</a>
                </div>
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <article
                        class="p-6 bg-white border border-gray-100 dark:bg-gray-900 rounded-2xl dark:border-gray-800 shadow-soft">
                        <h3 class="text-xl font-semibold">Gym 1</h3>
                        <p class="text-sm">Ubicación: Santa Cruz, Bolivia</p>
                        <p class="mt-3 text-gray-600 dark:text-gray-300">Equipado con las mejores máquinas para
                            entrenamiento de fuerza.</p>
                    </article>
                    <article
                        class="p-6 bg-white border border-gray-100 dark:bg-gray-900 rounded-2xl dark:border-gray-800 shadow-soft">
                        <h3 class="text-xl font-semibold">Gym 2</h3>
                        <p class="text-sm">Ubicación: La Paz, Bolivia</p>
                        <p class="mt-3 text-gray-600 dark:text-gray-300">Entrenamientos especializados en powerlifting.
                        </p>
                    </article>
                    <!-- Más cards dinámicas aquí -->
                </div>
            </div>
        </section>

        <!-- Testimonios (suave, visible siempre) -->
        <section x-data="testimonialSlider()" x-init="init()"
            class="py-16 bg-white dark:bg-gray-900 scroll-mt-[88px] md:scroll-mt-[96px]">
            <div class="max-w-screen-xl px-4 mx-auto">
                <h2 class="text-3xl font-semibold text-center">Lo que dicen los atletas</h2>
                <div class="mt-10 max-w-3xl mx-auto relative min-h-[220px]">
                    <template x-for="(t, i) in items" :key="i">
                        <blockquote
                            class="absolute inset-0 p-6 transition duration-500 ease-out border border-gray-100 rounded-2xl sm:p-8 bg-gray-50 dark:bg-gray-800/50 dark:border-gray-800 shadow-soft"
                            :class="current === i ? 'opacity-100 scale-100' : 'opacity-0 scale-95 pointer-events-none'">
                            <p class="text-lg">“<span x-text="t.text"></span>”</p>
                            <footer class="mt-4 text-sm text-slate-600 dark:text-slate-300">
                                <span class="font-semibold" x-text="t.name"></span> — <span x-text="t.role"></span>
                            </footer>
                        </blockquote>
                    </template>
                    <div class="absolute left-0 right-0 flex justify-center gap-2 mt-6 -bottom-10">
                        <template x-for="(t, i) in items" :key="i">
                            <button @click="go(i)" class="w-6 h-2 rounded-full"
                                :class="current === i ? 'bg-primary-600' : 'bg-gray-300 dark:bg-gray-700'"></button>
                        </template>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ -->
        <section class="py-16 bg-gray-50 dark:bg-gray-800/30 scroll-mt-[88px] md:scroll-mt-[96px]">
            <div class="max-w-screen-xl px-4 mx-auto">
                <h2 class="text-3xl font-semibold text-center">Preguntas frecuentes</h2>
                <div class="max-w-3xl mx-auto mt-8 space-y-3">
                    <details
                        class="p-4 bg-white border border-gray-200 group rounded-xl dark:border-gray-700 dark:bg-gray-900 open:shadow-soft">
                        <summary class="font-medium cursor-pointer">¿Necesito un teléfono específico para grabar la
                            técnica?</summary>
                        <p class="mt-2 text-slate-600 dark:text-slate-300">No, cualquier smartphone moderno funciona.
                            Recomendamos buena iluminación y trípode si es posible.</p>
                    </details>
                    <details
                        class="p-4 bg-white border border-gray-200 group rounded-xl dark:border-gray-700 dark:bg-gray-900 open:shadow-soft">
                        <summary class="font-medium cursor-pointer">¿Se guardan mis videos?</summary>
                        <p class="mt-2 text-slate-600 dark:text-slate-300">Puedes decidir si mantenerlos en tu
                            historial o solo subirlos para análisis puntual.</p>
                    </details>
                    <details
                        class="p-4 bg-white border border-gray-200 group rounded-xl dark:border-gray-700 dark:bg-gray-900 open:shadow-soft">
                        <summary class="font-medium cursor-pointer">¿Puedo compartir rutinas con mi equipo?</summary>
                        <p class="mt-2 text-slate-600 dark:text-slate-300">Sí, los entrenadores pueden clonar y asignar
                            rutinas a múltiples atletas.</p>
                    </details>
                </div>
            </div>
        </section>

    </main>

    <!-- FOOTER compacto -->
    <footer class="pt-8 pb-4 text-white bg-primary-900">
        <div class="max-w-screen-xl px-4 mx-auto">
            <div class="grid grid-cols-1 gap-8 pb-6 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <div class="flex items-center gap-3">
                        <img src="{{ asset('image/powercheckLogo.jpg') }}" class="h-8" alt="Logo">
                        <span class="text-xl font-semibold">PowerCheck</span>
                    </div>
                    <p class="mt-3 text-sm text-gray-300">Seguimiento de rutinas y técnica para entrenadores y atletas
                        de powerlifting en Bolivia.</p>
                </div>
                <div>
                    <h3 class="text-sm font-semibold tracking-wider uppercase">Enlaces</h3>
                    <ul class="mt-3 space-y-1 text-sm text-gray-300">
                        <li><a href="#inicio" class="hover:text-white">Inicio</a></li>
                        <li><a href="#about" class="hover:text-white">Powerlifting/ABP</a></li>
                        <li><a href="#gyms" class="hover:text-white">Gyms</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-sm font-semibold tracking-wider uppercase">Recursos</h3>
                    <ul class="mt-3 space-y-1 text-sm text-gray-300">
                        <li><a href="#" class="hover:text-white">Guías</a></li>
                        <li><a href="#" class="hover:text-white">Reglamento ABP</a></li>
                        <li><a href="#" class="hover:text-white">Soporte</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-sm font-semibold tracking-wider uppercase">Contacto</h3>
                    <ul class="mt-3 space-y-1 text-sm text-gray-300">
                        <li>Email: <a href="mailto:info@powercheck.bo" class="hover:text-white">info@powercheck.bo</a>
                        </li>
                        <li>Tel: <a href="tel:+59165371924" class="hover:text-white">+591 70000000</a></li>
                    </ul>
                </div>
            </div>
            <div class="pt-3 border-t border-white/10">
                <p class="text-xs text-center text-gray-300">© 2025 PowerCheck. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <!-- Alpine helpers -->
    <script>
        function heroCarousel() {
            return {
                current: 0,
                slides: [
                    "{{ asset('image/filosofia.jpg') }}",
                    "{{ asset('image/powerlifting.jpg') }}",
                    "{{ asset('image/grupoABP.png') }}"
                ],
                intervalId: null,
                init() {
                    this.intervalId = setInterval(() => {
                        this.next()
                    }, 4000); // cada 4s
                    window.addEventListener('beforeunload', () => clearInterval(this.intervalId));
                },
                next() {
                    this.current = (this.current + 1) % this.slides.length
                },
                prev() {
                    this.current = (this.current - 1 + this.slides.length) % this.slides.length
                },
                go(i) {
                    this.current = i
                }
            }
        }


        function testimonialSlider() {
            return {
                current: 0,
                items: [{
                        text: 'Me ayudó a organizar mis progresiones y evitar estancarme.',
                        name: 'Carla P.',
                        role: 'Atleta ABP'
                    },
                    {
                        text: 'Como coach, me ahorra horas a la semana en seguimiento.',
                        name: 'Luis R.',
                        role: 'Entrenador'
                    },
                    {
                        text: 'La revisión técnica con video marca la diferencia.',
                        name: 'Marcos G.',
                        role: 'Atleta'
                    }
                ],
                timer: null,
                init() {
                    this.timer = setInterval(() => this.next(), 5000);
                    window.addEventListener('beforeunload', () => clearInterval(this.timer));
                },
                next() {
                    this.current = (this.current + 1) % this.items.length;
                },
                go(i) {
                    this.current = i;
                }
            }
        }
    </script>
</body>

</html>
