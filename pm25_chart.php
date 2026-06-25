<?php
date_default_timezone_set('Asia/Bangkok');
require_once __DIR__ . '/config/database.php';
$pdo = getPDO();

// ── Parameters ────────────────────────────────────────────────────────────────
$initCid    = $_GET['cid']    ?? '';
$initMetric = in_array($_GET['metric'] ?? '', ['pm25','pm10','pm1','temp','humi','co2'])
              ? $_GET['metric'] : 'pm25';
$days       = max(1, min(90, (int)($_GET['days'] ?? 30)));

// ── Sensors ───────────────────────────────────────────────────────────────────
$sensors = [];
try {
    $sensors = $pdo->query("SELECT * FROM pm25_sensors WHERE is_active=1 ORDER BY id")
                   ->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
$cids = array_column($sensors, 'cid');

// ── Chart data (hourly avg, last N days) ──────────────────────────────────────
$chartRawByCid = [];
$allTs         = [];
if (!empty($cids)) {
    try {
        $in   = implode(',', array_fill(0, count($cids), '?'));
        $stmt = $pdo->prepare("
            SELECT cid,
                   FLOOR(sensor_timestamp / 3600) * 3600 AS ht,
                   AVG(pm25)        AS pm25,
                   AVG(pm1)         AS pm1,
                   AVG(pm10)        AS pm10,
                   AVG(temperature) AS temp,
                   AVG(humidity)    AS humi,
                   AVG(co2)         AS co2
            FROM pm25_data
            WHERE cid IN ($in)
              AND sensor_timestamp >= UNIX_TIMESTAMP(NOW()) - ? * 86400
              AND pm25 IS NOT NULL
            GROUP BY cid, ht
            ORDER BY ht ASC
        ");
        $stmt->execute(array_merge($cids, [$days]));
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $ts = (int)$r['ht'];
            $allTs[$ts] = date('d/m H:i', $ts);
            $chartRawByCid[$r['cid']][$ts] = [
                'pm25' => $r['pm25'] !== null ? round((float)$r['pm25'], 1) : null,
                'pm1'  => $r['pm1']  !== null ? round((float)$r['pm1'],  1) : null,
                'pm10' => $r['pm10'] !== null ? round((float)$r['pm10'], 1) : null,
                'temp' => $r['temp'] !== null ? round((float)$r['temp'], 1) : null,
                'humi' => $r['humi'] !== null ? round((float)$r['humi'], 1) : null,
                'co2'  => $r['co2']  !== null ? round((float)$r['co2'],  0) : null,
            ];
        }
    } catch (Exception $e) {}
}
ksort($allTs);
$chartLabels = array_values($allTs);
$chartTsKeys = array_keys($allTs);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>กราฟ PM2.5 ย้อนหลัง — เทศบาลนครรังสิต</title>
    <link rel="icon" type="image/png" href="public/assets/images/logo/rangsit-small-logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/hammerjs@2.0.8/hammer.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom@2.0.1/dist/chartjs-plugin-zoom.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { font-family: 'Kanit', sans-serif; }
        body { background: #0f172a; color: #e2e8f0; }
        .btn-active { background: #0d9488 !important; color: #fff !important; border-color: #0d9488 !important; }
        #chartCanvas { cursor: crosshair; }
    </style>
</head>
<body class="min-h-screen flex flex-col">

<!-- ── Top bar ── -->
<div class="bg-slate-900 border-b border-slate-700 px-4 py-2.5 flex items-center gap-3 flex-wrap">
    <a href="pm25_dashboard.php" class="text-teal-400 hover:text-teal-300 text-sm flex items-center gap-1.5 transition-colors">
        <i class="fas fa-arrow-left text-xs"></i> กลับ Dashboard
    </a>
    <span class="text-slate-600">|</span>
    <span class="text-slate-300 text-sm font-semibold">กราฟ PM2.5 ย้อนหลัง</span>

    <!-- Days selector -->
    <div class="ml-auto flex items-center gap-1.5">
        <span class="text-slate-500 text-xs">ย้อนหลัง:</span>
        <?php foreach ([7=>'7 วัน', 14=>'14 วัน', 30=>'30 วัน', 60=>'60 วัน', 90=>'90 วัน'] as $d => $label): ?>
        <a href="?cid=<?= urlencode($initCid) ?>&metric=<?= $initMetric ?>&days=<?= $d ?>"
           class="px-2.5 py-1 text-xs rounded-lg border transition-colors
                  <?= $days === $d
                      ? 'bg-teal-600 text-white border-teal-600'
                      : 'border-slate-600 text-slate-400 hover:border-teal-500 hover:text-teal-400' ?>">
            <?= $label ?>
        </a>
        <?php endforeach; ?>

        <button onclick="chart.resetZoom()"
                class="ml-2 flex items-center gap-1 px-3 py-1 text-xs rounded-lg border border-slate-600 text-slate-400 hover:bg-slate-700 transition-colors">
            <i class="fas fa-compress-arrows-alt"></i> Reset
        </button>
    </div>
</div>

<!-- ── Controls ── -->
<div class="bg-slate-800 border-b border-slate-700 px-4 py-3 flex flex-wrap gap-4 items-center">
    <!-- Station -->
    <div class="flex flex-wrap gap-1.5" id="stationBtns">
        <?php foreach ($sensors as $i => $s): ?>
        <button onclick="selectStation(<?= $i ?>)" id="sBtn<?= $i ?>"
                class="px-3 py-1.5 rounded-full text-xs font-medium border border-slate-600 text-slate-400 hover:border-teal-500 transition-colors">
            <?= htmlspecialchars($s['location_name']) ?>
        </button>
        <?php endforeach; ?>
    </div>

    <!-- Metric -->
    <div class="flex gap-1.5 ml-auto flex-wrap">
        <?php foreach ([
            ['pm25','PM2.5','#3BCCFF'],['pm10','PM10','#0ea5e9'],['pm1','PM1','#8b5cf6'],
            ['temp','อุณหภูมิ','#f97316'],['humi','ความชื้น','#3b82f6'],['co2','CO2','#92D050'],
        ] as [$mk,$ml,$mc]): ?>
        <button onclick="selectMetric('<?= $mk ?>')" id="mBtn<?= $mk ?>"
                class="px-3 py-1 rounded-lg text-xs font-medium border transition-all"
                style="border-color:<?= $mc ?>;color:<?= $mc ?>">
            <?= $ml ?>
        </button>
        <?php endforeach; ?>
    </div>
</div>

<!-- ── Chart info bar ── -->
<div class="bg-slate-900 px-5 py-2 flex items-center gap-3 border-b border-slate-800 text-sm">
    <span id="infoStation" class="font-semibold text-slate-200"></span>
    <span id="infoMetric"  class="text-slate-500 text-xs"></span>
    <span id="infoValue"   class="font-bold ml-auto"></span>
    <span class="text-slate-600 text-xs hidden sm:inline">scroll = zoom · drag = pan</span>
</div>

<!-- ── Chart ── -->
<div class="flex-1 p-4" style="min-height:0">
    <div class="bg-slate-900 rounded-xl border border-slate-700 h-full p-3" style="min-height:400px">
        <?php if (empty($chartLabels)): ?>
        <div class="flex items-center justify-center h-full text-slate-500">
            <div class="text-center">
                <i class="fas fa-chart-line text-4xl mb-3 block opacity-30"></i>
                <p>ไม่มีข้อมูลในช่วง <?= $days ?> วันที่ผ่านมา</p>
            </div>
        </div>
        <?php else: ?>
        <canvas id="chartCanvas"></canvas>
        <?php endif; ?>
    </div>
</div>

<!-- ── Footer hint ── -->
<div class="text-center text-xs text-slate-600 py-2">
    ข้อมูลย้อนหลัง <?= $days ?> วัน · เฉลี่ยรายชั่วโมง · <?= count($chartLabels) ?> จุด · เทศบาลนครรังสิต
</div>
<?php $privacy_category='pm25'; $privacy_page_title='กราฟ PM2.5 ย้อนหลัง — เทศบาลนครรังสิต'; require_once __DIR__.'/includes/privacy_consent.php'; ?>

<?php if (!empty($chartLabels)): ?>
<script>
const chartLabels  = <?= json_encode($chartLabels) ?>;
const chartTsKeys  = <?= json_encode($chartTsKeys) ?>;
const chartRawData = <?= json_encode($chartRawByCid) ?>;
const sensorList   = <?= json_encode(array_map(fn($s) => ['cid'=>$s['cid'],'name'=>$s['location_name']], $sensors)) ?>;

const COLORS = ['#3BCCFF','#92D050','#FFA200','#8b5cf6','#F04646','#FFFF00','#06b6d4','#ec4899'];
const metricConfig = {
    pm25: { label:'PM2.5',   unit:'µg/m³', color:'#3BCCFF' },
    pm10: { label:'PM10',    unit:'µg/m³', color:'#0ea5e9' },
    pm1:  { label:'PM1',     unit:'µg/m³', color:'#8b5cf6' },
    temp: { label:'อุณหภูมิ', unit:'°C',   color:'#f97316' },
    humi: { label:'ความชื้น', unit:'%',    color:'#3b82f6' },
    co2:  { label:'CO2',     unit:'ppm',   color:'#92D050' },
};

let activeStation = 0, activeMetric = '<?= $initMetric ?>';

// find initial station by cid
const initCid = '<?= addslashes($initCid) ?>';
if (initCid) {
    const idx = sensorList.findIndex(s => s.cid === initCid);
    if (idx >= 0) activeStation = idx;
}

function getData(stIdx, metric) {
    const cid = sensorList[stIdx].cid;
    const raw = chartRawData[cid] || {};
    return chartTsKeys.map(ts => { const r = raw[ts]; return r ? (r[metric] ?? null) : null; });
}

// ── Chart init ────────────────────────────────────────────────────────────────
const ctx = document.getElementById('chartCanvas').getContext('2d');
const chart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: chartLabels,
        datasets: [{
            data: [], borderColor: '#3BCCFF', backgroundColor: '#3BCCFF15',
            borderWidth: 1.5, pointRadius: 0, pointHoverRadius: 4,
            fill: true, tension: 0.3, spanGaps: true,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#1e293b',
                titleColor: '#94a3b8',
                bodyColor: '#f1f5f9',
                borderColor: '#334155',
                borderWidth: 1,
                callbacks: {
                    label: c => ` ${c.parsed.y !== null ? c.parsed.y : '–'} ${metricConfig[activeMetric].unit}`
                }
            },
            zoom: {
                zoom: {
                    wheel: { enabled: true, speed: 0.1 },
                    pinch: { enabled: true },
                    mode: 'x',
                },
                pan: {
                    enabled: true,
                    mode: 'x',
                    threshold: 3,
                },
                limits: {
                    x: { minRange: 3 },
                },
            },
        },
        scales: {
            x: {
                ticks: {
                    maxTicksLimit: 12,
                    font: { family:'Kanit', size: 10 },
                    color: '#64748b',
                    maxRotation: 0,
                },
                grid: { color: '#1e293b' },
                border: { color: '#334155' },
            },
            y: {
                beginAtZero: false,
                title: { display: true, text: '', font: { family:'Kanit', size: 11 }, color: '#64748b' },
                ticks: { font: { family:'Kanit', size: 10 }, color: '#64748b' },
                grid: { color: '#1e293b' },
                border: { color: '#334155' },
            }
        },
        animation: false,
    }
});

