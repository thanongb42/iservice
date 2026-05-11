@echo off
:: ตั้งค่า Windows Task Scheduler ให้ดึง PM2.5 ทุก 10 นาที
:: ดับเบิลคลิกไฟล์นี้ แล้วเลือก "Run as administrator"

echo ============================================
echo  ตั้งค่า Scheduled Task: PM25 Fetch 1 hour
echo ============================================

schtasks /Create ^
  /TN "PM25_Fetch_Hourly" ^
  /TR "\"C:\xampp\php\php.exe\" \"C:\xampp\htdocs\iservice\pm25_cron.php\"" ^
  /SC HOURLY ^
  /MO 1 ^
  /ST 00:00 ^
  /F ^
  /RL HIGHEST

if %ERRORLEVEL% == 0 (
    echo.
    echo [OK] Task ถูกสร้างเรียบร้อยแล้ว
    echo      ชื่อ Task : PM25_Fetch_Hourly
    echo      รันทุก   : 1 ชั่วโมง
    echo      Script   : C:\xampp\htdocs\iservice\pm25_cron.php
    echo      Log      : C:\xampp\htdocs\iservice\storage\pm25_cron.log
) else (
    echo.
    echo [ERROR] สร้าง Task ไม่สำเร็จ
    echo         กรุณาคลิกขวา "Run as administrator" แล้วลองใหม่
)

echo.
pause
