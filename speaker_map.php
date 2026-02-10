<?php
/**
 * แผนที่จุดติดตั้งระบบกระจายเสียงชนิดไร้สาย
 * Leaflet Map - Pin Markers with Tooltip
 * เทศบาลนครรังสิต
 */
require_once 'config/database.php';

// ดึงข้อมูลจุดติดตั้งทั้งหมด
$sql = "SELECT * FROM speaker_locations WHERE status = 'active' ORDER BY point_number ASC";
$result = $conn->query($sql);

$locations = [];
$zones = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $locations[] = $row;
        if (!empty($row['zone_group']) && !in_array($row['zone_group'], $zones)) {
            $zones[] = $row['zone_group'];
        }
    }
}
sort($zones);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แผนที่จุดติดตั้งระบบเสียงตามสาย - เทศบาลนครรังสิต</title>

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
            --accent: #e67e22;
            --accent-light: #f39c12;
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

        .header-icon {
            width: 42px; height: 42px;
            background: linear-gradient(135deg, var(--accent), var(--accent-light));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #fff;
            box-shadow: 0 4px 12px rgba(230, 126, 34, 0.4);
        }

        .header-title h1 {
            font-family: 'Prompt', sans-serif;
            font-size: 16px;
            font-weight: 700;
            color: var(--text-light);
            letter-spacing: 0.3px;
        }

        .header-title p {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 1px;
        }

        .header-stats {
            display: flex;
            gap: 20px;
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

        .stat-badge i {
            color: var(--accent-light);
            font-size: 14px;
        }

        .stat-badge .num {
            font-weight: 700;
            color: var(--accent-light);
            font-size: 15px;
        }

        .header-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: linear-gradient(135deg, var(--primary-light), var(--primary));
            color: white;
            border: 1px solid var(--primary-light);
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .header-btn:hover {
            background: linear-gradient(135deg, #3498db, var(--primary-light));
            box-shadow: 0 4px 12px rgba(41, 128, 185, 0.3);
            transform: translateY(-1px);
        }

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
            width: 300px;
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

        .side-panel.collapsed {
            transform: translateX(-310px);
        }

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

        .panel-toggle:hover {
            background: var(--primary-light);
        }

        .side-panel:not(.collapsed) ~ .panel-toggle {
            left: 320px;
        }

        .panel-search {
            padding: 12px;
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
        .search-box input:focus { border-color: var(--primary-light); }

        .search-box i {
            position: absolute;
            left: 11px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 13px;
        }

        /* Zone filter */
        .zone-filter {
            padding: 8px 12px;
            border-bottom: 1px solid var(--border);
        }

        .zone-filter select {
            width: 100%;
            padding: 7px 10px;
            background: rgba(255,255,255,0.07);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text-light);
            font-family: 'Sarabun', sans-serif;
            font-size: 13px;
            outline: none;
            cursor: pointer;
        }

        .zone-filter select option {
            background: #1b2838;
            color: var(--text-light);
        }

        /* Location list */
        .location-list {
            flex: 1;
            overflow-y: auto;
            padding: 6px;
        }

        .location-list::-webkit-scrollbar { width: 4px; }
        .location-list::-webkit-scrollbar-track { background: transparent; }
        .location-list::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 4px; }

        .loc-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 10px;
            border-radius: 10px;
            cursor: pointer;
            transition: background 0.2s;
            border: 1px solid transparent;
        }

        .loc-item:hover {
            background: rgba(255,255,255,0.06);
            border-color: var(--border);
        }

        .loc-item.active {
            background: rgba(41, 128, 185, 0.15);
            border-color: var(--primary-light);
        }

        .loc-pin {
            width: 32px; height: 32px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
            flex-shrink: 0;
            color: #fff;
        }

        .loc-info { flex: 1; min-width: 0; }

        .loc-info .loc-name {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-light);
            margin-bottom: 2px;
        }

        .loc-info .loc-desc {
            font-size: 11.5px;
            color: var(--text-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .loc-info .loc-coords {
            font-size: 10.5px;
            color: rgba(255,255,255,0.3);
            margin-top: 2px;
            font-family: monospace;
        }

        /* === CUSTOM LEAFLET TOOLTIP === */
        .custom-tooltip {
            background: var(--bg-panel) !important;
            border: 1px solid var(--primary-light) !important;
            border-radius: 10px !important;
            color: var(--text-light) !important;
            padding: 10px 14px !important;
            font-family: 'Sarabun', sans-serif !important;
            font-size: 13px !important;
            box-shadow: 0 6px 24px rgba(0,0,0,0.4) !important;
            backdrop-filter: blur(10px);
            max-width: 260px;
            z-index: 9998 !important;
        }

        .custom-tooltip .tip-title {
            font-family: 'Prompt', sans-serif;
            font-weight: 600;
            font-size: 14px;
            color: var(--accent-light);
            margin-bottom: 4px;
        }

        .custom-tooltip .tip-desc {
            font-size: 12.5px;
            color: #ccc;
            line-height: 1.5;
        }

        .custom-tooltip .tip-coords {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 5px;
            font-family: monospace;
        }

        .custom-tooltip .tip-zone {
            display: inline-block;
            margin-top: 5px;
            padding: 2px 8px;
            background: rgba(41,128,185,0.25);
            border-radius: 4px;
            font-size: 11px;
            color: var(--primary-light);
        }

        /* Leaflet popup override */
        .leaflet-popup-content-wrapper {
            background: var(--bg-panel) !important;
            border: 1px solid var(--primary-light) !important;
            border-radius: 12px !important;
            color: var(--text-light) !important;
            box-shadow: 0 8px 32px rgba(0,0,0,0.5) !important;
            z-index: 9999 !important;
        }

        .leaflet-popup-tip {
            background: var(--bg-panel) !important;
            border: 1px solid var(--primary-light) !important;
            z-index: 9999 !important;
        }

        .leaflet-popup {
            z-index: 9999 !important;
        }

        .leaflet-tooltip {
            z-index: 9998 !important;
        }

        .leaflet-popup-content {
            font-family: 'Sarabun', sans-serif !important;
            margin: 12px 16px !important;
        }

        .popup-content .popup-title {
            font-family: 'Prompt', sans-serif;
            font-size: 15px;
            font-weight: 700;
            color: var(--accent-light);
            margin-bottom: 6px;
        }

        .popup-content .popup-desc {
            font-size: 13px;
            color: #ccc;
            line-height: 1.6;
        }

        .popup-content .popup-meta {
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid var(--border);
            font-size: 12px;
            color: var(--text-muted);
        }

        .popup-content .popup-meta span {
            display: block;
            margin-bottom: 3px;
        }

        .popup-content .popup-nav {
            margin-top: 8px;
        }

        .popup-content .popup-nav a {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            background: var(--primary);
            color: #fff;
            border-radius: 6px;
            text-decoration: none;
            font-size: 12px;
            transition: background 0.2s;
        }

        .popup-content .popup-nav a:hover {
            background: var(--primary-light);
        }

        /* Custom marker */
        .custom-marker {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border: 2px solid #fff;
            border-radius: 50%;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            font-family: 'Sarabun', sans-serif;
            box-shadow: 0 3px 10px rgba(0,0,0,0.4);
            transition: transform 0.2s;
        }

        .custom-marker:hover {
            transform: scale(1.3);
        }

        /* === LEGEND === */
        .map-legend {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 999;
            background: var(--bg-panel);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 12px 16px;
            backdrop-filter: blur(10px);
            font-size: 12px;
            max-width: 200px;
        }

        .map-legend h4 {
            font-family: 'Prompt', sans-serif;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--accent-light);
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 4px;
            color: var(--text-muted);
        }

        .legend-dot {
            width: 10px; height: 10px;
            border-radius: 50%;
            border: 2px solid #fff;
            flex-shrink: 0;
        }

        /* === RESPONSIVE === */
        @media (max-width: 768px) {
            .side-panel { width: 260px; }
            .side-panel:not(.collapsed) ~ .panel-toggle { left: 278px; }
            .header-stats { display: flex; }
            .stat-badge { display: none; }
            .header-btn { padding: 6px 12px; font-size: 12px; gap: 4px; }
            .header-btn i { font-size: 14px; }
            .header-title h1 { font-size: 14px; }
            .map-legend { bottom: 10px; right: 10px; }
        }

        @media (max-width: 480px) {
            .header-title h1 { font-size: 12px; }
            .header-title p { font-size: 10px; }
            .header-btn { padding: 5px 10px; font-size: 11px; }
            .header-logo { height: 35px !important; }
        }
    </style>
</head>
<body>

<!-- HEADER -->
<div class="header">
    <div class="header-left">
        <img src="public/assets/images/logo/rangsit-small-logo.png" alt="เทศบาลนครรังสิต" class="header-logo" style="height: 45px; width: auto;">
        <div class="header-title">
            <h1>จุดติดตั้งระบบกระจายเสียงชนิดไร้สาย</h1>
            <p>เทศบาลนครรังสิต จ.ปทุมธานี</p>
        </div>
    </div>
    <div class="header-stats">
        <a href="index.php" class="header-btn">
            <i class="fas fa-home"></i>
            <span>กลับไปหน้าหลัก</span>
        </a>
        <div class="stat-badge">
            <i class="fas fa-map-marker-alt"></i>
            <span>จุดติดตั้งทั้งหมด</span>
            <span class="num"><?php echo count($locations); ?></span>
        </div>
        <div class="stat-badge">
            <i class="fas fa-layer-group"></i>
            <span>กลุ่มพื้นที่</span>
            <span class="num"><?php echo count($zones); ?></span>
        </div>
    </div>
</div>

<!-- SIDE PANEL -->
<div class="side-panel" id="sidePanel">
    <div class="panel-search">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="ค้นหาจุดติดตั้ง...">
        </div>
    </div>
    <div class="zone-filter">
        <select id="zoneFilter">
            <option value="">-- ทุกพื้นที่ --</option>
            <?php foreach ($zones as $zone): ?>
                <option value="<?php echo htmlspecialchars($zone); ?>"><?php echo htmlspecialchars($zone); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="location-list" id="locationList">
        <?php foreach ($locations as $loc): ?>
            <div class="loc-item" data-id="<?php echo $loc['id']; ?>"
                 data-lat="<?php echo $loc['latitude']; ?>"
                 data-lng="<?php echo $loc['longitude']; ?>"
                 data-zone="<?php echo htmlspecialchars($loc['zone_group'] ?? ''); ?>"
                 data-search="<?php echo htmlspecialchars($loc['point_number'] . ' ' . ($loc['description'] ?? '') . ' ' . ($loc['zone_group'] ?? '')); ?>">
                <div class="loc-pin"><?php echo $loc['point_number']; ?></div>
                <div class="loc-info">
                    <div class="loc-name">จุดที่ <?php echo $loc['point_number']; ?></div>
                    <div class="loc-desc"><?php echo htmlspecialchars($loc['description'] ?? '-'); ?></div>
                    <div class="loc-coords"><?php echo $loc['latitude']; ?>, <?php echo $loc['longitude']; ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Panel Toggle -->
<button class="panel-toggle" id="panelToggle" title="เปิด/ปิด แผงควบคุม">
    <i class="fas fa-chevron-left"></i>
</button>

<!-- LEGEND -->
<div class="map-legend">
    <h4><i class="fas fa-info-circle"></i> สัญลักษณ์</h4>
    <div class="legend-item">
        <div class="legend-dot" style="background: var(--primary-light);"></div>
        <span>จุดติดตั้งลำโพง</span>
    </div>
    <div class="legend-item">
        <div class="legend-dot" style="background: var(--accent);"></div>
        <span>จุดที่เลือก</span>
    </div>
</div>

<!-- MAP -->
<div id="map"></div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<!-- MarkerCluster JS -->
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

<script>
    // === DATA ===
    const locations = <?php echo json_encode($locations, JSON_UNESCAPED_UNICODE); ?>;

    // === MAP INIT ===
    const map = L.map('map', {
        center: [13.985, 100.620],
        zoom: 14,
        zoomControl: false
    });

    // Zoom control - top right
    L.control.zoom({ position: 'topright' }).addTo(map);

    // === TILE LAYERS ===
    const tileLayers = {
        'ดาวเทียม': L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: '&copy; Esri',
            maxZoom: 19
        }),
        'แผนที่ถนน': L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap',
            maxZoom: 19
        }),
        'แผนที่มืด': L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; CartoDB',
            maxZoom: 19
        })
    };

    // Default layer
    tileLayers['แผนที่ถนน'].addTo(map);

    // Layer control
    L.control.layers(tileLayers, null, { position: 'topright' }).addTo(map);

    // === OVERLAY LAYER GROUP ===
    const layerGroup = L.layerGroup().addTo(map);
    const overlayLayers = {
        'จุดติดตั้งระบบเสียงตามสาย': layerGroup
    };

    // === MARKERS ===
    const markers = {};
    const markerCluster = L.markerClusterGroup({
        maxClusterRadius: 40,
        spiderfyOnMaxZoom: true,
        showCoverageOnHover: false,
        disableClusteringAtZoom: 16
    });

    locations.forEach(loc => {
        const icon = L.divIcon({
            className: '',
            html: `<div class="custom-marker">${loc.point_number}</div>`,
            iconSize: [28, 28],
            iconAnchor: [14, 14],
            popupAnchor: [0, -16]
        });

        const marker = L.marker([parseFloat(loc.latitude), parseFloat(loc.longitude)], { icon });

        // Tooltip
        const tooltipHtml = `
            <div class="tip-title">จุดติดตั้งที่ ${loc.point_number}</div>
            <div class="tip-desc">${loc.description || '-'}</div>
            ${loc.zone_group ? `<div class="tip-zone"><i class="fas fa-layer-group"></i> ${loc.zone_group}</div>` : ''}
            <div class="tip-coords">${loc.latitude}, ${loc.longitude}</div>
        `;

        marker.bindTooltip(tooltipHtml, {
            className: 'custom-tooltip',
            direction: 'top',
            offset: [0, -10],
            opacity: 1
        });

        // Popup
        const popupHtml = `
            <div class="popup-content">
                <div class="popup-title"><i class="fas fa-broadcast-tower"></i> จุดติดตั้งที่ ${loc.point_number}</div>
                <div class="popup-desc">${loc.description || '-'}</div>
                <div class="popup-meta">
                    ${loc.zone_group ? `<span><i class="fas fa-layer-group"></i> กลุ่ม: ${loc.zone_group}</span>` : ''}
                    ${loc.device_count ? `<span><i class="fas fa-volume-up"></i> จำนวนอุปกรณ์: ${loc.device_count} ตัว</span>` : ''}
                    <span><i class="fas fa-map-pin"></i> พิกัด: ${loc.latitude}, ${loc.longitude}</span>
                    <span><i class="fas fa-circle ${loc.status === 'active' ? 'text-success' : ''}"></i> สถานะ: ${loc.status === 'active' ? 'ใช้งานปกติ' : loc.status}</span>
                </div>
                <div class="popup-nav">
                    <a href="https://www.google.com/maps?q=${loc.latitude},${loc.longitude}" target="_blank">
                        <i class="fas fa-directions"></i> นำทาง Google Maps
                    </a>
                </div>
            </div>
        `;

        marker.bindPopup(popupHtml, { maxWidth: 280 });
        
        // Hide tooltip when popup opens, show it again when popup closes
        marker.on('popupopen', function() {
            this.closeTooltip();
        });
        
        marker.on('popupclose', function() {
            this.openTooltip();
        });
        
        markerCluster.addLayer(marker);
        markers[loc.id] = marker;
    });

    layerGroup.addLayer(markerCluster);

    // Fit bounds
    if (locations.length > 0) {
        const bounds = L.latLngBounds(locations.map(l => [parseFloat(l.latitude), parseFloat(l.longitude)]));
        map.fitBounds(bounds, { padding: [60, 60] });
    }

    // === SIDE PANEL INTERACTIONS ===
    const sidePanel = document.getElementById('sidePanel');
    const panelToggle = document.getElementById('panelToggle');
    const searchInput = document.getElementById('searchInput');
    const zoneFilter = document.getElementById('zoneFilter');
    const locationList = document.getElementById('locationList');
    let activeItem = null;

    // Toggle panel
    panelToggle.addEventListener('click', () => {
        sidePanel.classList.toggle('collapsed');
        const icon = panelToggle.querySelector('i');
        icon.className = sidePanel.classList.contains('collapsed') ? 'fas fa-chevron-right' : 'fas fa-chevron-left';
    });

    // Click location item
    locationList.addEventListener('click', (e) => {
        const item = e.target.closest('.loc-item');
        if (!item) return;

        const lat = parseFloat(item.dataset.lat);
        const lng = parseFloat(item.dataset.lng);
        const id = item.dataset.id;

        // Highlight
        if (activeItem) activeItem.classList.remove('active');
        item.classList.add('active');
        activeItem = item;

        // Fly to and open popup
        map.flyTo([lat, lng], 17, { duration: 0.8 });
        setTimeout(() => {
            if (markers[id]) {
                markers[id].openPopup();
            }
        }, 900);
    });

    // Search
    searchInput.addEventListener('input', filterLocations);
    zoneFilter.addEventListener('change', filterLocations);

    function filterLocations() {
        const query = searchInput.value.toLowerCase().trim();
        const zone = zoneFilter.value;
        const items = locationList.querySelectorAll('.loc-item');

        items.forEach(item => {
            const searchText = item.dataset.search.toLowerCase();
            const itemZone = item.dataset.zone;
            const matchSearch = !query || searchText.includes(query);
            const matchZone = !zone || itemZone === zone;
            item.style.display = (matchSearch && matchZone) ? '' : 'none';
        });
    }
</script>

</body>
</html>
