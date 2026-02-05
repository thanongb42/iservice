<?php
// Simple test to verify calendar rendering

$tasks = [
    [
        'assignment_id' => 1,
        'request_code' => 'REQ-2026-001',
        'status' => 'pending',
        'start_time' => '2026-02-07 08:31:00',
        'end_time' => '2026-02-07 10:00:00'
    ],
    [
        'assignment_id' => 2,
        'request_code' => 'REQ-2026-002',
        'status' => 'in_progress',
        'start_time' => '2026-02-10 14:00:00',
        'end_time' => null
    ]
];

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Calendar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            padding: 2rem;
            background: #f3f4f6;
        }

        .calendar-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            margin: 1rem 0;
        }

        .calendar-table th {
            background: #f3f4f6;
            padding: 1rem;
            text-align: center;
            font-weight: 600;
            color: #374151;
        }

        .calendar-table td {
            padding: 0.5rem;
            border: 1px solid #e5e7eb;
            height: 120px;
            vertical-align: top;
            position: relative;
        }

        .calendar-day-number {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }

        .calendar-task-item {
            background: #dbeafe;
            color: #0c4a6e;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            margin-bottom: 0.25rem;
            font-size: 0.75rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .calendar-task-item.status-pending { background: #fef3c7; color: #92400e; }
        .calendar-task-item.status-in_progress { background: #c7d2fe; color: #3730a3; }
        .calendar-task-item.status-completed { background: #dcfce7; color: #166534; }
    </style>
</head>
<body>
    <h1>Test Calendar Rendering</h1>
    <div id="calendar-month-year"></div>
    
    <table class="calendar-table">
        <thead>
            <tr>
                <th>จันทร์</th>
                <th>อังคาร</th>
                <th>พุธ</th>
                <th>พฤหัสบดี</th>
                <th>ศุกร์</th>
                <th>เสาร์</th>
                <th>อาทิตย์</th>
            </tr>
        </thead>
        <tbody id="calendar-body">
        </tbody>
    </table>

    <script>
        const tasksData = <?= json_encode($tasks) ?>;
        
        let currentMonth = new Date().getMonth();
        let currentYear = new Date().getFullYear();

        function getTasksForDay(date) {
            const dateStr = date.getFullYear() + '-' + 
                           String(date.getMonth() + 1).padStart(2, '0') + '-' + 
                           String(date.getDate()).padStart(2, '0');
            const tasks = [];

            tasksData.forEach(task => {
                if (task.start_time) {
                    const taskDateStr = task.start_time.split(' ')[0];
                    if (taskDateStr === dateStr) {
                        tasks.push(task);
                    }
                }
            });

            return tasks;
        }

        function renderCalendar() {
            console.log('renderCalendar called, currentMonth:', currentMonth, 'currentYear:', currentYear);
            
            const firstDay = new Date(currentYear, currentMonth, 1);
            const lastDay = new Date(currentYear, currentMonth + 1, 0);
            const daysInMonth = lastDay.getDate();
            const startingDayOfWeek = (firstDay.getDay() + 6) % 7;

            console.log('daysInMonth:', daysInMonth, 'startingDayOfWeek:', startingDayOfWeek);

            const monthNames = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
                               'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
            document.getElementById('calendar-month-year').textContent = `${monthNames[currentMonth]} ${currentYear + 543}`;

            const calendarBody = document.getElementById('calendar-body');
            calendarBody.innerHTML = '';

            let weekRow = document.createElement('tr');

            // Previous month days
            for (let i = 0; i < startingDayOfWeek; i++) {
                const cell = document.createElement('td');
                cell.style.background = '#f9fafb';
                weekRow.appendChild(cell);
            }

            // Current month days
            const today = new Date();
            for (let day = 1; day <= daysInMonth; day++) {
                if (weekRow.children.length === 7) {
                    calendarBody.appendChild(weekRow);
                    weekRow = document.createElement('tr');
                }

                const cell = document.createElement('td');
                const cellDate = new Date(currentYear, currentMonth, day);
                
                const cellDateStr = cellDate.getFullYear() + '-' + 
                                   String(cellDate.getMonth() + 1).padStart(2, '0') + '-' + 
                                   String(cellDate.getDate()).padStart(2, '0');

                cell.innerHTML = `<div class="calendar-day-number">${day}</div>`;
                cell.dataset.date = cellDateStr;

                const dayTasks = getTasksForDay(cellDate);
                if (dayTasks.length > 0) {
                    const tasksDiv = document.createElement('div');
                    dayTasks.forEach(task => {
                        const taskEl = document.createElement('div');
                        taskEl.classList.add('calendar-task-item', `status-${task.status}`);
                        taskEl.textContent = task.request_code;
                        tasksDiv.appendChild(taskEl);
                    });
                    cell.appendChild(tasksDiv);
                }

                weekRow.appendChild(cell);
            }

            // Next month days
            while (weekRow.children.length < 7) {
                const cell = document.createElement('td');
                cell.style.background = '#f9fafb';
                weekRow.appendChild(cell);
            }
            calendarBody.appendChild(weekRow);

            console.log('Calendar rendered successfully');
        }

        // Render on page load
        document.addEventListener('DOMContentLoaded', renderCalendar);
    </script>
</body>
</html>
