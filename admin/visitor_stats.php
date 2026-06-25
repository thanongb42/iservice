<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin','staff'])) {
    header('Location: ../login.php'); exit;
}
require_once '../config/database.php';
$pdo = getPDO();
$current_page = 'visitor_stats';

$days  = max(7, min(365, (int)($_GET['days'] ?? 30)));
$since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

function q(PDO $pdo, string $sql, array $p = []): array {
    try { $s=$pdo->prepare($sql); $s->execute($p); return $s->fetchAll(PDO::FETCH_ASSOC); }
    catch (Exception $e) { return []; }
}
function q1(PDO $pdo, string $sql, array $p = []): mixed {
    $r = q($pdo,$sql,$p); return $r ? array_values($r[0])[0] : null;
}

// ── Overall stats ─────────────────────────────────────────────────────────────
$totalPV    = (int)q1($pdo,"SELECT COUNT(*) FROM visitor_pageviews WHERE visited_at>=?",[$since]);
$uniqueVis  = (int)q1($pdo,"SELECT COUNT(DISTINCT visitor_hash) FROM visitor_pageviews WHERE visited_at>=?",[$since]);
$totalSess  = (int)q1($pdo,"SELECT COUNT(*) FROM visitor_sessions WHERE started_at>=?",[$since]);
$consentYes = (int)q1($pdo,"SELECT COUNT(*) FROM visitor_sessions WHERE started_at>=? AND consent_given=1",[$since]);

