<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit;
}

$stmtTotal = $pdo->query("SELECT COUNT(*) FROM assets");
$totalEq = $stmtTotal->fetchColumn();

$stmtInUse = $pdo->query("SELECT COUNT(*) FROM assets WHERE status = 'In-Use'");
$inUse = $stmtInUse->fetchColumn();

$stmtAvail = $pdo->query("SELECT COUNT(*) FROM assets WHERE status = 'Available'");
$avail = $stmtAvail->fetchColumn();

$stmtMaint = $pdo->query("SELECT COUNT(*) FROM assets WHERE status = 'Under Maintenance'");
$maint = $stmtMaint->fetchColumn();

$stmtActive = $pdo->query("
    SELECT 
        a.equipment_id,
        a.resource_name,
        a.status,
        a.last_updated,
        c.category_name,
        l.location_name,
        u.full_name  AS assigned_user,
        d.department_name AS assigned_department
    FROM assets a
    LEFT JOIN categories c ON a.category_id = c.category_id
    LEFT JOIN locations  l ON a.location_id  = l.location_id
    LEFT JOIN users      u ON a.assigned_to  = u.id
    LEFT JOIN departments d ON u.department_id = d.department_id
    WHERE a.status IN ('In-Use', 'Reserved')
    ORDER BY a.last_updated DESC
");
$activeAssets = $stmtActive->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="page-container">
        <header class="app-header">
            <div class="header-left">
                <h1>Asset Management System</h1>
                <nav class="main-nav">
                    <a href="admin-dashboard.php" class="nav-item active">Dashboard</a>
                    <a href="assets.php" class="nav-item">Asset Management</a>
                </nav>
            </div>
            <div class="header-right">
                <div class="user-info">
                    <span class="user-name"><?= htmlspecialchars($_SESSION['full_name']) ?></span>
                    <span class="user-role"><?= htmlspecialchars($_SESSION['role']) ?></span>
                </div>
                <a href="logout.php" class="logout-btn">↪ Logout</a>
            </div>
        </header>

        <main class="main-content">
            <div class="dashboard-top">
                <div>
                    <h2>Admin Dashboard</h2>
                    <p class="section-description">Real-time overview of asset management</p>
                </div>
                <span class="last-updated" id="liveClock">↗ Last updated:</span>
            </div>

            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-icon blue">📦</div>
                    <h3><?= $totalEq ?></h3><p>Total Equipment</p>
                </div>
                <div class="metric-card">
                    <div class="metric-icon green">🕒</div>
                    <h3><?= $inUse ?></h3><p>Currently In-Use</p>
                </div>
                <div class="metric-card">
                    <div class="metric-icon purple">✔️</div>
                    <h3><?= $avail ?></h3><p>Available Resources</p>
                </div>
                <div class="metric-card">
                    <div class="metric-icon orange">🔧</div>
                    <h3><?= $maint ?></h3><p>Under Maintenance</p>
                </div>
            </div>

            <div class="table-container">
                <div class="table-header-text">
                    <h3>Active Items (In-Use &amp; Reserved)</h3>
                </div>
                <table class="asset-table">
                    <thead>
                        <tr>
                            <th>EQUIPMENT ID</th>
                            <th>RESOURCE NAME</th>
                            <th>CATEGORY</th>
                            <th>STATUS</th>
                            <th>ASSIGNED TO</th>
                            <th>DEPARTMENT</th>
                            <th>LOCATION</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($activeAssets as $asset): ?>
                            <tr>
                                <td><?= htmlspecialchars($asset['equipment_id']) ?></td>
                                <td><?= htmlspecialchars($asset['resource_name']) ?></td>
                                <td><?= htmlspecialchars($asset['category_name'] ?? '-') ?></td>
                                <td>
                                    <?php $class = strtolower(str_replace(' ', '-', $asset['status'])); ?>
                                    <span class="status-badge <?= $class ?>"><?= $asset['status'] ?></span>
                                </td>
                                <td><?= htmlspecialchars($asset['assigned_user'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($asset['assigned_department'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($asset['location_name'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>