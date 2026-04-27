<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Staff') {
    header("Location: login.php");
    exit;
}

$userId   = $_SESSION['user_id'];
$userName = $_SESSION['full_name'];

// Fetch ONLY Reserved or In-Use items assigned to this faculty member (by user ID)
$stmtMyItems = $pdo->prepare("
    SELECT
        a.equipment_id,
        a.resource_name,
        a.status,
        c.category_name,
        l.location_name,
        d.department_name
    FROM assets a
    LEFT JOIN categories  c ON a.category_id  = c.category_id
    LEFT JOIN locations   l ON a.location_id   = l.location_id
    LEFT JOIN users       u ON a.assigned_to   = u.id
    LEFT JOIN departments d ON u.department_id = d.department_id
    WHERE a.assigned_to = ? AND a.status IN ('Reserved', 'In-Use')
");
$stmtMyItems->execute([$userId]);
$myItems = $stmtMyItems->fetchAll();
$myReservationsCount = count($myItems);

// Fetch all available items (with category via JOIN)
$stmtAvail = $pdo->query("
    SELECT
        a.equipment_id,
        a.resource_name,
        a.status,
        c.category_name
    FROM assets a
    LEFT JOIN categories c ON a.category_id = c.category_id
    WHERE a.status = 'Available'
");
$availItems = $stmtAvail->fetchAll();
$availableCount = count($availItems);

// Fetch total equipment
$stmtTotal = $pdo->query("SELECT COUNT(*) FROM assets");
$totalEquipment = $stmtTotal->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Faculty Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="page-container">
        <header class="app-header">
            <div class="header-left">
                <h1>Asset Management System</h1>
            </div>
            <div class="header-right">
                <div class="user-info">
                    <span class="user-name"><?= htmlspecialchars($userName) ?></span>
                    <span class="user-role"><?= htmlspecialchars($_SESSION['department']) ?></span>
                </div>
                <a href="logout.php" class="logout-btn">↪ Logout</a>
            </div>
        </header>

        <main class="main-content">
            <div class="dashboard-top">
                <div>
                    <h2>Faculty Dashboard</h2>
                    <p class="section-description">Welcome back, <?= htmlspecialchars($userName) ?></p>
                </div>
            </div>

            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-icon green">✔️</div>
                    <h3><?= $availableCount ?></h3>
                    <p>Available Equipment</p>
                </div>
                <div class="metric-card">
                    <div class="metric-icon purple">📅</div>
                    <h3><?= $myReservationsCount ?></h3>
                    <p>My Reservations</p>
                </div>
                <div class="metric-card">
                    <div class="metric-icon blue">📦</div>
                    <h3><?= $totalEquipment ?></h3>
                    <p>Total Equipment</p>
                </div>
            </div>

            <div class="table-container" style="margin-bottom: 2rem;">
                <div class="table-header-text"><h3>My Active Items</h3></div>
                <table class="asset-table">
                    <thead>
                        <tr>
                            <th>EQUIPMENT ID</th>
                            <th>NAME</th>
                            <th>CATEGORY</th>
                            <th>STATUS</th>
                            <th>DEPARTMENT</th>
                            <th>LOCATION</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($myReservationsCount > 0): ?>
                            <?php foreach($myItems as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['equipment_id']) ?></td>
                                    <td><?= htmlspecialchars($item['resource_name']) ?></td>
                                    <td><?= htmlspecialchars($item['category_name'] ?? '-') ?></td>
                                    <td>
                                        <?php $class = strtolower(str_replace(' ', '-', $item['status'])); ?>
                                        <span class="status-badge <?= $class ?>"><?= $item['status'] ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($item['department_name'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($item['location_name'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align: center; color: #64748b; padding: 2rem;">You currently have no active items.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="table-container">
                <div class="table-header-text" style="margin-bottom: 1rem;">
                    <h3>Available Equipment</h3>
                    <p class="section-description">Browse and reserve available resources</p>
                </div>
                
                <div class="controls-bar">
                    <div class="search-box">
                        <span class="search-icon">🔍</span>
                        <input type="text" placeholder="Search equipment...">
                    </div>
                    <div class="category-filters">
                        <button class="filter-btn active">All</button>
                        <button class="filter-btn">IT Equipment</button>
                        <button class="filter-btn">Laboratory</button>
                        <button class="filter-btn">Classroom</button>
                        <button class="filter-btn">Office</button>
                        <button class="filter-btn">Audio-Visual</button>
                    </div>
                </div>

                <table class="asset-table">
                    <thead>
                        <tr>
                            <th>EQUIPMENT ID</th>
                            <th>NAME</th>
                            <th>CATEGORY</th>
                            <th>DEPARTMENT</th>
                            <th>ACTION</th> 
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($availableCount > 0): ?>
                            <?php foreach($availItems as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['equipment_id']) ?></td>
                                    <td><?= htmlspecialchars($item['resource_name']) ?></td>
                                    <td><?= htmlspecialchars($item['category_name'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($item['department_name'] ?? '-') ?></td>
                                    <td>
                                        <a href="#" 
                                           class="btn-primary" 
                                           style="padding: 0.5rem 1rem; text-decoration: none; font-size: 0.75rem; border-radius: 0.375rem; display: inline-block;" 
                                           onclick="event.preventDefault(); let loc = prompt('Please enter the location where you will use the <?= htmlspecialchars($item['resource_name']) ?>:'); if(loc && loc.trim() !== '') { window.location.href = 'reserve_asset.php?id=<?= urlencode($item['equipment_id']) ?>&location=' + encodeURIComponent(loc.trim()); }">
                                           Reserve
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align: center; color: #64748b; padding: 2rem;">No equipment is currently available.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    <script src="script.js"></script>
</body>
</html>