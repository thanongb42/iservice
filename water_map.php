<?php
/**
 * แผนที่จุดติดตั้งระบบตู้น้ำดื่ม
 * Leaflet Map - Water Kiosk Locations
 * เทศบาลนครรังสิต
 */
require_once 'config/database.php';

// ดึงข้อมูลตู้น้ำ
$kiosks = [];
$sql = "SELECT * FROM water_kiosks WHERE status = 'active' ORDER BY kiosk_code ASC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $kiosks[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แผนที่จุดติดตั้งตู้น้ำดื่ม - เทศบาลนครรังสิต</title>

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- Leaflet MarkerCluster CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&family=Prompt:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

    <style>
        :root {
            --primary: #1a5276;
            --primary-light: #2980b9;
            --accent: #00b894;
            --accent-light: #00a880;
            --success: #27ae60;
            --bg-dark: #0d1b2a;
            --bg-panel: rgba(13, 27, 42, 0.92);
            --text-light: #ecf0f1;
            --text-muted: #95a5a6;
            --border: rgba(255,255,255,0.08);
            --shadow: 0 8px 32px rgba(0,0,0,0.3);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Sarabun', sans-serif;
            background: var(--bg-dark);
            color: var(--text-light);
            overflow: hidden;
            height: 100vh;
        }

        /* === HEADER === */
        .header {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1000;
            background: linear-gradient(135deg, var(--bg-dark) 0%, #1b2838 100%);
            border-bottom: 1px solid var(--border);
            padding: 10px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            backdrop-filter: blur(10px);
            box-shadow: var(--shadow);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .header-logo {
            height: 45px;
            width: auto;
            object-fit: contain;
        }

        .header-icon {
            width: 42px; height: 42px;
            background: linear-gradient(135deg, var(--accent), var(--accent-light));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #fff;
            box-shadow: 0 4px 12px rgba(0, 184, 148, 0.4);
        }

        .header-title h1 {
            font-family: 'Prompt', sans-serif;
            font-size: 16px;
            font-weight: 700;
            color: var(--text-light);
        }

        .header-title p {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 1px;
        }

        .header-stats {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .stat-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            background: rgba(255,255,255,0.06);
            border-radius: 20px;
            border: 1px solid var(--border);
            font-size: 13px;
        }

        .stat-badge i { color: var(--accent); }
        .stat-badge .num { color: var(--accent); font-weight: 700; font-size: 15px; }

        .home-btn {
            padding: 6px 12px;
            background: linear-gradient(135deg, #3498db, #2980b9);
            border: none;
            border-radius: 8px;
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
        }

        .home-btn:hover { background: linear-gradient(135deg, #2980b9, #1f618d); box-shadow: 0 6px 16px rgba(52, 152, 219, 0.5); }

        /* === MAP === */
        #map {
            position: fixed;
            top: 62px;
            left: 0; right: 0; bottom: 0;
            z-index: 1;
        }

        /* === SIDE PANEL === */
        .side-panel {
            position: fixed;
            top: 72px;
            left: 10px;
            bottom: 10px;
            width: 320px;
            z-index: 999;
            background: var(--bg-panel);
            border-radius: 14px;
            border: 1px solid var(--border);
            backdrop-filter: blur(16px);
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .side-panel.collapsed { transform: translateX(-330px); }

        .panel-toggle {
            position: fixed;
            top: 80px;
            left: 10px;
            z-index: 1000;
            width: 36px; height: 36px;
            background: var(--bg-panel);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text-light);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .panel-toggle:hover { background: var(--primary-light); }
        .side-panel:not(.collapsed) ~ .panel-toggle { left: 340px; }

        /* === PANEL CONTENT === */
        .panel-header {
            padding: 12px 14px;
            border-bottom: 1px solid var(--border);
            background: rgba(0, 184, 148, 0.08);
        }

        .panel-header h2 {
            font-family: 'Prompt', sans-serif;
            font-size: 14px;
            font-weight: 700;
            color: var(--accent);
            margin: 0;
        }

        .panel-search {
            padding: 10px 12px;
            border-bottom: 1px solid var(--border);
        }

        .search-box {
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 9px 12px 9px 36px;
            background: rgba(255,255,255,0.07);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text-light);
            font-family: 'Sarabun', sans-serif;
            font-size: 14px;
            outline: none;
            transition: border 0.2s;
        }

        .search-box input::placeholder { color: var(--text-muted); }
        .search-box input:focus { border-color: var(--accent); }

        .search-box i {
            position: absolute;
            left: 11px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 13px;
        }

        /* === ITEMS LIST === */
        .items-list {
            flex: 1;
            overflow-y: auto;
            padding: 10px 0;
        }

        .items-list::-webkit-scrollbar {
            width: 6px;
        }

        .items-list::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.02);
        }

        .items-list::-webkit-scrollbar-thumb {
            background: rgba(0, 184, 148, 0.3);
            border-radius: 3px;
        }

        .items-list::-webkit-scrollbar-thumb:hover {
            background: rgba(0, 184, 148, 0.6);
        }

        .item {
            padding: 10px 12px;
            margin: 4px 8px;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }

        .item:hover {
            background: rgba(255,255,255,0.08);
            border-left-color: var(--accent);
            box-shadow: 0 2px 8px rgba(0, 184, 148, 0.15);
        }

        .item-title {
            font-family: 'Prompt', sans-serif;
            font-size: 13px;
            font-weight: 700;
            color: var(--text-light);
            margin: 0 0 3px;
        }

        .item-meta {
            font-size: 11px;
            color: var(--text-muted);
            margin: 0;
        }

        .item.active {
            background: rgba(0, 184, 148, 0.15);
            border-color: var(--accent);
            border-left-color: var(--accent);
        }

        .item.active .item-title { color: var(--accent); }

        /* === RESPONSIVE === */
        @media (max-width: 768px) {
            .header-stats { display: none; }
            .side-panel { width: 280px; }
            .side-panel.collapsed { transform: translateX(-290px); }
            .side-panel:not(.collapsed) ~ .panel-toggle { left: 300px; }
        }

        @media (max-width: 480px) {
            .header { padding: 8px 12px; }
            .header-title h1 { font-size: 14px; }
            .header-title p { display: none; }
            .header-logo { height: 35px !important; }
            .home-btn { padding: 5px 10px; font-size: 11px; }
            .side-panel { width: 100%; border-radius: 0; left: 0; bottom: 0; }
            .side-panel.collapsed { transform: translateX(-100%); }
            .side-panel:not(.collapsed) ~ .panel-toggle { left: 10px; }
        }
    </style>
</head>
<body>
    <!-- HEADER -->
    <div class="header">
        <div class="header-left">
            <img src="public/assets/images/logo/rangsit-small-logo.png" alt="เทศบาลนครรังสิต" class="header-logo">
            <div class="header-icon">
                <i class="fas fa-droplet"></i>
            </div>
            <div class="header-title">
                <h1>ระบบตู้น้ำดื่ม</h1>
                <p>เทศบาลนครรังสิต</p>
            </div>
        </div>
        <div class="header-stats">
            <div class="stat-badge">
                <i class="fas fa-water"></i>
                <span class="num" id="totalCount"><?= count($kiosks) ?></span>
                <span>ตู้</span>
            </div>
        </div>
        <button class="home-btn" onclick="window.location.href='index.php'">
            <i class="fas fa-home"></i> กลับหน้าหลัก
        </button>
    </div>

    <!-- MAP -->
    <div id="map"></div>

    <!-- SIDE PANEL -->
    <div class="side-panel" id="sidePanel">
        <div class="panel-header">
            <h2><i class="fas fa-list"></i> รายการตู้น้ำ</h2>
        </div>
        
        <div class="panel-search">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="ค้นหาตู้น้ำ...">
            </div>
        </div>

        <div class="items-list" id="itemsList"></div>
    </div>

    <!-- PANEL TOGGLE BUTTON -->
    <button class="panel-toggle" id="panelToggle" onclick="togglePanel()">
        <i class="fas fa-chevron-left"></i>
    </button>

    <!-- Leaflet -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <!-- Leaflet MarkerCluster -->
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

    <script>
        // Map Data
        const kiosks = <?= json_encode($kiosks) ?>;
        let map, markers = {}, markerGroup;

        // Initialize Map
        function initMap() {
            map = L.map('map').setView([13.985, 100.620], 14);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19
            }).addTo(map);

            markerGroup = L.markerClusterGroup();
            
            kiosks.forEach((kiosk, idx) => {
                if (kiosk.latitude && kiosk.longitude) {
                    const lat = parseFloat(kiosk.latitude);
                    const lng = parseFloat(kiosk.longitude);
                    
                    const icon = L.icon({
                        iconUrl: 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0MCIgaGVpZ2h0PSI0MCIgdmlld0JveD0iMCAwIDQwIDQwIj48Y2lyY2xlIGN4PSIyMCIgY3k9IjIwIiByPSIyMCIgZmlsbD0iIzAwYjg5NCIvPjxwYXRoIGQ9Ik0yMCAxMGM1LDAgOSw0IDksOXMtNDEwLTktOS05LTQgNDEwLTkgOXM0IDkgOSA5eiIgZmlsbD0iI2ZmZiIvPjwvc3ZnPg==',
                        iconSize: [40, 40],
                        iconAnchor: [20, 40],
                        popupAnchor: [0, -40]
                    });
                    
                    const marker = L.marker([lat, lng], { icon })
                        .bindPopup(`<div style="font-family: Sarabun; text-align: center;">
                            <strong>${kiosk.kiosk_code}</strong><br>
                            ${kiosk.location_name || 'ตู้น้ำ'}<br>
                            <small style="color: #666;">${kiosk.address || 'สถานที่'}</small>
                        </div>`)
                        .bindTooltip(kiosk.kiosk_code, {
                            permanent: false,
                            direction: 'top',
                            offset: [0, -40]
                        });
                    
                    markers[idx] = marker;
                    markerGroup.addLayer(marker);
                }
            });

            map.addLayer(markerGroup);

            // Tooltip/Popup z-index handling
            map.on('popupopen', function(e) {
                const tooltips = document.querySelectorAll('.leaflet-tooltip');
                tooltips.forEach(t => t.style.zIndex = '9998');
                e.popup._container.style.zIndex = '9999';
            });

            map.on('popupclose', function() {
                const tooltips = document.querySelectorAll('.leaflet-tooltip');
                tooltips.forEach(t => t.style.zIndex = '9999');
            });
        }

        // Render Items List
        function renderList(filterKiosks = kiosks) {
            const listEl = document.getElementById('itemsList');
            listEl.innerHTML = '';
            
            filterKiosks.forEach((kiosk, idx) => {
                const div = document.createElement('div');
                div.className = 'item';
                div.innerHTML = `
                    <p class="item-title">${kiosk.kiosk_code}</p>
                    <p class="item-meta"><i class="fas fa-map-marker"></i> ${kiosk.location_name || 'ตู้น้ำ'}</p>
                `;
                div.onclick = () => focusMarker(idx);
                listEl.appendChild(div);
            });
        }

        // Focus Marker
        function focusMarker(idx) {
            if (markers[idx]) {
                const pos = markers[idx].getLatLng();
                map.setView(pos, 17);
                markers[idx].openPopup();
                
                // Highlight in list
                document.querySelectorAll('.item').forEach((el, i) => {
                    el.classList.toggle('active', i === idx);
                });
            }
        }

        // Search
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const query = e.target.value.toLowerCase();
            const filtered = kiosks.filter(k => 
                (k.kiosk_code && k.kiosk_code.toLowerCase().includes(query)) ||
                (k.location_name && k.location_name.toLowerCase().includes(query)) ||
                (k.address && k.address.toLowerCase().includes(query))
            );
            renderList(filtered);
        });

        // Toggle Panel
        function togglePanel() {
            document.getElementById('sidePanel').classList.toggle('collapsed');
            document.getElementById('panelToggle').querySelector('i').classList.toggle('fa-chevron-left');
            document.getElementById('panelToggle').querySelector('i').classList.toggle('fa-chevron-right');
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            initMap();
            renderList();
        });
    </script>
</body>
</html>
