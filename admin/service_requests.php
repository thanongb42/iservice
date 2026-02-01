<?php
/**
 * Service Requests Management
 * Admin interface for managing all service requests
 */

require_once '../config/database.php';
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$page_title = 'Service Requests Management';

// Get statistics
$stats_query = "SELECT
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
FROM service_requests";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Get all service requests using view
$sql = "SELECT * FROM v_service_requests_full ORDER BY created_at DESC";

$result = $conn->query($sql);
$requests = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
}

// Get service types - define standard service types
$service_types = [
    ['service_code' => 'EMAIL', 'service_name' => 'ขอใช้บริการ Email'],
    ['service_code' => 'NAS', 'service_name' => 'ขอใช้พื้นที่ NAS'],
    ['service_code' => 'IT_SUPPORT', 'service_name' => 'ขอรับการสนับสนุนด้าน IT'],
    ['service_code' => 'INTERNET', 'service_name' => 'ขอใช้บริการ Internet'],
    ['service_code' => 'QR_CODE', 'service_name' => 'ขอทำ QR Code'],
    ['service_code' => 'PHOTOGRAPHY', 'service_name' => 'ขอถ่ายภาพกิจกรรม'],
    ['service_code' => 'WEB_DESIGN', 'service_name' => 'ขอออกแบบเว็บไซต์'],
    ['service_code' => 'PRINTER', 'service_name' => 'ขอใช้เครื่องพิมพ์']
];

// Try to get actual service codes from database if table has service_code column
$check_query = "SHOW COLUMNS FROM service_requests LIKE 'service_code'";
$check_result = $conn->query($check_query);
if ($check_result && $check_result->num_rows > 0) {
    $db_services_query = "SELECT DISTINCT service_code FROM service_requests WHERE service_code IS NOT NULL";
    $db_services_result = $conn->query($db_services_query);
    if ($db_services_result && $db_services_result->num_rows > 0) {
        // Use only service codes that actually exist in database
        $existing_codes = [];
        while ($row = $db_services_result->fetch_assoc()) {
            $existing_codes[] = $row['service_code'];
        }
        $service_types = array_filter($service_types, function($service) use ($existing_codes) {
            return in_array($service['service_code'], $existing_codes);
        });
    }
}

// Get users for assignment
$users_query = "SELECT user_id, first_name, last_name FROM users WHERE role IN ('admin', 'staff') ORDER BY first_name";
$users_result = $conn->query($users_query);
$users = [];
while ($row = $users_result->fetch_assoc()) {
    $users[] = $row;
}

