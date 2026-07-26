@extends('layouts.marketplace')

@section('title', 'Registra tu taller - Talleres Automotrices')

@section('content')
    <h1 class="text-2xl font-semibold tracking-tight">Registra tu taller</h1>
    <p class="mt-1 text-sm text-zinc-600">
        No necesitas crear una cuenta. Completa el formulario y un administrador revisará tu solicitud.
    </p>

    @if ($errors->any())
        <div class="mt-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            <p class="font-medium">Revisa los siguientes datos:</p>
            <ul class="mt-1 list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <ol class="mt-6 flex items-center gap-2 text-xs font-medium text-zinc-500" id="stepper-nav">
        <li class="step-label flex-1 rounded-full bg-zinc-900 px-3 py-1.5 text-center text-white" data-step-label="1">1. Tus datos</li>
        <li class="step-label flex-1 rounded-full bg-zinc-200 px-3 py-1.5 text-center" data-step-label="2">2. El taller</li>
        <li class="step-label flex-1 rounded-full bg-zinc-200 px-3 py-1.5 text-center" data-step-label="3">3. Ubicación</li>
        <li class="step-label flex-1 rounded-full bg-zinc-200 px-3 py-1.5 text-center" data-step-label="4">4. Confirmar</li>
    </ol>

    <form method="POST" action="{{ route('solicitudes.store') }}" novalidate id="solicitud-form" class="mt-6 space-y-6">
        @csrf

        {{-- Paso 1: solicitante --}}
        <section data-step="1" class="space-y-4 rounded-xl border border-zinc-200 bg-white p-5">
            <h2 class="font-medium">Tus datos de contacto</h2>

            <div>
                <label for="solicitante_nombre" class="block text-sm font-medium text-zinc-700">Nombre completo</label>
                <input type="text" name="solicitante_nombre" id="solicitante_nombre" required
                    value="{{ old('solicitante_nombre') }}"
                    class="mt-1 block w-full rounded-md border border-zinc-300 px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500">
            </div>

            <div>
                <label for="solicitante_email" class="block text-sm font-medium text-zinc-700">Email</label>
                <input type="email" name="solicitante_email" id="solicitante_email" required
                    value="{{ old('solicitante_email') }}"
                    class="mt-1 block w-full rounded-md border border-zinc-300 px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500">
            </div>

            <div>
                <label for="solicitante_telefono" class="block text-sm font-medium text-zinc-700">Teléfono</label>
                <input type="tel" name="solicitante_telefono" id="solicitante_telefono" required
                    value="{{ old('solicitante_telefono') }}"
                    class="mt-1 block w-full rounded-md border border-zinc-300 px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500">
            </div>
        </section>

        {{-- Paso 2: taller --}}
        <section data-step="2" class="hidden space-y-4 rounded-xl border border-zinc-200 bg-white p-5">
            <h2 class="font-medium">Datos del taller</h2>

            <div>
                <label for="taller_nombre" class="block text-sm font-medium text-zinc-700">Nombre del taller</label>
                <input type="text" name="taller_nombre" id="taller_nombre" required
                    value="{{ old('taller_nombre') }}"
                    class="mt-1 block w-full rounded-md border border-zinc-300 px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500">
            </div>

            <div>
                <label for="taller_direccion" class="block text-sm font-medium text-zinc-700">Dirección (opcional)</label>
                <input type="text" name="taller_direccion" id="taller_direccion"
                    value="{{ old('taller_direccion') }}"
                    class="mt-1 block w-full rounded-md border border-zinc-300 px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500">
            </div>

            <div>
                <label for="referencia" class="block text-sm font-medium text-zinc-700">Referencia (opcional)</label>
                <input type="text" name="referencia" id="referencia"
                    value="{{ old('referencia') }}" placeholder="Ej. frente a la plaza principal"
                    class="mt-1 block w-full rounded-md border border-zinc-300 px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500">
            </div>

            <div>
                <label for="categoria_principal_id" class="block text-sm font-medium text-zinc-700">Categoría principal (opcional)</label>
                <select name="categoria_principal_id" id="categoria_principal_id"
                    class="mt-1 block w-full rounded-md border border-zinc-300 px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500">
                    <option value="">Sin especificar</option>
                    @foreach ($categorias as $categoria)
                        <option value="{{ $categoria->id }}" @selected(old('categoria_principal_id') == $categoria->id)>
                            {{ $categoria->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="comentario" class="block text-sm font-medium text-zinc-700">Comentario (opcional)</label>
                <textarea name="comentario" id="comentario" rows="3"
                    class="mt-1 block w-full rounded-md border border-zinc-300 px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500">{{ old('comentario') }}</textarea>
            </div>
        </section>

        {{-- Paso 3: ubicación --}}
        <section data-step="3" class="hidden space-y-3 rounded-xl border border-zinc-200 bg-white p-5">
            <h2 class="font-medium">Ubicación en el mapa (opcional)</h2>
            <p class="text-sm text-zinc-600">
                Toca el mapa para marcar dónde está el taller, o usa tu ubicación actual. Puedes omitir este paso si no
                estás seguro; el administrador podrá ajustarlo al aprobar la solicitud.
            </p>

            <button type="button" id="btn-mi-ubicacion"
                class="rounded-md border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50">
                Usar mi ubicación actual
            </button>

            <div id="map" class="h-64 w-full rounded-lg border border-zinc-200" data-loading-text="Cargando mapa…"></div>

            <p id="ubicacion-resumen" class="text-sm text-zinc-500">Sin ubicación seleccionada todavía.</p>

            <input type="hidden" name="lat" id="lat" value="{{ old('lat') }}">
            <input type="hidden" name="lon" id="lon" value="{{ old('lon') }}">
        </section>

        {{-- Paso 4: confirmación --}}
        <section data-step="4" class="hidden space-y-4 rounded-xl border border-zinc-200 bg-white p-5">
            <h2 class="font-medium">Confirma tu solicitud</h2>
            <dl class="grid grid-cols-1 gap-x-4 gap-y-2 text-sm sm:grid-cols-2" id="resumen"></dl>
            <p class="text-xs text-zinc-500">
                Al enviar, recibirás un enlace de seguimiento para consultar el estado de tu solicitud en cualquier
                momento (no necesitas crear una cuenta).
            </p>
        </section>

        <div class="flex items-center justify-between">
            <button type="button" id="btn-anterior"
                class="hidden rounded-md border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50">
                Anterior
            </button>
            <div class="flex-1"></div>
            <button type="button" id="btn-siguiente"
                class="rounded-md bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800">
                Siguiente
            </button>
            <button type="submit" id="btn-enviar"
                class="hidden rounded-md bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800 disabled:opacity-60">
                <span id="btn-enviar-texto">Enviar solicitud</span>
            </button>
        </div>
    </form>

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
                        field.classList.add('border-red-400');
                        field.focus();
                        return false;
                    }
                    field.classList.remove('border-red-400');
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
                    `<div><dt class="font-medium text-zinc-500">${label}</dt><dd class="text-zinc-900">${value}</dd></div>`
                ).join('');
            }

            function mostrarPaso(step) {
                sections.forEach((section) => {
                    section.classList.toggle('hidden', Number(section.dataset.step) !== step);
                });
                labels.forEach((label) => {
                    const isActive = Number(label.dataset.stepLabel) <= step;
                    label.classList.toggle('bg-zinc-900', isActive);
                    label.classList.toggle('text-white', isActive);
                    label.classList.toggle('bg-zinc-200', !isActive);
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
                attribution: '&copy; OpenStreetMap contributors',
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
