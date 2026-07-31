@extends('marketplace.layouts.app')

@section('title', 'Registra tu taller')

@section('content')
    <div class="mx-auto max-w-[800px] px-16 py-48 sm:px-24 md:py-64 lg:px-16">
        <h1 class="text-heading-sm font-semibold text-graphite">Registra tu taller</h1>
        <p class="mt-8 text-body-lg text-fog">
            No necesitas crear una cuenta. Completa el formulario y un administrador revisará tu solicitud.
        </p>

        @if ($errors->any())
            <x-alert type="error" class="mt-24">
                <p class="font-medium">Revisa los siguientes datos:</p>
                <ul class="mt-4 list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alert>
        @endif

        <ol class="mt-32 flex items-center gap-8 text-caption font-medium text-fog" id="stepper-nav">
            <li class="step-label flex-1 rounded-pills bg-obsidian px-12 py-8 text-center text-white" data-step-label="1">1. Tus datos</li>
            <li class="step-label flex-1 rounded-pills bg-cloud px-12 py-8 text-center text-iron" data-step-label="2">2. El taller</li>
            <li class="step-label flex-1 rounded-pills bg-cloud px-12 py-8 text-center text-iron" data-step-label="3">3. Ubicación</li>
            <li class="step-label flex-1 rounded-pills bg-cloud px-12 py-8 text-center text-iron" data-step-label="4">4. Confirmar</li>
        </ol>

        <form method="POST" action="{{ route('solicitudes.store') }}" novalidate id="solicitud-form" class="mt-24 space-y-24">
            @csrf

            {{-- Paso 1: solicitante --}}
            <x-card data-step="1" class="space-y-20">
                <h2 class="text-subheading font-semibold text-graphite">Tus datos de contacto</h2>

                <x-input name="solicitante_nombre" label="Nombre completo" required />
                <x-input name="solicitante_email" type="email" label="Email" required />
                <x-input name="solicitante_telefono" type="tel" label="Teléfono" required />
            </x-card>

            {{-- Paso 2: taller --}}
            <x-card data-step="2" class="hidden space-y-20">
                <h2 class="text-subheading font-semibold text-graphite">Datos del taller</h2>

                <x-input name="taller_nombre" label="Nombre del taller" required />
                <x-input name="taller_direccion" label="Dirección (opcional)" />
                <x-input name="referencia" label="Referencia (opcional)" placeholder="Ej. frente a la plaza principal" />

                <x-select
                    name="categoria_principal_id"
                    label="Categoría principal (opcional)"
                    placeholder="Sin especificar"
                    :options="$categorias->pluck('nombre', 'id')"
                />

                <div>
                    <label for="comentario" class="mb-8 block text-body font-medium text-graphite">Comentario (opcional)</label>
                    <textarea name="comentario" id="comentario" rows="3"
                        class="w-full rounded-inputs border border-cloud px-16 py-12 text-body text-graphite placeholder:text-ash focus:outline-none focus:ring-2 focus:ring-obsidian/20">{{ old('comentario') }}</textarea>
                </div>
            </x-card>

            {{-- Paso 3: ubicación --}}
            <x-card data-step="3" class="hidden space-y-16">
                <h2 class="text-subheading font-semibold text-graphite">Ubicación en el mapa (opcional)</h2>
                <p class="text-body text-fog">
                    Toca el mapa para marcar dónde está el taller, o usa tu ubicación actual. Puedes omitir este paso si no
                    estás seguro; el administrador podrá ajustarlo al aprobar la solicitud.
                </p>

                <x-button type="button" id="btn-mi-ubicacion" variant="ghost">
                    Usar mi ubicación actual
                </x-button>

                <div id="map" class="h-[260px] w-full rounded-inputs border border-cloud" data-loading-text="Cargando mapa…"></div>

                <p id="ubicacion-resumen" class="text-body text-fog">Sin ubicación seleccionada todavía.</p>

                <input type="hidden" name="lat" id="lat" value="{{ old('lat') }}">
                <input type="hidden" name="lon" id="lon" value="{{ old('lon') }}">
            </x-card>

            {{-- Paso 4: confirmación --}}
            <x-card data-step="4" class="hidden space-y-20">
                <h2 class="text-subheading font-semibold text-graphite">Confirma tu solicitud</h2>
                <dl class="grid grid-cols-1 gap-x-16 gap-y-8 text-body sm:grid-cols-2" id="resumen"></dl>
                <p class="text-caption text-ash">
                    Al enviar, recibirás un enlace de seguimiento para consultar el estado de tu solicitud en cualquier
                    momento (no necesitas crear una cuenta).
                </p>
            </x-card>

            <div class="flex items-center justify-between">
                <x-button type="button" id="btn-anterior" variant="ghost" class="hidden">
                    Anterior
                </x-button>
                <div class="flex-1"></div>
                <x-button type="button" id="btn-siguiente" variant="primary">
                    Siguiente
                </x-button>
                <x-button type="submit" id="btn-enviar" variant="primary" class="hidden">
                    <span id="btn-enviar-texto">Enviar solicitud</span>
                </x-button>
            </div>
        </form>
    </div>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        (function () {
            const totalSteps = 4;
            let current = 1;
            const sections = document.querySelectorAll('[data-step]');
            const labels = document.querySelectorAll('[data-step-label]');
            const btnAnterior = document.getElementById('btn-anterior');
            const btnSiguiente = document.getElementById('btn-siguiente');
            const btnEnviar = document.getElementById('btn-enviar');

            function requiredFieldsOf(step) {
                return Array.from(sections[step - 1].querySelectorAll('[required]'));
            }

            function validarPaso(step) {
                for (const field of requiredFieldsOf(step)) {
                    if (!field.value.trim()) {
                        field.classList.add('border-ember');
                        field.focus();
                        return false;
                    }
                    field.classList.remove('border-ember');
                }
                return true;
            }

            function renderResumen() {
                const resumen = document.getElementById('resumen');
                const campos = [
                    ['Nombre', document.getElementById('solicitante_nombre').value],
                    ['Email', document.getElementById('solicitante_email').value],
                    ['Teléfono', document.getElementById('solicitante_telefono').value],
                    ['Taller', document.getElementById('taller_nombre').value],
                    ['Dirección', document.getElementById('taller_direccion').value || '—'],
                    ['Ubicación', document.getElementById('lat').value ? 'Marcada en el mapa' : 'Sin marcar'],
                ];
                resumen.innerHTML = campos.map(([label, value]) =>
                    `<div><dt class="font-medium text-ash">${label}</dt><dd class="text-graphite">${value}</dd></div>`
                ).join('');
            }

            function mostrarPaso(step) {
                sections.forEach((section) => {
                    section.classList.toggle('hidden', Number(section.dataset.step) !== step);
                });
                labels.forEach((label) => {
                    const isActive = Number(label.dataset.stepLabel) <= step;
                    label.classList.toggle('bg-obsidian', isActive);
                    label.classList.toggle('text-white', isActive);
                    label.classList.toggle('bg-cloud', !isActive);
                    label.classList.toggle('text-iron', !isActive);
                });
                btnAnterior.classList.toggle('hidden', step === 1);
                btnSiguiente.classList.toggle('hidden', step === totalSteps);
                btnEnviar.classList.toggle('hidden', step !== totalSteps);
                if (step === totalSteps) renderResumen();
                current = step;
            }

            btnSiguiente.addEventListener('click', () => {
                if (!validarPaso(current)) return;
                mostrarPaso(Math.min(current + 1, totalSteps));
            });

            btnAnterior.addEventListener('click', () => {
                mostrarPaso(Math.max(current - 1, 1));
            });

            document.getElementById('solicitud-form').addEventListener('submit', () => {
                btnEnviar.disabled = true;
                document.getElementById('btn-enviar-texto').textContent = 'Enviando…';
            });

            mostrarPaso(1);

            // Mapa (Leaflet, opcional): clic para marcar, o geolocalización del navegador.
            const map = L.map('map').setView([-17.7833, -63.1821], 12);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; colaboradores de OpenStreetMap',
                maxZoom: 19,
            }).addTo(map);

            let marker = null;
            const latInput = document.getElementById('lat');
            const lonInput = document.getElementById('lon');
            const resumenUbicacion = document.getElementById('ubicacion-resumen');

            function marcar(lat, lon) {
                latInput.value = lat.toFixed(7);
                lonInput.value = lon.toFixed(7);
                resumenUbicacion.textContent = `Ubicación marcada: ${lat.toFixed(5)}, ${lon.toFixed(5)}`;
                if (marker) marker.setLatLng([lat, lon]);
                else marker = L.marker([lat, lon]).addTo(map);
            }

            map.on('click', (e) => marcar(e.latlng.lat, e.latlng.lng));

            document.getElementById('btn-mi-ubicacion').addEventListener('click', () => {
                if (!navigator.geolocation) return;
                navigator.geolocation.getCurrentPosition((pos) => {
                    map.setView([pos.coords.latitude, pos.coords.longitude], 16);
                    marcar(pos.coords.latitude, pos.coords.longitude);
                });
            });

            if (latInput.value && lonInput.value) {
                marcar(parseFloat(latInput.value), parseFloat(lonInput.value));
                map.setView([parseFloat(latInput.value), parseFloat(lonInput.value)], 16);
            }
        })();
    </script>
@endsection
