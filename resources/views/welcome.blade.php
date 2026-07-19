<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Talleres Automotrices - Santa Cruz</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        #map { height: 100vh; width: 100%; }
        #counter {
            position: fixed;
            top: 16px;
            right: 16px;
            z-index: 1000;
            background: white;
            padding: 10px 18px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            font-family: system-ui, sans-serif;
            font-size: 14px;
            font-weight: 600;
            color: #333;
        }
        #message {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1000;
            background: white;
            padding: 24px 32px;
            border-radius: 8px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.25);
            font-family: system-ui, sans-serif;
            font-size: 16px;
            color: #555;
            text-align: center;
            display: none;
        }
        .leaflet-popup-content {
            font-family: system-ui, sans-serif;
            font-size: 13px;
            line-height: 1.5;
            min-width: 200px;
        }
        .leaflet-popup-content strong {
            font-size: 15px;
            display: block;
            margin-bottom: 4px;
        }
        .leaflet-popup-content .label {
            font-weight: 600;
            color: #666;
        }
    </style>
</head>
<body>
    <div id="map"></div>
    <div id="counter"></div>
    <div id="message"></div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const map = L.map('map').setView([-17.7833, -63.1821], 12);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19,
        }).addTo(map);

        const counter = document.getElementById('counter');
        const message = document.getElementById('message');

        fetch('/api/talleres')
            .then(res => {
                if (!res.ok) throw new Error('Error al cargar los datos');
                return res.json();
            })
            .then(data => {
                if (!data || data.length === 0) {
                    message.style.display = 'block';
                    message.textContent = 'No se encontraron talleres disponibles.';
                    counter.textContent = '0 talleres encontrados';
                    return;
                }

                counter.textContent = data.length + ' talleres encontrados';

                data.forEach(t => {
                    const lat = parseFloat(t.lat);
                    const lon = parseFloat(t.lon);
                    if (isNaN(lat) || isNaN(lon)) return;

                    const nombre = t.nombre ?? 'No disponible';
                    const direccion = t.direccion ?? 'No disponible';
                    const telefono = t.telefono ?? 'No disponible';
                    const horario = t.horario ?? 'No disponible';

                    const popup = `
                        <strong>${nombre}</strong>
                        <span class="label">Dirección:</span> ${direccion}<br>
                        <span class="label">Teléfono:</span> ${telefono}<br>
                        <span class="label">Horario:</span> ${horario}
                    `;

                    L.marker([lat, lon])
                        .addTo(map)
                        .bindPopup(popup);
                });
            })
            .catch(err => {
                console.error(err);
                message.style.display = 'block';
                message.innerHTML = 'No se pudieron cargar los talleres.<br>Verifica la conexión con el servidor.';
                counter.textContent = 'Error al cargar';
            });
    </script>
</body>
</html>
