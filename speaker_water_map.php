<?php
/**
 * แผนที่จุดติดตั้งระบบสาธารณูปโภค - เทศบาลเมืองรังสิต
 * Leaflet Map - Multi Layer: ระบบเสียง + ตู้น้ำดื่ม
 */
require_once 'config/database.php';

// ดึงข้อมูลลำโพง
$speakers = [];
$speaker_zones = [];
$sql1 = "SELECT * FROM speaker_locations WHERE status = 'active' ORDER BY point_number ASC";
$result1 = $conn->query($sql1);
if ($result1 && $result1->num_rows > 0) {
    while ($row = $result1->fetch_assoc()) {
        $speakers[] = $row;
        if (!empty($row['zone_group']) && !in_array($row['zone_group'], $speaker_zones)) {
            $speaker_zones[] = $row['zone_group'];
        }
    }
}
sort($speaker_zones);

// ดึงข้อมูลตู้น้ำ
$kiosks = [];
$sql2 = "SELECT * FROM water_kiosks WHERE status = 'active' ORDER BY kiosk_code ASC";
$result2 = $conn->query($sql2);
if ($result2 && $result2->num_rows > 0) {
    while ($row = $result2->fetch_assoc()) {
        $kiosks[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แผนที่สาธารณูปโภค - เทศบาลเมืองรังสิต</title>

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- MarkerCluster CSS -->
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
            --water: #00b894;
            --water-dark: #00876c;
            --speaker: #6c5ce7;
            --speaker-light: #a29bfe;
            --success: #27ae60;
            --danger: #e74c3c;
            --bg-dark: #0d1b2a;
            --bg-panel: rgba(13, 27, 42, 0.94);
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
            background: linear-gradient(135deg, #2d3436, #636e72);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #fff;
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

        .stat-badge.speaker-badge i { color: var(--speaker-light); }
        .stat-badge.speaker-badge .num { color: var(--speaker-light); font-weight: 700; font-size: 15px; }
        .stat-badge.water-badge i { color: var(--water); }
        .stat-badge.water-badge .num { color: var(--water); font-weight: 700; font-size: 15px; }

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

        /* === TAB SWITCHER === */
        .tab-bar {
            display: flex;
            border-bottom: 1px solid var(--border);
        }

        .tab-btn {
            flex: 1;
            padding: 11px 8px;
            background: none;
            border: none;
            color: var(--text-muted);
            font-family: 'Sarabun', sans-serif;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .tab-btn:hover { color: var(--text-light); background: rgba(255,255,255,0.04); }

        .tab-btn.active-speaker {
            color: var(--speaker-light);
            border-bottom-color: var(--speaker-light);
            background: rgba(108, 92, 231, 0.08);
        }

        .tab-btn.active-water {
            color: var(--water);
            border-bottom-color: var(--water);
            background: rgba(0, 184, 148, 0.08);
        }

        .tab-btn .tab-count {
            font-size: 11px;
            padding: 1px 7px;
            border-radius: 10px;
            background: rgba(255,255,255,0.1);
        }

        /* === PANEL CONTENT === */
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
        .search-box input:focus { border-color: var(--primary-light); }

        .search-box i {
            position: absolute;
            left: 11px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 13px;
        }

        .zone-filter {
            padding: 6px 12px 8px;
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

        .zone-filter select option { background: #1b2838; color: var(--text-light); }

        /* TAB PANELS */
        .tab-panel {
            display: none;
            flex-direction: column;
            flex: 1;
            overflow: hidden;
        }

        .tab-panel.active { display: flex; }

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

        .loc-item.active-highlight {
            background: rgba(41, 128, 185, 0.15);
            border-color: var(--primary-light);
        }

        .loc-pin {
            width: 32px; height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
            flex-shrink: 0;
            color: #fff;
        }

        .loc-pin.speaker { background: linear-gradient(135deg, var(--speaker), var(--speaker-light)); }
        .loc-pin.water { background: linear-gradient(135deg, var(--water-dark), var(--water)); font-size: 14px; }

        .loc-info { flex: 1; min-width: 0; }
        .loc-info .loc-name { font-size: 13px; font-weight: 600; color: var(--text-light); margin-bottom: 2px; }
        .loc-info .loc-desc { font-size: 11.5px; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .loc-info .loc-coords { font-size: 10.5px; color: rgba(255,255,255,0.3); margin-top: 2px; font-family: monospace; }

        .loc-status {
            display: inline-block;
            padding: 1px 7px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 600;
            margin-top: 3px;
        }
        .loc-status.open { background: rgba(39,174,96,0.2); color: var(--success); }
        .loc-status.closed { background: rgba(231,76,60,0.2); color: var(--danger); }

        /* === CUSTOM MARKERS === */
        .marker-speaker {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 28px; height: 28px;
            background: linear-gradient(135deg, var(--speaker), var(--speaker-light));
            border: 2px solid #fff;
            border-radius: 50%;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            font-family: 'Sarabun', sans-serif;
            box-shadow: 0 3px 10px rgba(108, 92, 231, 0.5);
            transition: transform 0.2s;
        }

        .marker-speaker:hover { transform: scale(1.3); }

        .marker-water {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 30px; height: 30px;
            background: linear-gradient(135deg, var(--water-dark), var(--water));
            border: 2.5px solid #fff;
            border-radius: 8px;
            color: #fff;
            font-size: 13px;
            font-family: 'Sarabun', sans-serif;
            box-shadow: 0 3px 12px rgba(0, 184, 148, 0.5);
            transition: transform 0.2s;
        }

        .marker-water:hover { transform: scale(1.3); }

        /* === TOOLTIPS === */
        .tooltip-speaker {
            background: rgba(108, 92, 231, 0.95) !important;
            border: 1px solid var(--speaker-light) !important;
            border-radius: 10px !important;
            color: #fff !important;
            padding: 10px 14px !important;
            font-family: 'Sarabun', sans-serif !important;
            font-size: 13px !important;
            box-shadow: 0 6px 24px rgba(0,0,0,0.4) !important;
            max-width: 260px;
        }

        .tooltip-water {
            background: rgba(0, 135, 108, 0.95) !important;
            border: 1px solid var(--water) !important;
            border-radius: 10px !important;
            color: #fff !important;
            padding: 10px 14px !important;
            font-family: 'Sarabun', sans-serif !important;
            font-size: 13px !important;
            box-shadow: 0 6px 24px rgba(0,0,0,0.4) !important;
            max-width: 280px;
        }

        .tip-title {
            font-family: 'Prompt', sans-serif;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 4px;
        }

        .tooltip-speaker .tip-title { color: #ddd5ff; }
        .tooltip-water .tip-title { color: #a8f0dc; }

        .tip-desc { font-size: 12.5px; color: rgba(255,255,255,0.85); line-height: 1.5; }
        .tip-coords { font-size: 11px; color: rgba(255,255,255,0.5); margin-top: 5px; font-family: monospace; }

        .tip-zone, .tip-badge {
            display: inline-block;
            margin-top: 5px;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
        }

        .tip-zone { background: rgba(255,255,255,0.15); color: rgba(255,255,255,0.8); }
        .tip-badge { background: rgba(255,255,255,0.2); color: #fff; }

        /* === POPUPS === */
        .leaflet-popup-content-wrapper {
            background: var(--bg-panel) !important;
            border-radius: 12px !important;
            color: var(--text-light) !important;
            box-shadow: 0 8px 32px rgba(0,0,0,0.5) !important;
        }

        .leaflet-popup-tip { background: var(--bg-panel) !important; }
        .leaflet-popup-content { font-family: 'Sarabun', sans-serif !important; margin: 12px 16px !important; }

        .popup-speaker .leaflet-popup-content-wrapper { border: 1px solid var(--speaker-light) !important; }
        .popup-water .leaflet-popup-content-wrapper { border: 1px solid var(--water) !important; }

        .popup-content .popup-title {
            font-family: 'Prompt', sans-serif;
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .popup-content.speaker-popup .popup-title { color: var(--speaker-light); }
        .popup-content.water-popup .popup-title { color: var(--water); }

        .popup-content .popup-desc { font-size: 13px; color: #ccc; line-height: 1.6; }

        .popup-content .popup-meta {
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid var(--border);
            font-size: 12px;
            color: var(--text-muted);
        }

        .popup-content .popup-meta span { display: block; margin-bottom: 3px; }

        .popup-content .popup-actions {
            margin-top: 10px;
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .popup-content .popup-actions a {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 12px;
            transition: all 0.2s;
            color: #fff;
        }

        .btn-navigate { background: var(--primary); }
        .btn-navigate:hover { background: var(--primary-light); }
        .btn-qr { background: #636e72; }
        .btn-qr:hover { background: #b2bec3; color: #2d3436; }

        /* === LEGEND === */
        .map-legend {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 999;
            background: var(--bg-panel);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 14px 18px;
            backdrop-filter: blur(10px);
            font-size: 12px;
            min-width: 180px;
        }

        .map-legend h4 {
            font-family: 'Prompt', sans-serif;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--text-light);
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 6px;
            color: var(--text-muted);
            cursor: pointer;
            padding: 3px 4px;
            border-radius: 6px;
            transition: background 0.2s;
        }

        .legend-item:hover { background: rgba(255,255,255,0.06); }

        .legend-icon {
            width: 22px; height: 22px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: #fff;
            border: 2px solid #fff;
            flex-shrink: 0;
        }

        .legend-icon.speaker-icon { background: var(--speaker); }
        .legend-icon.water-icon { background: var(--water); border-radius: 6px; }

        .legend-item.dimmed { opacity: 0.35; }

        /* === RESPONSIVE === */
        @media (max-width: 768px) {
            .side-panel { width: 280px; }
            .side-panel:not(.collapsed) ~ .panel-toggle { left: 298px; }
            .header-stats { display: none; }
            .header-title h1 { font-size: 14px; }
            .map-legend { bottom: 10px; right: 10px; }
        }
    </style>
</head>
<body>

<!-- HEADER -->
<div class="header">
    <div class="header-left">
        <div class="header-icon">
            <i class="fas fa-map-marked-alt"></i>
        </div>
        <div class="header-title">
            <h1>แผนที่สาธารณูปโภค เทศบาลเมืองรังสิต</h1>
            <p>ระบบกระจายเสียง &bull; ตู้น้ำดื่ม &bull; จ.ปทุมธานี</p>
        </div>
    </div>
    <div class="header-stats">
        <div class="stat-badge speaker-badge">
            <i class="fas fa-broadcast-tower"></i>
            <span>ลำโพง</span>
            <span class="num"><?php echo count($speakers); ?></span>
        </div>
        <div class="stat-badge water-badge">
            <i class="fas fa-tint"></i>
            <span>ตู้น้ำ</span>
            <span class="num"><?php echo count($kiosks); ?></span>
        </div>
    </div>
</div>

<!-- SIDE PANEL -->
<div class="side-panel" id="sidePanel">
    <!-- Tab Switcher -->
    <div class="tab-bar">
        <button class="tab-btn active-speaker" id="tabSpeaker" onclick="switchTab('speaker')">
            <i class="fas fa-broadcast-tower"></i> ระบบเสียง
            <span class="tab-count"><?php echo count($speakers); ?></span>
        </button>
        <button class="tab-btn" id="tabWater" onclick="switchTab('water')">
            <i class="fas fa-tint"></i> ตู้น้ำดื่ม
            <span class="tab-count"><?php echo count($kiosks); ?></span>
        </button>
    </div>

    <!-- Speaker Tab -->
    <div class="tab-panel active" id="panelSpeaker">
        <div class="panel-search">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchSpeaker" placeholder="ค้นหาจุดติดตั้งลำโพง...">
            </div>
        </div>
        <div class="zone-filter">
            <select id="zoneFilter">
                <option value="">-- ทุกพื้นที่ --</option>
                <?php foreach ($speaker_zones as $zone): ?>
                    <option value="<?php echo htmlspecialchars($zone); ?>"><?php echo htmlspecialchars($zone); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="location-list" id="listSpeaker">
            <?php foreach ($speakers as $loc): ?>
                <div class="loc-item" data-type="speaker" data-id="s<?php echo $loc['id']; ?>"
                     data-lat="<?php echo $loc['latitude']; ?>" data-lng="<?php echo $loc['longitude']; ?>"
                     data-zone="<?php echo htmlspecialchars($loc['zone_group'] ?? ''); ?>"
                     data-search="<?php echo htmlspecialchars($loc['point_number'] . ' ' . ($loc['description'] ?? '') . ' ' . ($loc['zone_group'] ?? '')); ?>">
                    <div class="loc-pin speaker"><?php echo $loc['point_number']; ?></div>
                    <div class="loc-info">
                        <div class="loc-name">จุดที่ <?php echo $loc['point_number']; ?></div>
                        <div class="loc-desc"><?php echo htmlspecialchars($loc['description'] ?? '-'); ?></div>
                        <div class="loc-coords"><?php echo $loc['latitude']; ?>, <?php echo $loc['longitude']; ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Water Kiosk Tab -->
    <div class="tab-panel" id="panelWater">
        <div class="panel-search">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchWater" placeholder="ค้นหาตู้น้ำดื่ม...">
            </div>
        </div>
        <div class="location-list" id="listWater">
            <?php foreach ($kiosks as $k): ?>
                <div class="loc-item" data-type="water" data-id="w<?php echo $k['id']; ?>"
                     data-lat="<?php echo $k['latitude']; ?>" data-lng="<?php echo $k['longitude']; ?>"
                     data-search="<?php echo htmlspecialchars($k['kiosk_code'] . ' ' . $k['location_name']); ?>">
                    <div class="loc-pin water"><i class="fas fa-tint"></i></div>
                    <div class="loc-info">
                        <div class="loc-name"><?php echo htmlspecialchars($k['kiosk_code']); ?></div>
                        <div class="loc-desc"><?php echo htmlspecialchars($k['location_name']); ?></div>
                        <div class="loc-coords"><?php echo $k['latitude']; ?>, <?php echo $k['longitude']; ?></div>
                        <span class="loc-status <?php echo $k['status'] === 'active' ? 'open' : 'closed'; ?>">
                            <?php echo $k['status'] === 'active' ? 'เปิดใช้งาน' : 'ปิด'; ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Panel Toggle -->
<button class="panel-toggle" id="panelToggle" title="เปิด/ปิด แผงควบคุม">
    <i class="fas fa-chevron-left"></i>
</button>

<!-- LEGEND -->
<div class="map-legend">
    <h4><i class="fas fa-layer-group"></i> เลเยอร์แผนที่</h4>
    <div class="legend-item" id="legendSpeaker" onclick="toggleLayer('speaker')">
        <div class="legend-icon speaker-icon"><i class="fas fa-broadcast-tower" style="font-size:9px"></i></div>
        <span>จุดติดตั้งระบบเสียงตามสาย</span>
    </div>
    <div class="legend-item" id="legendWater" onclick="toggleLayer('water')">
        <div class="legend-icon water-icon"><i class="fas fa-tint" style="font-size:9px"></i></div>
        <span>จุดติดตั้งตู้น้ำดื่ม</span>
    </div>
</div>

<!-- MAP -->
<div id="map"></div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

<script>
// === DATA ===
const speakerData = <?php echo json_encode($speakers, JSON_UNESCAPED_UNICODE); ?>;
const kioskData = <?php echo json_encode($kiosks, JSON_UNESCAPED_UNICODE); ?>;

// === MAP INIT ===
const map = L.map('map', { center: [13.985, 100.630], zoom: 13, zoomControl: false });
L.control.zoom({ position: 'topright' }).addTo(map);

// === TILE LAYERS ===
const tiles = {
    'แผนที่ถนน': L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OSM', maxZoom: 19 }),
    'ดาวเทียม': L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { attribution: '&copy; Esri', maxZoom: 19 }),
    'แผนที่มืด': L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', { attribution: '&copy; CartoDB', maxZoom: 19 })
};
tiles['แผนที่ถนน'].addTo(map);

// === OVERLAY LAYER GROUPS ===
const speakerCluster = L.markerClusterGroup({ maxClusterRadius: 40, disableClusteringAtZoom: 16, showCoverageOnHover: false,
    iconCreateFunction: function(cluster) {
        const count = cluster.getChildCount();
        return L.divIcon({ html: `<div style="background:linear-gradient(135deg,#6c5ce7,#a29bfe);width:36px;height:36px;border-radius:50%;border:2px solid #fff;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:12px;font-family:Sarabun;box-shadow:0 3px 12px rgba(108,92,231,0.5);">${count}</div>`, className: '', iconSize: [36, 36] });
    }
});

const waterCluster = L.markerClusterGroup({ maxClusterRadius: 40, disableClusteringAtZoom: 16, showCoverageOnHover: false,
    iconCreateFunction: function(cluster) {
        const count = cluster.getChildCount();
        return L.divIcon({ html: `<div style="background:linear-gradient(135deg,#00876c,#00b894);width:36px;height:36px;border-radius:8px;border:2px solid #fff;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:12px;font-family:Sarabun;box-shadow:0 3px 12px rgba(0,184,148,0.5);">${count}</div>`, className: '', iconSize: [36, 36] });
    }
});

const speakerLayer = L.layerGroup([speakerCluster]).addTo(map);
const waterLayer = L.layerGroup([waterCluster]).addTo(map);

// Layer control
const overlays = {
    '<span style="color:#a29bfe">&#9679;</span> จุดติดตั้งระบบเสียงตามสาย': speakerLayer,
    '<span style="color:#00b894">&#9632;</span> จุดติดตั้งตู้น้ำดื่ม': waterLayer
};
L.control.layers(tiles, overlays, { position: 'topright', collapsed: true }).addTo(map);

// === MARKERS STORAGE ===
const markers = {};
let layerVisible = { speaker: true, water: true };

// === SPEAKER MARKERS ===
speakerData.forEach(loc => {
    const icon = L.divIcon({
        className: '',
        html: `<div class="marker-speaker">${loc.point_number}</div>`,
        iconSize: [28, 28], iconAnchor: [14, 14], popupAnchor: [0, -16]
    });

    const m = L.marker([parseFloat(loc.latitude), parseFloat(loc.longitude)], { icon });

    m.bindTooltip(`
        <div class="tip-title"><i class="fas fa-broadcast-tower"></i> จุดที่ ${loc.point_number}</div>
        <div class="tip-desc">${loc.description || '-'}</div>
        ${loc.zone_group ? `<div class="tip-zone">${loc.zone_group}</div>` : ''}
        <div class="tip-coords">${loc.latitude}, ${loc.longitude}</div>
    `, { className: 'tooltip-speaker', direction: 'top', offset: [0, -10], opacity: 1 });

    m.bindPopup(`
        <div class="popup-content speaker-popup">
            <div class="popup-title"><i class="fas fa-broadcast-tower"></i> จุดติดตั้งที่ ${loc.point_number}</div>
            <div class="popup-desc">${loc.description || '-'}</div>
            <div class="popup-meta">
                ${loc.zone_group ? `<span><i class="fas fa-layer-group"></i> กลุ่ม: ${loc.zone_group}</span>` : ''}
                ${loc.device_count ? `<span><i class="fas fa-volume-up"></i> อุปกรณ์: ${loc.device_count} ตัว</span>` : ''}
                <span><i class="fas fa-map-pin"></i> ${loc.latitude}, ${loc.longitude}</span>
            </div>
            <div class="popup-actions">
                <a href="https://www.google.com/maps?q=${loc.latitude},${loc.longitude}" target="_blank" class="btn-navigate"><i class="fas fa-directions"></i> นำทาง</a>
            </div>
        </div>
    `, { maxWidth: 280, className: 'popup-speaker' });

    speakerCluster.addLayer(m);
    markers['s' + loc.id] = m;
});

// === WATER KIOSK MARKERS ===
kioskData.forEach(k => {
    const icon = L.divIcon({
        className: '',
        html: `<div class="marker-water"><i class="fas fa-tint"></i></div>`,
        iconSize: [30, 30], iconAnchor: [15, 15], popupAnchor: [0, -18]
    });

    const m = L.marker([parseFloat(k.latitude), parseFloat(k.longitude)], { icon });

    m.bindTooltip(`
        <div class="tip-title"><i class="fas fa-tint"></i> ${k.kiosk_code}</div>
        <div class="tip-desc">${k.location_name}</div>
        <div class="tip-badge">${k.status === 'active' ? '&#9679; เปิดใช้งาน' : '&#9679; ปิด'}</div>
        <div class="tip-coords">${k.latitude}, ${k.longitude}</div>
    `, { className: 'tooltip-water', direction: 'top', offset: [0, -12], opacity: 1 });

    m.bindPopup(`
        <div class="popup-content water-popup">
            <div class="popup-title"><i class="fas fa-tint"></i> ${k.kiosk_code}</div>
            <div class="popup-desc">${k.location_name}</div>
            <div class="popup-meta">
                <span><i class="fas fa-box"></i> จำนวนตู้: ${k.kiosk_count}</span>
                <span><i class="fas fa-circle" style="color:${k.status === 'active' ? '#27ae60' : '#e74c3c'}; font-size:8px"></i> สถานะ: ${k.status === 'active' ? 'เปิดใช้งาน' : 'ปิด'}</span>
                <span><i class="fas fa-map-pin"></i> ${k.latitude}, ${k.longitude}</span>
            </div>
            <div class="popup-actions">
                <a href="https://www.google.com/maps?q=${k.latitude},${k.longitude}" target="_blank" class="btn-navigate"><i class="fas fa-directions"></i> นำทาง</a>
                ${k.qr_code_link ? `<a href="${k.qr_code_link}" target="_blank" class="btn-qr"><i class="fas fa-qrcode"></i> QR Code</a>` : ''}
            </div>
        </div>
    `, { maxWidth: 300, className: 'popup-water' });

    waterCluster.addLayer(m);
    markers['w' + k.id] = m;
});

// Fit all bounds
const allPoints = [
    ...speakerData.map(l => [parseFloat(l.latitude), parseFloat(l.longitude)]),
    ...kioskData.map(k => [parseFloat(k.latitude), parseFloat(k.longitude)])
];
if (allPoints.length) map.fitBounds(L.latLngBounds(allPoints), { padding: [60, 60] });

// === UI INTERACTIONS ===
const sidePanel = document.getElementById('sidePanel');
const panelToggle = document.getElementById('panelToggle');
let activeItem = null;

panelToggle.addEventListener('click', () => {
    sidePanel.classList.toggle('collapsed');
    panelToggle.querySelector('i').className = sidePanel.classList.contains('collapsed') ? 'fas fa-chevron-right' : 'fas fa-chevron-left';
});

// Tab switching
function switchTab(tab) {
    document.getElementById('tabSpeaker').className = tab === 'speaker' ? 'tab-btn active-speaker' : 'tab-btn';
    document.getElementById('tabWater').className = tab === 'water' ? 'tab-btn active-water' : 'tab-btn';
    document.getElementById('panelSpeaker').className = tab === 'speaker' ? 'tab-panel active' : 'tab-panel';
    document.getElementById('panelWater').className = tab === 'water' ? 'tab-panel active' : 'tab-panel';
}

// Click location item
document.addEventListener('click', (e) => {
    const item = e.target.closest('.loc-item');
    if (!item) return;

    const lat = parseFloat(item.dataset.lat);
    const lng = parseFloat(item.dataset.lng);
    const id = item.dataset.id;

    if (activeItem) activeItem.classList.remove('active-highlight');
    item.classList.add('active-highlight');
    activeItem = item;

    map.flyTo([lat, lng], 17, { duration: 0.8 });
    setTimeout(() => { if (markers[id]) markers[id].openPopup(); }, 900);
});

// Search speakers
document.getElementById('searchSpeaker').addEventListener('input', function() {
    filterList('listSpeaker', this.value, document.getElementById('zoneFilter').value);
});
document.getElementById('zoneFilter').addEventListener('change', function() {
    filterList('listSpeaker', document.getElementById('searchSpeaker').value, this.value);
});

// Search water
document.getElementById('searchWater').addEventListener('input', function() {
    filterList('listWater', this.value, '');
});

function filterList(listId, query, zone) {
    const q = query.toLowerCase().trim();
    document.querySelectorAll(`#${listId} .loc-item`).forEach(item => {
        const text = item.dataset.search.toLowerCase();
        const itemZone = item.dataset.zone || '';
        const matchQ = !q || text.includes(q);
        const matchZ = !zone || itemZone === zone;
        item.style.display = (matchQ && matchZ) ? '' : 'none';
    });
}

// Legend toggle layer
function toggleLayer(type) {
    layerVisible[type] = !layerVisible[type];
    const layer = type === 'speaker' ? speakerLayer : waterLayer;
    const legendItem = document.getElementById(type === 'speaker' ? 'legendSpeaker' : 'legendWater');

    if (layerVisible[type]) {
        map.addLayer(layer);
        legendItem.classList.remove('dimmed');
    } else {
        map.removeLayer(layer);
        legendItem.classList.add('dimmed');
    }
}
</script>

</body>
</html>