// ── Per-page stats (แยกหน้า) ──────────────────────────────────────────────────
$pageStats = q($pdo,"
    SELECT
        page_category,
        COUNT(*) AS pageviews,
        COUNT(DISTINCT visitor_hash) AS unique_visitors,
        COUNT(DISTINCT session_id)   AS sessions
    FROM visitor_pageviews
    WHERE visited_at >= ?
    GROUP BY page_category
    ORDER BY pageviews DESC
",[$since]);

// ── Daily trend ───────────────────────────────────────────────────────────────
$daily = q($pdo,"
    SELECT
        DATE(visited_at) AS d,
        COUNT(*) AS pageviews,
        COUNT(DISTINCT visitor_hash) AS unique_vis,
        COUNT(DISTINCT session_id)   AS sessions
    FROM visitor_pageviews
    WHERE visited_at >= ?
    GROUP BY DATE(visited_at)
    ORDER BY d ASC
",[$since]);

// ── Per-page daily breakdown (top 5 categories) ───────────────────────────────
$topCats = array_column(array_slice($pageStats, 0, 5), 'page_category');
$catDaily = [];
foreach ($topCats as $cat) {
    $rows = q($pdo,"
        SELECT DATE(visited_at) AS d,
               COUNT(*) AS pv,
               COUNT(DISTINCT visitor_hash) AS uv
        FROM visitor_pageviews
        WHERE visited_at>=? AND page_category=?
        GROUP BY DATE(visited_at) ORDER BY d ASC
    ",[$since, $cat]);
    $catDaily[$cat] = $rows;
}

// ── Survey stats ──────────────────────────────────────────────────────────────
$totalSurveys = (int)q1($pdo,"SELECT COUNT(*) FROM satisfaction_surveys WHERE submitted_at>=?",[$since]);
$avgRating    = q1($pdo,"SELECT ROUND(AVG(rating),2) FROM satisfaction_surveys WHERE submitted_at>=?",[$since]);
$ratingDist   = q($pdo,"SELECT rating, COUNT(*) cnt FROM satisfaction_surveys WHERE submitted_at>=? GROUP BY rating ORDER BY rating",[$since]);

// ── Device / Browser ─────────────────────────────────────────────────────────
$devices  = q($pdo,"SELECT device_type, COUNT(*) cnt FROM visitor_sessions WHERE started_at>=? GROUP BY device_type ORDER BY cnt DESC",[$since]);
$browsers = q($pdo,"SELECT browser, COUNT(*) cnt FROM visitor_sessions WHERE started_at>=? GROUP BY browser ORDER BY cnt DESC LIMIT 6",[$since]);

// ── Recent comments ───────────────────────────────────────────────────────────
$comments = q($pdo,"
    SELECT rating, comment, category, DATE_FORMAT(submitted_at,'%d/%m %H:%i') AS dt
    FROM satisfaction_surveys
    WHERE submitted_at>=? AND comment IS NOT NULL AND comment!=''
    ORDER BY submitted_at DESC LIMIT 15
",[$since]);

// ── Page labels ───────────────────────────────────────────────────────────────
$pageLabelMap = [
    'index'          => '🏠 หน้าหลัก (iService)',
    'pm25_dashboard' => '🌬️ PM2.5 Dashboard',
    'pm25_report'    => '📊 PM2.5 รายงาน',
    'pm25_chart'     => '📈 PM2.5 กราฟย้อนหลัง',
    'general'        => '🔗 อื่นๆ',
];
$pageColors = ['index'=>'#6366f1','pm25_dashboard'=>'#0d9488','pm25_report'=>'#f59e0b','pm25_chart'=>'#3b82f6','general'=>'#94a3b8'];

include 'admin-layout/header.php';
include 'admin-layout/sidebar.php';
include 'admin-layout/topbar.php';
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
* { font-family:'Kanit',sans-serif; }
.sc { background:#fff;border-radius:16px;padding:20px 24px;box-shadow:0 1px 4px rgba(0,0,0,.06);border:1px solid #f1f5f9 }
.badge { display:inline-block;padding:2px 10px;border-radius:999px;font-size:11px;font-weight:600 }
</style>

<div class="p-6 max-w-screen-2xl">

    <!-- Header -->
    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
        <div>
            <h1 class="text-xl font-bold text-gray-800">สถิติผู้เข้าชม & ความพึงพอใจ</h1>
            <p class="text-sm text-gray-400">Rangsit Smart City Analytics · <?= $days ?> วันล่าสุด</p>
        </div>
        <div class="flex gap-2 flex-wrap">
            <?php foreach ([7=>'7 วัน',14=>'14 วัน',30=>'30 วัน',90=>'90 วัน',365=>'1 ปี'] as $d=>$l): ?>
            <a href="?days=<?= $d ?>" class="px-3 py-1.5 rounded-xl text-xs font-medium border transition-colors
                <?= $days==$d ? 'bg-teal-600 text-white border-teal-600' : 'border-gray-200 text-gray-500 hover:border-teal-400' ?>">
                <?= $l ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ── KPI Cards ── -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <?php foreach ([
            ['fas fa-eye',      'bg-blue-50',   'text-blue-600',   number_format($totalPV),   'การเข้าชมทั้งหมด (Pageviews)'],
            ['fas fa-users',    'bg-teal-50',   'text-teal-600',   number_format($uniqueVis), 'ผู้เยี่ยมชมไม่ซ้ำ (Unique IP)'],
            ['fas fa-route',    'bg-purple-50', 'text-purple-600', number_format($totalSess), 'Sessions ทั้งหมด'],
            ['fas fa-star',     'bg-yellow-50', 'text-yellow-600', ($avgRating ? number_format((float)$avgRating,1).' / 5' : '–'), 'คะแนนเฉลี่ย ('.$totalSurveys.' ประเมิน)'],
        ] as [$icon,$bg,$col,$val,$label]): ?>
        <div class="sc flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl <?= $bg ?> flex items-center justify-center shrink-0">
                <i class="<?= $icon ?> <?= $col ?> text-xl"></i>
            </div>
            <div>
                <div class="text-2xl font-bold text-gray-800"><?= $val ?></div>
                <div class="text-xs text-gray-400 leading-snug"><?= $label ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ── Per-Page Stats ── -->
    <div class="sc mb-6">
        <h3 class="text-sm font-semibold text-gray-600 mb-4">สถิติแยกตามหน้า
            <span class="text-xs font-normal text-gray-400 ml-2">· คนเดียวเข้าหลายหน้า = นับ 1 unique ต่อหน้า แต่ในภาพรวม = 1</span>
        </h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-400 text-xs">
                        <th class="px-4 py-2 text-left">หน้า</th>
                        <th class="px-4 py-2 text-right">Pageviews</th>
                        <th class="px-4 py-2 text-right">Unique Visitors</th>
                        <th class="px-4 py-2 text-right">Sessions</th>
                        <th class="px-4 py-2 text-right">Views/Session</th>
                        <th class="px-4 py-2 text-left min-w-[140px]">สัดส่วน</th>
                    </tr>
                </thead>
                <tbody>
                <?php $maxPV = max(1, (int)($pageStats[0]['pageviews'] ?? 1));
                foreach ($pageStats as $ps):
                    $cat   = $ps['page_category'];
                    $label = $pageLabelMap[$cat] ?? $cat;
                    $color = $pageColors[$cat]   ?? '#94a3b8';
                    $vps   = $ps['sessions'] > 0 ? round($ps['pageviews']/$ps['sessions'],1) : 0;
                    $pct   = round($ps['pageviews']/$maxPV*100);
                ?>
                <tr class="border-t border-gray-50 hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <span class="font-medium text-gray-700"><?= $label ?></span>
                    </td>
                    <td class="px-4 py-3 text-right font-bold" style="color:<?= $color ?>"><?= number_format($ps['pageviews']) ?></td>
                    <td class="px-4 py-3 text-right text-gray-600"><?= number_format($ps['unique_visitors']) ?></td>
                    <td class="px-4 py-3 text-right text-gray-600"><?= number_format($ps['sessions']) ?></td>
                    <td class="px-4 py-3 text-right text-gray-400 text-xs"><?= $vps ?> views</td>
                    <td class="px-4 py-3">
                        <div class="bg-gray-100 rounded-full h-2 overflow-hidden">
                            <div class="h-2 rounded-full" style="width:<?= $pct ?>%;background:<?= $color ?>"></div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <!-- รวม -->
                <tr class="border-t-2 border-gray-200 bg-teal-50">
                    <td class="px-4 py-2.5 font-bold text-teal-700">รวมทั้งหมด</td>
                    <td class="px-4 py-2.5 text-right font-bold text-teal-700"><?= number_format($totalPV) ?></td>
                    <td class="px-4 py-2.5 text-right font-bold text-teal-700"><?= number_format($uniqueVis) ?><span class="text-xs font-normal text-teal-400 ml-1">(ไม่ซ้ำ)</span></td>
                    <td class="px-4 py-2.5 text-right font-bold text-teal-700"><?= number_format($totalSess) ?></td>
                    <td colspan="2"></td>
                </tr>
                </tbody>
            </table>
        </div>
        <p class="text-xs text-gray-400 mt-2">* Unique Visitors รวม = ผู้เยี่ยมชมที่ไม่ซ้ำกัน (คนเดิมเข้าหลายหน้า = นับ 1 เท่านั้น)</p>
    </div>

    <!-- ── Charts ── -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-6">

        <!-- Daily trend -->
        <div class="sc lg:col-span-2">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gray-600">การเข้าชมรายวัน (ภาพรวม)</h3>
                <div class="flex gap-3 text-xs text-gray-400">
                    <span class="flex items-center gap-1"><span class="w-3 h-1 bg-teal-500 inline-block rounded"></span>Pageviews</span>
                    <span class="flex items-center gap-1"><span class="w-3 h-1 bg-purple-400 inline-block rounded"></span>Unique</span>
                </div>
            </div>
            <div style="height:220px"><canvas id="dailyChart"></canvas></div>
        </div>

        <!-- Rating -->
        <div class="sc">
            <h3 class="text-sm font-semibold text-gray-600 mb-3">ความพึงพอใจ</h3>
            <?php
            $rMap   = array_column($ratingDist,'cnt','rating');
            $rTotal = array_sum(array_column($ratingDist,'cnt')) ?: 1;
            $emojis = ['','😞','😐','🙂','😊','😍'];
            $rcols  = ['','#ef4444','#f97316','#eab308','#22c55e','#0d9488'];
            for ($r=5;$r>=1;$r--): $cnt=(int)($rMap[$r]??0); $pct=round($cnt/$rTotal*100); ?>
            <div class="flex items-center gap-2 mb-2">
                <span class="w-5 text-sm"><?= $emojis[$r] ?></span>
                <div class="flex-1 bg-gray-100 rounded-full h-4 overflow-hidden">
                    <div class="h-4 rounded-full" style="width:<?= $pct ?>%;background:<?= $rcols[$r] ?>"></div>
                </div>
                <span class="text-xs text-gray-400 w-6 text-right"><?= $cnt ?></span>
            </div>
            <?php endfor; ?>
            <div class="text-center mt-3 pt-3 border-t border-gray-100">
                <span class="text-2xl font-bold text-teal-600"><?= $avgRating ? number_format((float)$avgRating,1) : '–' ?></span>
                <span class="text-gray-400"> / 5.0</span>
                <div class="text-xs text-gray-400"><?= number_format($totalSurveys) ?> การประเมิน</div>
            </div>
        </div>
    </div>

    <!-- ── Per-page daily chart ── -->
    <?php if (count($topCats) > 1): ?>
    <div class="sc mb-6">
        <h3 class="text-sm font-semibold text-gray-600 mb-3">Pageviews รายวัน แยกตามหน้า</h3>
        <div style="height:240px"><canvas id="catChart"></canvas></div>
    </div>
    <?php endif; ?>

    <!-- ── Device / Browser / Consent ── -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-6">
        <div class="sc">
            <h3 class="text-sm font-semibold text-gray-600 mb-3">อุปกรณ์</h3>
            <?php $dt=array_sum(array_column($devices,'cnt'))?:1;
            $dicon=['desktop'=>'fa-desktop','mobile'=>'fa-mobile-alt','tablet'=>'fa-tablet-alt'];
            foreach ($devices as $d): $pct=round($d['cnt']/$dt*100); ?>
            <div class="flex items-center gap-2 mb-2">
                <i class="fas <?= $dicon[$d['device_type']]??'fa-question' ?> text-gray-400 w-4"></i>
                <span class="text-xs flex-1 text-gray-600"><?= $d['device_type'] ?></span>
                <div class="w-24 bg-gray-100 rounded-full h-2">
                    <div class="h-2 rounded-full bg-teal-500" style="width:<?= $pct ?>%"></div>
                </div>
                <span class="text-xs text-gray-400 w-8 text-right"><?= $pct ?>%</span>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="sc">
            <h3 class="text-sm font-semibold text-gray-600 mb-3">เบราว์เซอร์</h3>
            <?php $bt=array_sum(array_column($browsers,'cnt'))?:1;
            foreach ($browsers as $b): $pct=round($b['cnt']/$bt*100); ?>
            <div class="flex items-center gap-2 mb-1.5">
                <span class="text-xs flex-1 text-gray-600"><?= htmlspecialchars($b['browser']) ?></span>
                <div class="w-20 bg-gray-100 rounded-full h-2">
                    <div class="h-2 rounded-full bg-blue-400" style="width:<?= $pct ?>%"></div>
                </div>
                <span class="text-xs text-gray-400 w-8 text-right"><?= number_format($b['cnt']) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="sc">
            <h3 class="text-sm font-semibold text-gray-600 mb-3">การยินยอม Cookie</h3>
            <?php $cpct=$totalSess ? round($consentYes/$totalSess*100) : 0; ?>
            <div class="bg-gray-100 rounded-full h-6 overflow-hidden relative mb-2">
                <div class="h-6 rounded-full bg-teal-500" style="width:<?= $cpct ?>%"></div>
                <span class="absolute inset-0 flex items-center justify-center text-xs font-bold text-gray-700">
                    <?= $cpct ?>% ยอมรับ
                </span>
            </div>
            <div class="flex justify-between text-xs text-gray-500">
                <span>✅ <?= number_format($consentYes) ?> sessions</span>
                <span>❌ <?= number_format($totalSess-$consentYes) ?> sessions</span>
            </div>
        </div>
    </div>

    <!-- ── Comments ── -->
    <?php if ($comments): ?>
    <div class="sc">
        <h3 class="text-sm font-semibold text-gray-600 mb-3">ความคิดเห็นล่าสุด</h3>
        <div class="space-y-2">
        <?php foreach ($comments as $c): ?>
        <div class="flex gap-3 p-3 bg-gray-50 rounded-xl">
            <span class="text-2xl shrink-0"><?= $emojis[(int)$c['rating']] ?? '⭐' ?></span>
            <div class="flex-1">
                <div class="flex items-center gap-1 mb-1">
                    <?php for($s=1;$s<=5;$s++) echo '<span style="color:'.($s<=(int)$c['rating']?'#f59e0b':'#e2e8f0').';font-size:11px">★</span>'; ?>
                    <span class="text-xs bg-gray-200 text-gray-500 px-1.5 rounded ml-1"><?= htmlspecialchars($c['category']) ?></span>
                    <span class="ml-auto text-xs text-gray-300"><?= $c['dt'] ?></span>
                </div>
                <p class="text-sm text-gray-600"><?= htmlspecialchars($c['comment']) ?></p>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<script>
const F = { family:'Kanit', size:10 };
const daily = <?= json_encode($daily) ?>;
const catData = <?= json_encode($catDaily) ?>;
const pageColors = <?= json_encode($pageColors) ?>;
const pageLabelMap = <?= json_encode($pageLabelMap) ?>;

// ── Daily overall chart ──────────────────────────────────────────────────────
new Chart(document.getElementById('dailyChart').getContext('2d'), {
    data: {
        labels: daily.map(d=>d.d),
        datasets: [
            { type:'bar',  label:'Pageviews', data:daily.map(d=>d.pageviews), backgroundColor:'#0d948825', borderColor:'#0d9488', borderWidth:1.5, borderRadius:3 },
            { type:'line', label:'Unique',    data:daily.map(d=>d.unique_vis), borderColor:'#8b5cf6', backgroundColor:'transparent', borderWidth:2, pointRadius:2, tension:.3 },
        ]
    },
    options: {
        responsive:true, maintainAspectRatio:false, animation:false,
        interaction:{mode:'index',intersect:false},
        plugins:{ legend:{labels:{font:F}} },
        scales:{ x:{ticks:{maxTicksLimit:12,font:F,maxRotation:0},grid:{color:'#f8fafc'}}, y:{beginAtZero:true,ticks:{font:F},grid:{color:'#f8fafc'}} }
    }
});

// ── Per-category chart ────────────────────────────────────────────────────────
const catEl = document.getElementById('catChart');
if (catEl && Object.keys(catData).length > 0) {
    const allDates = [...new Set(Object.values(catData).flat().map(r=>r.d))].sort();
    const datasets = Object.entries(catData).map(([cat, rows]) => {
        const byDate = Object.fromEntries(rows.map(r=>[r.d, r.pv]));
        return {
            label: (pageLabelMap[cat]||cat),
            data: allDates.map(d => byDate[d]||0),
            borderColor: pageColors[cat]||'#94a3b8',
            backgroundColor: (pageColors[cat]||'#94a3b8') + '20',
            borderWidth: 2, pointRadius: 2, tension: .3, fill: false,
        };
    });
    new Chart(catEl.getContext('2d'), {
        type: 'line',
        data: { labels: allDates, datasets },
        options: {
            responsive:true, maintainAspectRatio:false, animation:false,
            interaction:{mode:'index',intersect:false},
            plugins:{ legend:{labels:{font:{family:'Kanit',size:11}}} },
            scales:{ x:{ticks:{maxTicksLimit:12,font:F,maxRotation:0},grid:{color:'#f8fafc'}}, y:{beginAtZero:true,ticks:{font:F},grid:{color:'#f8fafc'}} }
        }
    });
}
</script>

<?php include 'admin-layout/footer.php'; ?>
