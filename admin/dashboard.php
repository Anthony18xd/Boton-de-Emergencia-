<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Emergencias Huamancaca Chico</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            height: calc(100vh - 80px);
        }
        #map-dashboard {
            width: 100%;
            height: 100%;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .panel-lateral {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .panel-header {
            padding: 15px 20px;
            background: #e74c3c;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .panel-header h2 {
            margin: 0;
            font-size: 18px;
        }
        .badge {
            background: white;
            color: #e74c3c;
            padding: 3px 12px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
        }
        .lista-emergencias {
            flex: 1;
            overflow-y: auto;
            padding: 10px;
        }
        .emergencia-item {
            padding: 12px;
            margin-bottom: 8px;
            border-radius: 8px;
            border-left: 4px solid #e74c3c;
            background: #fef2f2;
            cursor: pointer;
            transition: all 0.2s;
        }
        .emergencia-item:hover {
            background: #fee2e2;
            transform: translateX(-2px);
        }
        .emergencia-item.leido {
            background: #f9fafb;
            border-left-color: #9ca3af;
        }
        .emergencia-item .tiempo {
            font-size: 12px;
            color: #6b7280;
        }
        .emergencia-item .nombre {
            font-weight: bold;
            font-size: 15px;
        }
        .emergencia-item .tipo {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
            background: #e74c3c;
            color: white;
            margin-top: 4px;
        }
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #9ca3af;
        }
        .empty-state h3 {
            margin: 10px 0 0;
        }
        .auto-refresh-bar {
            padding: 8px 15px;
            background: #f3f4f6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            color: #6b7280;
        }
        .nav-bar {
            background: #1f2937;
            color: white;
            padding: 12px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            border-radius: 12px;
        }
        .nav-bar h1 {
            margin: 0;
            font-size: 20px;
        }
        .nav-bar .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 14px;
        }
        .nav-bar a {
            color: #fbbf24;
            text-decoration: none;
        }
        .nav-bar a:hover {
            text-decoration: underline;
        }
        .info-emergencia {
            padding: 12px;
            background: #f3f4f6;
            border-radius: 8px;
            margin: 8px 0;
            font-size: 14px;
        }
        .info-emergencia strong {
            display: block;
            margin-bottom: 4px;
        }
    </style>