// Helper function to get service name from code
function getServiceName($code) {
    $service_names = [
        'EMAIL' => 'ขอใช้บริการ Email',
        'NAS' => 'ขอใช้พื้นที่ NAS',
        'IT_SUPPORT' => 'ขอรับการสนับสนุนด้าน IT',
        'INTERNET' => 'ขอใช้บริการ Internet',
        'QR_CODE' => 'ขอทำ QR Code',
        'PHOTOGRAPHY' => 'ขอถ่ายภาพกิจกรรม',
        'WEB_DESIGN' => 'ขอออกแบบเว็บไซต์',
        'PRINTER' => 'ขอใช้เครื่องพิมพ์'
    ];
    return $service_names[$code] ?? $code;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-pending { background-color: #fef3c7; color: #92400e; }
        .status-in_progress { background-color: #dbeafe; color: #1e40af; }
        .status-completed { background-color: #d1fae5; color: #065f46; }
        .status-cancelled { background-color: #fee2e2; color: #991b1b; }

        .priority-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .priority-low { background-color: #f3f4f6; color: #374151; }
        .priority-medium { background-color: #fef3c7; color: #92400e; }
        .priority-high { background-color: #fed7aa; color: #9a3412; }
        .priority-urgent { background-color: #fecaca; color: #991b1b; }

        .stat-card {
            background: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .action-btn {
            padding: 0.5rem;
            margin: 0 0.25rem;
            border-radius: 0.25rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .action-btn:hover {
            transform: scale(1.1);
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background-color: #f9fafb;
            padding: 0.75rem;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #e5e7eb;
        }

        td {
            padding: 0.75rem;
            border-bottom: 1px solid #e5e7eb;
        }

        tr:hover {
            background-color: #f9fafb;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: white;
            margin: 2% auto;
            padding: 2rem;
            border-radius: 0.5rem;
            width: 90%;
            max-width: 800px;
            max-height: 85vh;
            overflow-y: auto;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #000;
        }

        .filter-container {
            background: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">
                <i class="fas fa-tasks"></i> Service Requests Management
            </h1>
            <a href="index.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Total Requests</p>
                        <p class="text-2xl font-bold text-gray-800"><?= $stats['total'] ?></p>
                    </div>
                    <i class="fas fa-clipboard-list text-3xl text-gray-400"></i>
                </div>
            </div>
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Pending</p>
                        <p class="text-2xl font-bold text-yellow-600"><?= $stats['pending'] ?></p>
                    </div>
                    <i class="fas fa-clock text-3xl text-yellow-400"></i>
                </div>
            </div>
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">In Progress</p>
                        <p class="text-2xl font-bold text-blue-600"><?= $stats['in_progress'] ?></p>
                    </div>
                    <i class="fas fa-spinner text-3xl text-blue-400"></i>
                </div>
            </div>
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Completed</p>
                        <p class="text-2xl font-bold text-green-600"><?= $stats['completed'] ?></p>
                    </div>
                    <i class="fas fa-check-circle text-3xl text-green-400"></i>
                </div>
            </div>
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Cancelled</p>
                        <p class="text-2xl font-bold text-red-600"><?= $stats['cancelled'] ?></p>
                    </div>
                    <i class="fas fa-times-circle text-3xl text-red-400"></i>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-container">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input type="text" id="searchInput" placeholder="Request Code, Name, Department..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select id="filterStatus" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Service Type</label>
                    <select id="filterService" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Services</option>
                        <?php foreach ($service_types as $service): ?>
                            <option value="<?= htmlspecialchars($service['service_code']) ?>">
                                <?= htmlspecialchars($service['service_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                    <select id="filterPriority" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Priorities</option>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
            </div>
            <div class="flex justify-between items-center">
                <button onclick="clearFilters()" class="text-sm text-blue-600 hover:text-blue-800">
                    <i class="fas fa-redo"></i> Clear Filters
                </button>
                <div id="bulkActions" style="display: none;">
                    <select id="bulkActionSelect" class="px-3 py-2 border border-gray-300 rounded-md mr-2">
                        <option value="">-- Bulk Actions --</option>
                        <option value="update_status">Update Status</option>
                        <option value="delete">Delete Selected</option>
                    </select>
                    <button onclick="executeBulkAction()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                        Apply
                    </button>
                </div>
            </div>
        </div>

        <!-- Requests Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="table-responsive">
                <table id="requestsTable">
                    <thead>
                        <tr>
                            <th style="width: 40px;">
                                <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                            </th>
                            <th>Request Code</th>
                            <th>Service</th>
                            <th>Requester</th>
                            <th>Department</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Assigned To</th>
                            <th>Created</th>
                            <th style="width: 180px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $req): ?>
                            <tr data-id="<?= $req['request_id'] ?>"
                                data-status="<?= $req['status'] ?? '' ?>"
                                data-service="<?= $req['service_code'] ?? '' ?>"
                                data-priority="<?= $req['priority'] ?? '' ?>"
                                data-search="<?= strtolower($req['request_id'] . ' ' . ($req['user_full_name'] ?? '') . ' ' . ($req['department_name'] ?? '')) ?>">
                                <td>
                                    <input type="checkbox" class="request-checkbox" value="<?= $req['request_id'] ?>">
                                </td>
                                <td class="font-mono text-sm">#<?= str_pad($req['request_id'], 4, '0', STR_PAD_LEFT) ?></td>
                                <td><?= htmlspecialchars(isset($req['service_name']) ? $req['service_name'] : getServiceName($req['service_code'] ?? 'N/A')) ?></td>
                                <td>
                                    <?= htmlspecialchars($req['user_full_name'] ?? 'N/A') ?>
                                    <br><small class="text-gray-500"><?= htmlspecialchars($req['user_email'] ?? '-') ?></small>
                                </td>
                                <td><?= htmlspecialchars($req['department_name'] ?? '-') ?></td>
                                <td>
                                    <span class="status-badge status-<?= $req['status'] ?>">
                                        <?= ucfirst(str_replace('_', ' ', $req['status'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="priority-badge priority-<?= $req['priority'] ?>">
                                        <?= ucfirst($req['priority']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($req['assigned_to']): ?>
                                        <?= htmlspecialchars($req['assigned_full_name'] ?? $req['assigned_to']) ?>
                                    <?php else: ?>
                                        <span class="text-gray-400">Unassigned</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-sm"><?= date('d/m/Y H:i', strtotime($req['created_at'])) ?></td>
                                <td>
                                    <button onclick="viewDetails(<?= $req['request_id'] ?>)" class="action-btn text-blue-600 hover:bg-blue-50" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button onclick="updateStatus(<?= $req['request_id'] ?>)" class="action-btn text-green-600 hover:bg-green-50" title="Update Status">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="assignRequest(<?= $req['request_id'] ?>)" class="action-btn text-purple-600 hover:bg-purple-50" title="Assign">
                                        <i class="fas fa-user-tag"></i>
                                    </button>
                                    <button onclick="updatePriority(<?= $req['request_id'] ?>)" class="action-btn text-orange-600 hover:bg-orange-50" title="Update Priority">
                                        <i class="fas fa-flag"></i>
                                    </button>
                                    <button onclick="deleteRequest(<?= $req['request_id'] ?>)" class="action-btn text-red-600 hover:bg-red-50" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- View Details Modal -->
    <div id="detailsModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeDetailsModal()">&times;</span>
            <h2 class="text-2xl font-bold mb-4">Request Details</h2>
            <div id="detailsContent"></div>
        </div>
    </div>

    <script>
        const users = <?= json_encode($users) ?>;

        // Service name mapping
        const serviceNames = {
            'EMAIL': 'ขอใช้บริการ Email',
            'NAS': 'ขอใช้พื้นที่ NAS',
            'IT_SUPPORT': 'ขอรับการสนับสนุนด้าน IT',
            'INTERNET': 'ขอใช้บริการ Internet',
            'QR_CODE': 'ขอทำ QR Code',
            'PHOTOGRAPHY': 'ขอถ่ายภาพกิจกรรม',
            'WEB_DESIGN': 'ขอออกแบบเว็บไซต์',
            'PRINTER': 'ขอใช้เครื่องพิมพ์'
        };

        function getServiceName(code) {
            return serviceNames[code] || code;
        }

        // Filter functionality
        function applyFilters() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const statusFilter = document.getElementById('filterStatus').value;
            const serviceFilter = document.getElementById('filterService').value;
            const priorityFilter = document.getElementById('filterPriority').value;

            const rows = document.querySelectorAll('#requestsTable tbody tr');

            rows.forEach(row => {
                const searchData = row.getAttribute('data-search');
                const status = row.getAttribute('data-status');
                const service = row.getAttribute('data-service');
                const priority = row.getAttribute('data-priority');

                const matchSearch = searchData.includes(searchTerm);
                const matchStatus = !statusFilter || status === statusFilter;
                const matchService = !serviceFilter || service === serviceFilter;
                const matchPriority = !priorityFilter || priority === priorityFilter;

                if (matchSearch && matchStatus && matchService && matchPriority) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function clearFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('filterStatus').value = '';
            document.getElementById('filterService').value = '';
            document.getElementById('filterPriority').value = '';
            applyFilters();
        }

        // Attach filter listeners
        document.getElementById('searchInput').addEventListener('input', applyFilters);
        document.getElementById('filterStatus').addEventListener('change', applyFilters);
        document.getElementById('filterService').addEventListener('change', applyFilters);
        document.getElementById('filterPriority').addEventListener('change', applyFilters);

        // Checkbox selection
        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.request-checkbox');
            checkboxes.forEach(cb => {
                if (cb.closest('tr').style.display !== 'none') {
                    cb.checked = selectAll.checked;
                }
            });
            updateBulkActionsVisibility();
        }

        function updateBulkActionsVisibility() {
            const checkedBoxes = document.querySelectorAll('.request-checkbox:checked');
            document.getElementById('bulkActions').style.display = checkedBoxes.length > 0 ? 'block' : 'none';
        }

        document.querySelectorAll('.request-checkbox').forEach(cb => {
            cb.addEventListener('change', updateBulkActionsVisibility);
        });

        // View Details
        async function viewDetails(id) {
            try {
                const response = await fetch(`../get_request_status.php?request_id=${id}`);
                const data = await response.json();

                if (data.success) {
                    const req = data.request;
                    let html = `
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm text-gray-500">Request Code</p>
                                    <p class="font-bold">#${String(req.request_id).padStart(4, '0')}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Service</p>
                                    <p class="font-bold">${req.service_name || getServiceName(req.service_code)}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Status</p>
                                    <p><span class="status-badge status-${req.status}">${req.status.replace('_', ' ')}</span></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Priority</p>
                                    <p><span class="priority-badge priority-${req.priority}">${req.priority}</span></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Requester</p>
                                    <p>${req.user_full_name}</p>
                                    <p class="text-sm text-gray-600">${req.user_email}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Department</p>
                                    <p>${req.department_name || '-'}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Created At</p>
                                    <p>${new Date(req.created_at).toLocaleString('th-TH')}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Updated At</p>
                                    <p>${new Date(req.updated_at).toLocaleString('th-TH')}</p>
                                </div>
                            </div>

                            ${req.description ? `
                            <div>
                                <p class="text-sm text-gray-500">Description</p>
                                <p class="mt-1">${req.description}</p>
                            </div>
                            ` : ''}

                            ${req.admin_notes ? `
                            <div>
                                <p class="text-sm text-gray-500">Admin Notes</p>
                                <p class="mt-1 bg-yellow-50 p-3 rounded">${req.admin_notes}</p>
                            </div>
                            ` : ''}

                            ${req.completed_date ? `
                            <div>
                                <p class="text-sm text-gray-500">Completed Date</p>
                                <p>${new Date(req.completed_date).toLocaleString('th-TH')}</p>
                            </div>
                            ` : ''}
                        </div>
                    `;

                    document.getElementById('detailsContent').innerHTML = html;
                    document.getElementById('detailsModal').style.display = 'block';
                } else {
                    Swal.fire('Error', 'Failed to load request details', 'error');
                }
            } catch (error) {
                Swal.fire('Error', 'Failed to load request details', 'error');
            }
        }

        function closeDetailsModal() {
            document.getElementById('detailsModal').style.display = 'none';
        }

        // Update Status
        async function updateStatus(id) {
            const { value: formValues } = await Swal.fire({
                title: 'Update Request Status',
                html:
                    '<select id="swal-status" class="swal2-input">' +
                    '<option value="pending">Pending</option>' +
                    '<option value="in_progress">In Progress</option>' +
                    '<option value="completed">Completed</option>' +
                    '<option value="cancelled">Cancelled</option>' +
                    '</select>' +
                    '<textarea id="swal-notes" class="swal2-textarea" placeholder="Admin Notes (Optional)"></textarea>',
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: 'Update',
                preConfirm: () => {
                    return {
                        status: document.getElementById('swal-status').value,
                        admin_notes: document.getElementById('swal-notes').value
                    }
                }
            });

            if (formValues) {
                try {
                    const formData = new FormData();
                    formData.append('action', 'update_status');
                    formData.append('id', id);
                    formData.append('status', formValues.status);
                    formData.append('admin_notes', formValues.admin_notes);

                    const response = await fetch('api/service_requests_api.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        Swal.fire('Success', result.message, 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', result.message, 'error');
                    }
                } catch (error) {
                    Swal.fire('Error', 'Failed to update status', 'error');
                }
            }
        }

        // Assign Request
        async function assignRequest(id) {
            const usersOptions = users.map(u =>
                `<option value="${u.user_id}">${u.first_name} ${u.last_name}</option>`
            ).join('');

            const { value: userId } = await Swal.fire({
                title: 'Assign Request',
                html:
                    '<select id="swal-user" class="swal2-input">' +
                    '<option value="">-- Select User --</option>' +
                    usersOptions +
                    '</select>',
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: 'Assign',
                preConfirm: () => {
                    return document.getElementById('swal-user').value;
                }
            });

            if (userId !== undefined) {
                try {
                    const formData = new FormData();
                    formData.append('action', 'assign');
                    formData.append('id', id);
                    formData.append('assigned_to', userId);

                    const response = await fetch('api/service_requests_api.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        Swal.fire('Success', result.message, 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', result.message, 'error');
                    }
                } catch (error) {
                    Swal.fire('Error', 'Failed to assign request', 'error');
                }
            }
        }

        // Update Priority
        async function updatePriority(id) {
            const { value: priority } = await Swal.fire({
                title: 'Update Priority',
                input: 'select',
                inputOptions: {
                    'low': 'Low',
                    'medium': 'Medium',
                    'high': 'High',
                    'urgent': 'Urgent'
                },
                inputPlaceholder: 'Select priority',
                showCancelButton: true,
                confirmButtonText: 'Update'
            });

            if (priority) {
                try {
                    const formData = new FormData();
                    formData.append('action', 'update_priority');
                    formData.append('id', id);
                    formData.append('priority', priority);

                    const response = await fetch('api/service_requests_api.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        Swal.fire('Success', result.message, 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', result.message, 'error');
                    }
                } catch (error) {
                    Swal.fire('Error', 'Failed to update priority', 'error');
                }
            }
        }

        // Delete Request
        async function deleteRequest(id) {
            const result = await Swal.fire({
                title: 'Are you sure?',
                text: "This will permanently delete the request and all related data!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            });

            if (result.isConfirmed) {
                try {
                    const formData = new FormData();
                    formData.append('action', 'delete');
                    formData.append('id', id);

                    const response = await fetch('api/service_requests_api.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        Swal.fire('Deleted!', data.message, 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                } catch (error) {
                    Swal.fire('Error', 'Failed to delete request', 'error');
                }
            }
        }

        // Bulk Actions
        async function executeBulkAction() {
            const action = document.getElementById('bulkActionSelect').value;
            const checkedBoxes = document.querySelectorAll('.request-checkbox:checked');
            const ids = Array.from(checkedBoxes).map(cb => cb.value);

            if (!action) {
                Swal.fire('Error', 'Please select an action', 'error');
                return;
            }

            if (ids.length === 0) {
                Swal.fire('Error', 'Please select at least one request', 'error');
                return;
            }

            if (action === 'update_status') {
                const { value: status } = await Swal.fire({
                    title: `Update Status for ${ids.length} requests`,
                    input: 'select',
                    inputOptions: {
                        'pending': 'Pending',
                        'in_progress': 'In Progress',
                        'completed': 'Completed',
                        'cancelled': 'Cancelled'
                    },
                    inputPlaceholder: 'Select status',
                    showCancelButton: true,
                    confirmButtonText: 'Update All'
                });

                if (status) {
                    try {
                        const formData = new FormData();
                        formData.append('action', 'bulk_update_status');
                        formData.append('ids', JSON.stringify(ids));
                        formData.append('status', status);

                        const response = await fetch('api/service_requests_api.php', {
                            method: 'POST',
                            body: formData
                        });

                        const result = await response.json();

                        if (result.success) {
                            Swal.fire('Success', result.message, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', result.message, 'error');
                        }
                    } catch (error) {
                        Swal.fire('Error', 'Failed to update status', 'error');
                    }
                }
            } else if (action === 'delete') {
                const result = await Swal.fire({
                    title: 'Are you sure?',
                    text: `This will permanently delete ${ids.length} requests and all related data!`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete them!'
                });

                if (result.isConfirmed) {
                    try {
                        let successCount = 0;
                        let errorCount = 0;

                        for (const id of ids) {
                            const formData = new FormData();
                            formData.append('action', 'delete');
                            formData.append('id', id);

                            const response = await fetch('api/service_requests_api.php', {
                                method: 'POST',
                                body: formData
                            });

                            const data = await response.json();
                            if (data.success) {
                                successCount++;
                            } else {
                                errorCount++;
                            }
                        }

                        if (errorCount === 0) {
                            Swal.fire('Success', `Successfully deleted ${successCount} requests`, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Partial Success', `Deleted ${successCount} requests, ${errorCount} failed`, 'warning').then(() => {
                                location.reload();
                            });
                        }
                    } catch (error) {
                        Swal.fire('Error', 'Failed to delete requests', 'error');
                    }
                }
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('detailsModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>
