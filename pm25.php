<?php
// pm25.php
$url = "https://app.freshnergy.com/api/v2/device";
$api_key = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJhY2NvdW50Ijp7ImVtYWlsIjoiYXFpQHB0aXMuYWMudGgiLCJhcGkiOnsiZXhwaXJlIjp7Il9zZWNvbmRzIjoxNzY5NzkyNDAwLCJfbmFub3NlY29uZHMiOjQ2NjAwMDAwMH0sImFsbG93Ijp0cnVlfX0sImlhdCI6MTczNjk0NTkxMDE3MCwiZXhwIjoxNzM3MDMyMzEwMTcwfQ.qQ8AFrwvIn2UaOkhdKUXynki1izs5BmY1f31EFv25qk"; // <-- ใส่ API Key ของคุณที่นี่ (เฉพาะค่า token)

// ตัวอย่างค่า cid ที่ต้องการดึงข้อมูล
$body = [
    "cid" => ["E465B875ADCC", "E86BEAF6EB60"]
];

// 2. ตั้งค่า cURL
$ch = curl_init();


curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));

// ส่ง API Key ใน header และส่ง body เป็น RAW JSON
$headers = [
    "Authorization: $api_key", // ใส่เฉพาะ token
    "Content-Type: application/json"
];
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));

// 3. ประมวลผลและเก็บค่า
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
} else {
    if ($http_code == 200) {
        // แปลงข้อมูล JSON เป็น Array
        $data = json_decode($response, true);
        

        // แสดงโครงสร้างข้อมูลที่ดึงมาได้ (เพื่อดูชื่อฟิลด์ของ Sensor)
        echo "<pre>";
        print_r($data);
        echo "</pre>";

        // --- บันทึกข้อมูลลงฐานข้อมูล ---
        require_once __DIR__ . '/config/database.php';
        $pdo = getPDO();
        if (isset($data['data']) && is_array($data['data'])) {
            $stmt = $pdo->prepare("INSERT INTO pm25_data (cid, pm25, co2, pm1, pm10, pm4, sensor_timestamp) VALUES (?, ?, ?, ?, ?, ?, ?)");
            foreach ($data['data'] as $item) {
                $cid = $item['cid'] ?? '';
                $sensor = $item['sensor'] ?? [];
                $pm25 = $sensor['pm2_5'] ?? null;
                $co2 = $sensor['co2'] ?? null;
                $pm1 = $sensor['pm1'] ?? null;
                $pm10 = $sensor['pm10'] ?? null;
                $pm4 = $sensor['pm4'] ?? null;
                $sensor_timestamp = $sensor['timeStamp'] ?? null;
                if ($cid && $pm25 !== null && $sensor_timestamp) {
                    $stmt->execute([$cid, $pm25, $co2, $pm1, $pm10, $pm4, $sensor_timestamp]);
                }
            }
        }

        // ตัวอย่างการเข้าถึงค่า (สมมติโครงสร้างข้อมูล)
        // $pm25_value = $data['data']['pm25']; 
            // ตัวอย่างการเข้าถึงค่า (โปรดตรวจสอบโครงสร้างจริงจาก print_r)
            // สมมติว่า $data['data'][0]['pm25']
            // $pm25_value = $data['data'][0]['pm25'] ?? null;
            // echo "ค่า PM2.5 ปัจจุบันคือ: " . ($pm25_value !== null ? $pm25_value : 'ไม่พบข้อมูล');
        // echo "ค่า PM2.5 ปัจจุบันคือ: " . $pm25_value;
        
    } else {
        echo "เกิดข้อผิดพลาด HTTP Code: " . $http_code;
        echo "รายละเอียด: " . $response;
    }
}

// 4. ปิดการเชื่อมต่อ
curl_close($ch);

?>