</head>
<body style="background:#f0f2f5;padding:16px;">

    <div class="nav-bar">
        <h1>🚨 Panel de Monitoreo de Emergencias</h1>
        <div class="user-info">
            <span>👤 <?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></span>
            <a href="logout.php">Cerrar sesión</a>
        </div>
    </div>

    <div class="dashboard-grid">
        <div id="map-dashboard"></div>

        <div class="panel-lateral">
            <div class="panel-header">
                <h2>📋 Últimas Emergencias</h2>
                <span class="badge" id="badge-count">0</span>
            </div>
            <div class="auto-refresh-bar">
                <span>🔄 Actualizado: <span id="last-update">-</span></span>
                <label>
                    <input type="checkbox" id="auto-refresh" checked onchange="toggleAutoRefresh()">
                    Auto-actualizar
                </label>
            </div>
            <div class="lista-emergencias" id="lista-emergencias">
                <div class="empty-state">
                    <span style="font-size:48px;">🛡️</span>
                    <h3>No hay emergencias</h3>
                    <p>Esperando alertas...</p>
                </div>
            </div>
        </div>
    </div>

    <audio id="alert-sound" preload="auto">
        <source src="data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACAf39/f4B/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+AgH9/f3+"
        type="audio/wav">
    </audio>

    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyB41DRUbKWJHPxaFjMAwdrzWzbVKartNGg&libraries=places&callback=initDashboardMap" async defer></script>
    <script>
        let map, markers = [], infoWindow;
        let autoRefreshInterval;
        let lastCount = 0;
        let alertSound = document.getElementById('alert-sound');

        function initDashboardMap() {
            const defaultLocation = { lat: -12.062106, lng: -75.235855 };
            map = new google.maps.Map(document.getElementById('map-dashboard'), {
                center: defaultLocation,
                zoom: 14,
                mapTypeId: 'roadmap',
                styles: [
                    { featureType: 'poi', elementType: 'labels', stylers: [{ visibility: 'off' }] }
                ]
            });

            infoWindow = new google.maps.InfoWindow();
            cargarEmergencias();
            autoRefreshInterval = setInterval(cargarEmergencias, 10000);
        }

        function toggleAutoRefresh() {
            if (document.getElementById('auto-refresh').checked) {
                autoRefreshInterval = setInterval(cargarEmergencias, 10000);
            } else {
                clearInterval(autoRefreshInterval);
            }
        }

        function cargarEmergencias() {
            fetch('../api/listar.php')
                .then(res => res.json())
                .then(data => {
                    if (!data.success) return;
                    actualizarMapa(data.data);
                    actualizarLista(data.data);
                    actualizarContador(data.pendientes);

                    if (data.pendientes > lastCount && lastCount > 0) {
                        try { alertSound.play(); } catch(e) {}
                    }
                    lastCount = data.pendientes;

                    document.getElementById('last-update').textContent =
                        new Date().toLocaleTimeString('es-PE');
                })
                .catch(console.error);
        }

        function actualizarMapa(emergencias) {
            markers.forEach(m => m.setMap(null));
            markers = [];

            if (emergencias.length === 0) return;

            const bounds = new google.maps.LatLngBounds();

            emergencias.forEach(emergencia => {
                const pos = {
                    lat: parseFloat(emergencia.latitud),
                    lng: parseFloat(emergencia.longitud)
                };

                const icon = emergencia.leido == 0
                    ? 'http://maps.google.com/mapfiles/ms/icons/red-dot.png'
                    : 'http://maps.google.com/mapfiles/ms/icons/grey-dot.png';

                const marker = new google.maps.Marker({
                    position: pos,
                    map: map,
                    icon: icon,
                    animation: emergencia.leido == 0 ? google.maps.Animation.BOUNCE : null,
                    title: emergencia.nombre,
                });

                const time = new Date(emergencia.created_at).toLocaleString('es-PE');
                const content = `
                    <div style="font-family:sans-serif;max-width:250px;">
                        <strong>${emergencia.nombre}</strong><br>
                        📞 ${emergencia.telefono}<br>
                        ${emergencia.direccion ? '📍 ' + emergencia.direccion + '<br>' : ''}
                        🏷️ ${emergencia.tipo_emergencia}<br>
                        📝 ${emergencia.mensaje || 'Sin mensaje'}<br>
                        <small>🕐 ${time}</small>
                    </div>
                `;

                marker.addListener('click', () => {
                    infoWindow.setContent(content);
                    infoWindow.open(map, marker);
                });

                markers.push(marker);
                bounds.extend(pos);
            });

            if (emergencias.length === 1) {
                map.setCenter(bounds.getCenter());
                map.setZoom(16);
            } else {
                map.fitBounds(bounds);
            }
        }

        function actualizarLista(emergencias) {
            const container = document.getElementById('lista-emergencias');

            if (emergencias.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <span style="font-size:48px;">🛡️</span>
                        <h3>No hay emergencias</h3>
                        <p>Esperando alertas...</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = emergencias.map(e => {
                const time = new Date(e.created_at).toLocaleString('es-PE');
                const isNew = e.leido == 0;
                return `
                    <div class="emergencia-item ${e.leido == 1 ? 'leido' : ''}" onclick="centrarEmergencia(${e.latitud}, ${e.longitud}, ${e.id})">
                        <div class="nombre">${e.nombre}</div>
                        <div class="tiempo">🕐 ${time}</div>
                        <div>
                            <span class="tipo">${e.tipo_emergencia}</span>
                            ${isNew ? '<span class="tipo" style="background:#f59e0b;">🆕 Nuevo</span>' : ''}
                        </div>
                    </div>
                `;
            }).join('');
        }

        function centrarEmergencia(lat, lng, id) {
            map.setCenter({ lat: parseFloat(lat), lng: parseFloat(lng) });
            map.setZoom(18);

            fetch('../api/marcar_leido.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            }).then(() => cargarEmergencias());
        }

        function actualizarContador(pendientes) {
            const badge = document.getElementById('badge-count');
            badge.textContent = pendientes;
            badge.style.background = pendientes > 0 ? '#f59e0b' : 'white';
            badge.style.color = pendientes > 0 ? 'white' : '#e74c3c';

            if (pendientes > 0) {
                document.title = `(${pendientes}) Emergencias - Huamancaca Chico`;
            } else {
                document.title = 'Dashboard - Emergencias Huamancaca Chico';
            }
        }
    </script>
</body>
</html>
