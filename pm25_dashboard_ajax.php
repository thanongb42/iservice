<?php
// pm25_dashboard_ajax.php - Dashboard PM2.5 แบบ AJAX
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>PM2.5 Dashboard (AJAX)</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Kanit', sans-serif; background: #f4f8fb; margin: 0; padding: 0; }
        .container { max-width: 1100px; margin: 2em auto; padding: 1em; }
        h2 { text-align: center; color: #0d9488; margin-bottom: 1.5em; }
        .dashboard { display: flex; flex-wrap: wrap; gap: 2em; justify-content: center; }
        .card {
            background: #fff;
            border-radius: 1em;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            padding: 2em 2em 1.5em 2em;
            min-width: 260px;
            max-width: 320px;
            flex: 1 1 260px;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
        }
        .pm25-value {
            font-size: 2.8em;
            font-weight: bold;
            color: #0d9488;
            margin-bottom: 0.2em;
        }
        .cid-label {
            font-size: 1.1em;
            color: #888;
            margin-bottom: 0.7em;
        }
        .time-label {
            font-size: 0.95em;
            color: #aaa;
            margin-bottom: 1em;
        }
        .card canvas { max-width: 100%; margin-top: 0.5em; min-height: 180px; height: 180px; }
        @media (max-width: 900px) {
            .dashboard { flex-direction: column; align-items: center; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>PM2.5 Dashboard (AJAX Realtime)</h2>
        <div class="dashboard" id="dashboard"></div>
    </div>
    <script>
    let chartMap = {};
    function renderDashboard(data) {
        const dash = document.getElementById('dashboard');
        dash.innerHTML = '';
        // Group ข้อมูลล่าสุดตาม cid
        const group = {};
        data.latest.forEach(row => {
            if (!group[row.cid]) group[row.cid] = [];
            group[row.cid].push(row);
        });
        Object.keys(group).forEach(cid => {
            const row = group[cid][0]; // ใช้ record ล่าสุดของแต่ละ cid
            const pm25 = row.pm25;
            const time = new Date(row.sensor_timestamp * 1000);
            const timeStr = time.getFullYear() + '-' + String(time.getMonth()+1).padStart(2,'0') + '-' + String(time.getDate()).padStart(2,'0') + ' ' + time.toLocaleTimeString('th-TH', {hour:'2-digit',minute:'2-digit'});
            const card = document.createElement('div');
            card.className = 'card';
            card.innerHTML = `
                <div class="pm25-value">${pm25}</div>
                <div class="cid-label">CID: ${cid}</div>
                <div class="time-label">อัปเดตล่าสุด: ${timeStr}</div>
                <canvas id="chart_${cid}"></canvas>
            `;
            dash.appendChild(card);
            setTimeout(() => {
                const points = (data.chartData[cid]||[]);
                if (chartMap[cid]) chartMap[cid].destroy();
                chartMap[cid] = new Chart(document.getElementById('chart_'+cid).getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: points.map(p=>p.time),
                        datasets: [{
                            label: 'PM2.5',
                            data: points.map(p=>p.pm25),
                            borderColor: '#0d9488',
                            backgroundColor: 'rgba(13,148,136,0.08)',
                            fill: true,
                            tension: 0.3,
                            pointRadius: 2
                        }]
                    },
                    options: {
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero:true } },
                        responsive: true,
                        maintainAspectRatio: true,
                        animation: false
                    }
                });
            }, 0);
        });
    }
    async function fetchData() {
        const res = await fetch('pm25_api.php');
        const data = await res.json();
        renderDashboard(data);
    }
    fetchData();
    setInterval(fetchData, 60000);
    </script>
</body>
</html>
