@extends('marketplace.layouts.app')

@section('title', 'Inicio')

@section('content')
    {{-- Hero — 018-modernizacion-ui/spec.md: landing page persuasiva, sin mapa ni buscador embebido.
         Esa experiencia sigue completa en /talleres/buscar; acá solo hay CTAs hacia ella. --}}
    <section class="relative overflow-hidden bg-obsidian text-white">
        <div class="pointer-events-none absolute -right-64 -top-64 h-[320px] w-[320px] animate-float-slow rounded-full bg-ember/20 blur-3xl md:h-[480px] md:w-[480px]"></div>

        <div class="relative mx-auto max-w-[1200px] px-16 py-64 sm:px-24 md:py-80 lg:px-16 lg:py-120">
            <span class="inline-flex items-center gap-8 rounded-pills border border-white/15 bg-white/5 px-16 py-8 text-caption font-medium text-mist">
                Marketplace de talleres mecánicos en Santa Cruz
            </span>

            <h1 class="mt-24 max-w-2xl text-heading font-semibold sm:text-heading-lg lg:text-display">
                Encuentra el taller mecánico ideal en Santa Cruz
            </h1>
            <p class="mt-16 max-w-xl text-body-lg text-mist">
                Compara talleres cercanos por categoría, calificación y horario. Sin registrarte.
            </p>

            <div class="mt-32 flex flex-col gap-12 sm:flex-row">
                <x-button :href="route('talleres.buscar')" variant="primary" class="!bg-white !text-obsidian hover:!bg-paper">
                    Buscar talleres
                </x-button>
                <x-button :href="route('solicitudes.create')" variant="ghost" class="!border-white/30 !bg-transparent !text-white hover:!bg-white/10">
                    Registra tu taller
                </x-button>
            </div>

            @if ($categorias->isNotEmpty())
                <div class="mt-32 flex flex-wrap gap-8">
                    @foreach ($categorias->take(6) as $categoria)
                        <a
                            href="{{ route('talleres.buscar', ['categoria' => $categoria->slug]) }}"
                            class="rounded-pills border border-white/15 px-16 py-8 text-caption text-mist hover:border-white/40 hover:text-white"
                        >
                            {{ $categoria->nombre }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- Acceso para personal de talleres ya registrados (staff/dueño con usuario del sistema).
         Sección propia y separada de "Registra tu taller" (esa es para talleres nuevos, sin
         cuenta todavía) — este botón lleva directo al login de /erp. El panel de Super Admin
         (/admin) no tiene enlace público a propósito: solo se accede por URL directa. --}}
    <section class="reveal border-b border-cloud bg-white">
        <div class="mx-auto max-w-[1200px] px-16 py-24 sm:px-24 lg:px-16">
            <div class="flex flex-col items-center justify-between gap-20 rounded-cards border border-cloud bg-paper p-24 sm:flex-row">
                <div class="flex items-center gap-16">
                    <span class="flex size-48 shrink-0 items-center justify-center rounded-cards bg-obsidian text-white">
                        <svg class="size-24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M3 21h18M5 21V7l7-4 7 4v14M9 9h1m-1 4h1m4-4h1m-1 4h1M9 21v-4a3 3 0 013-3 3 3 0 013 3v4" />
                        </svg>
                    </span>
                    <div>
                        <p class="text-subheading font-semibold text-graphite">¿Ya trabajas en un taller registrado?</p>
                        <p class="mt-4 text-body text-fog">Ingresa con tu usuario del sistema para gestionar clientes, vehículos, inventario y órdenes de trabajo.</p>
                    </div>
                </div>
                <x-button :href="route('filament.erp.auth.login')" variant="primary" class="shrink-0">
                    Ingresar al panel de mi taller
                </x-button>
            </div>
        </div>
    </section>

    {{-- Cómo funciona: propuesta de valor para quien busca un taller. --}}
    <section class="reveal mx-auto max-w-[1200px] px-16 py-48 sm:px-24 md:py-64 lg:px-16">
        <h2 class="text-heading-sm font-semibold text-graphite">Cómo funciona</h2>
        <div class="mt-32 grid grid-cols-1 gap-24 md:grid-cols-3">
            <div class="rounded-cards border border-cloud bg-white p-28 transition duration-300 hover:-translate-y-4 hover:shadow-md">
                <span class="text-heading-sm font-semibold text-ash">01</span>
                <p class="mt-12 text-subheading font-semibold text-graphite">Busca</p>
                <p class="mt-8 text-body text-fog">Filtra talleres por categoría, nombre o cercanía sin necesidad de crear una cuenta.</p>
            </div>
            <div class="rounded-cards border border-cloud bg-white p-28 transition duration-300 hover:-translate-y-4 hover:shadow-md">
                <span class="text-heading-sm font-semibold text-ash">02</span>
                <p class="mt-12 text-subheading font-semibold text-graphite">Compara</p>
                <p class="mt-8 text-body text-fog">Revisa calificaciones, reseñas y horarios reales de cada taller antes de decidir.</p>
            </div>
            <div class="rounded-cards border border-cloud bg-white p-28 transition duration-300 hover:-translate-y-4 hover:shadow-md">
                <span class="text-heading-sm font-semibold text-ash">03</span>
                <p class="mt-12 text-subheading font-semibold text-graphite">Contacta</p>
                <p class="mt-8 text-body text-fog">Llega directo al taller que más te convenga, sin intermediarios ni comisiones.</p>
            </div>
        </div>
    </section>

    @if ($categorias->isNotEmpty())
        <section class="reveal border-t border-cloud bg-paper">
            <div class="mx-auto max-w-[1200px] px-16 py-48 sm:px-24 md:py-64 lg:px-16">
                <h2 class="text-heading-sm font-semibold text-graphite">Categorías</h2>
                <div class="mt-24 grid grid-cols-2 gap-16 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($categorias as $categoria)
                        <a
                            href="{{ route('talleres.buscar', ['categoria' => $categoria->slug]) }}"
                            class="rounded-cards border border-cloud bg-white p-20 text-center text-body font-medium text-graphite transition duration-300 hover:-translate-y-2 hover:border-obsidian/40 hover:shadow-md"
                        >
                            {{ $categoria->nombre }}
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Prueba social: estadísticas agregadas ya calculadas por HomeController. --}}
    <section class="reveal border-t border-cloud bg-white">
        <div class="mx-auto max-w-[1200px] px-16 py-48 sm:px-24 md:py-64 lg:px-16">
            <div class="grid grid-cols-1 gap-32 sm:grid-cols-3">
                <x-marketplace.stats-block :number="$stats['talleres']" label="Talleres publicados" />
                <x-marketplace.stats-block :number="$stats['categorias']" label="Categorías" />
                <x-marketplace.stats-block :number="number_format($stats['calificacionPromedio'], 1)" label="Calificación promedio" />
            </div>
        </div>
    </section>

    <section class="reveal bg-graphite py-48 text-center text-white md:py-64">
        <div class="mx-auto max-w-2xl px-16 sm:px-24">
            <p class="text-heading-sm font-semibold">Talleres verificados, cerca de donde estás</p>
            <p class="mt-12 text-body-lg text-mist">
                Cada taller pasa por una revisión antes de aparecer en el marketplace.
            </p>
        </div>
    </section>

    {{-- Para dueños de taller: sección persuasiva hacia la solicitud de alta (004-solicitud-alta-taller). --}}
    <section class="reveal border-t border-cloud bg-paper">
        <div class="mx-auto max-w-[1200px] px-16 py-48 sm:px-24 md:py-80 lg:px-16">
            <div class="grid grid-cols-1 gap-40 lg:grid-cols-2 lg:items-center">
                <div>
                    <span class="inline-flex items-center rounded-pills border border-cloud bg-white px-16 py-8 text-caption font-medium text-iron">
                        ¿Tienes un taller mecánico?
                    </span>
                    <h2 class="mt-24 text-heading-sm font-semibold text-graphite md:text-heading">
                        Digitaliza la gestión de tu taller con TallerPro
                    </h2>
                    <p class="mt-16 max-w-lg text-body-lg text-fog">
                        Además de aparecer en el marketplace frente a nuevos clientes, obtienes un sistema completo para administrar tu operación diaria.
                    </p>

                    <div class="mt-32">
                        <x-button :href="route('solicitudes.create')" variant="primary">
                            Registra tu taller
                        </x-button>
                    </div>
                </div>

                <ul class="grid grid-cols-1 gap-16 sm:grid-cols-2">
                    @foreach ([
                        'Clientes y vehículos organizados en un solo lugar',
                        'Control de inventario y repuestos en tiempo real',
                        'Órdenes de trabajo con seguimiento por estado',
                        'Notificaciones automáticas para tu equipo',
                    ] as $beneficio)
                        <li class="flex items-start gap-12 rounded-cards border border-cloud bg-white p-20 transition duration-300 hover:-translate-y-2 hover:shadow-md">
                            <svg class="mt-2 size-20 shrink-0 text-ember" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            <span class="text-body text-graphite">{{ $beneficio }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>

    {{-- Cotización: cómo contactar a nuestro equipo para llevar el taller al sistema. --}}
    <section class="reveal border-t border-cloud bg-white">
        <div class="mx-auto max-w-[1200px] px-16 py-48 sm:px-24 md:py-80 lg:px-16">
            <div class="mx-auto max-w-2xl text-center">
                <span class="inline-flex items-center rounded-pills border border-cloud bg-paper px-16 py-8 text-caption font-medium text-iron">
                    ¿Quieres una cotización?
                </span>
                <h2 class="mt-24 text-heading-sm font-semibold text-graphite md:text-heading">
                    Habla con nuestro equipo
                </h2>
                <p class="mt-16 text-body-lg text-fog">
                    Cuéntanos sobre tu taller desde el mismo formulario de registro y nuestro equipo te contacta para coordinar la implementación y resolver tus dudas sobre el sistema.
                </p>
            </div>

            <div class="mx-auto mt-40 grid max-w-3xl grid-cols-1 gap-24 sm:grid-cols-3">
                @foreach ([
                    ['n' => '01', 't' => 'Cuéntanos de tu taller', 'd' => 'Completa el formulario de registro con tus datos de contacto y los del taller.'],
                    ['n' => '02', 't' => 'Lo revisamos', 'd' => 'Un administrador valida la información antes de aprobarla.'],
                    ['n' => '03', 't' => 'Te contactamos', 'd' => 'Coordinamos contigo la puesta en marcha del marketplace y el sistema de gestión.'],
                ] as $paso)
                    <div class="rounded-cards border border-cloud bg-paper p-24 text-center transition duration-300 hover:-translate-y-4 hover:shadow-md">
                        <span class="text-heading-sm font-semibold text-ash">{{ $paso['n'] }}</span>
                        <p class="mt-12 text-subheading font-semibold text-graphite">{{ $paso['t'] }}</p>
                        <p class="mt-8 text-body text-fog">{{ $paso['d'] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-40 flex justify-center">
                <x-button :href="route('solicitudes.create')" variant="primary">
                    Solicitar cotización
                </x-button>
            </div>
        </div>
    </section>

    {{-- Preguntas frecuentes. --}}
    <section class="reveal border-t border-cloud bg-paper">
        <div class="mx-auto max-w-[800px] px-16 py-48 sm:px-24 md:py-80 lg:px-16">
            <h2 class="text-heading-sm font-semibold text-graphite">Preguntas frecuentes</h2>

            <div class="mt-32 divide-y divide-cloud rounded-cards border border-cloud bg-white">
                @foreach ([
                    ['q' => '¿Necesito crear una cuenta para buscar un taller?', 'a' => 'No. La búsqueda y comparación de talleres en el marketplace es pública, sin registro.'],
                    ['q' => '¿Cómo registro mi taller en el marketplace?', 'a' => 'Completa el formulario de registro con tus datos y los del taller. Tampoco necesitas crear una cuenta para eso.'],
                    ['q' => '¿Qué pasa después de enviar mi solicitud?', 'a' => 'Un administrador la revisa. Recibes un enlace de seguimiento para consultar el estado en cualquier momento y te contactamos con el resultado.'],
                    ['q' => '¿Qué incluye el sistema de gestión para talleres?', 'a' => 'Clientes y vehículos, control de inventario, órdenes de trabajo con seguimiento por estado y notificaciones para tu equipo.'],
                ] as $faq)
                    <details class="group p-20">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-16 text-body-lg font-medium text-graphite marker:content-none">
                            {{ $faq['q'] }}
                            <svg class="size-20 shrink-0 text-ash transition duration-300 group-open:rotate-45" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                        </summary>
                        <p class="mt-12 text-body text-fog">{{ $faq['a'] }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA final. --}}
    <section class="reveal bg-obsidian py-48 text-center text-white md:py-64">
        <div class="mx-auto max-w-xl px-16 sm:px-24">
            <p class="text-heading-sm font-semibold">¿Listo para encontrar tu taller ideal?</p>
            <p class="mt-12 text-body-lg text-mist">Busca por categoría, calificación o cercanía en segundos.</p>
            <div class="mt-32 flex justify-center">
                <x-button :href="route('talleres.buscar')" variant="primary" class="!bg-white !text-obsidian hover:!bg-paper">
                    Buscar talleres ahora
                </x-button>
            </div>
        </div>
    </section>
@endsection