// ── Select functions ──────────────────────────────────────────────────────────
function selectStation(idx) {
    activeStation = idx;
    document.querySelectorAll('[id^="sBtn"]').forEach((b, i) => {
        const c = COLORS[i % COLORS.length];
        b.style.background   = i === idx ? c       : 'transparent';
        b.style.color        = i === idx ? '#0f172a': '#94a3b8';
        b.style.borderColor  = i === idx ? c       : '#475569';
    });
    updateChart();
}

function selectMetric(k) {
    activeMetric = k;
    document.querySelectorAll('[id^="mBtn"]').forEach(b => {
        const mk = b.id.replace('mBtn','');
        const c  = metricConfig[mk].color;
        b.style.background  = mk === k ? c : 'transparent';
        b.style.color       = mk === k ? '#0f172a' : c;
        b.style.borderColor = c;
    });
    updateChart();
}

function updateChart() {
    const mc      = metricConfig[activeMetric];
    const data    = getData(activeStation, activeMetric);
    const latest  = [...data].reverse().find(v => v !== null);
    const color   = COLORS[activeStation % COLORS.length];

    document.getElementById('infoStation').textContent = sensorList[activeStation].name;
    document.getElementById('infoMetric').textContent  = mc.label;
    const valEl = document.getElementById('infoValue');
    valEl.textContent = latest != null ? latest + ' ' + mc.unit : '--';
    valEl.style.color = color;

    chart.data.datasets[0].data            = data;
    chart.data.datasets[0].borderColor     = color;
    chart.data.datasets[0].backgroundColor = color + '15';
    chart.options.scales.y.title.text      = mc.label + ' (' + mc.unit + ')';
    chart.update('none');
}

// ── Init ──────────────────────────────────────────────────────────────────────
selectMetric(activeMetric);
selectStation(activeStation);

// resize canvas to fill container
function resizeChart() {
    const container = document.getElementById('chartCanvas').parentElement;
    document.getElementById('chartCanvas').style.height = (container.offsetHeight - 20) + 'px';
}
window.addEventListener('resize', resizeChart);
resizeChart();
</script>
<?php endif; ?>
</body>
</html>
