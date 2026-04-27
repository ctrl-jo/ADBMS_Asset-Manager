<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit;
}

$stmt = $pdo->query("
    SELECT
        a.equipment_id,
        a.resource_name,
        a.status,
        a.last_updated,
        c.category_name
    FROM assets a
    LEFT JOIN categories c ON a.category_id = c.category_id
    ORDER BY a.last_updated DESC
");
$assets = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Asset Management</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="page-container">
        <header class="app-header">
            <div class="header-left">
                <h1>Asset Management System</h1>
                <nav class="main-nav">
                    <a href="admin-dashboard.php" class="nav-item">Dashboard</a>
                    <a href="assets.php" class="nav-item active">Asset Management</a>
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
            <div class="content-header">
                <div>
                    <h2>Asset Management</h2>
                    <p class="section-description">Manage equipment lifecycle and status transitions</p>
                </div>
                <button class="add-asset-btn">+ Add Asset</button>
            </div>

            <div class="table-container">
                <div class="controls-bar">
                    <div class="search-box">
                        <span class="search-icon">🔍</span>
                        <input type="text" placeholder="Search assets...">
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
                            <th>ID</th>
                            <th>NAME</th>
                            <th>CATEGORY</th>
                            <th>STATUS</th>
                            <th>DEPARTMENT</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($assets as $asset): ?>
                            <tr>
                                <td><?= htmlspecialchars($asset['equipment_id']) ?></td>
                                <td><?= htmlspecialchars($asset['resource_name']) ?></td>
                                <td><?= htmlspecialchars($asset['category_name'] ?? '-') ?></td>
                                <td>
                                    <?php $class = strtolower(str_replace(' ', '-', $asset['status'])); ?>
                                    <span class="status-badge <?= $class ?>"><?= $asset['status'] ?></span>
                                </td>
                                
                                <td><?= htmlspecialchars($asset['department_name'] ?? '-') ?></td>
                                
                                <td class="action-cell">
                                    <button class="action-btn edit-btn" 
                                        data-id="<?= htmlspecialchars($asset['equipment_id']) ?>"
                                        data-name="<?= htmlspecialchars($asset['resource_name']) ?>"
                                        data-category="<?= htmlspecialchars($asset['category_name'] ?? '') ?>"
                                        data-status="<?= htmlspecialchars($asset['status']) ?>"
                                        data-department="<?= htmlspecialchars($asset['department_name'] ?? '') ?>">
                                        ✏️
                                    </button>
                                    
                                    <a href="delete_asset.php?id=<?= urlencode($asset['equipment_id']) ?>" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to delete this asset? This cannot be undone.');">
                                       🗑️
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <div class="modal-overlay" id="addAssetModal">
        <div class="modal-content">
            <h3 class="modal-title">Add New Asset</h3>
            <form method="POST" action="add_asset_process.php">
                <div class="form-group">
                    <label>Equipment ID</label>
                    <input type="text" name="equipId" required>
                </div>
                <div class="form-group">
                    <label>Resource Name</label>
                    <input type="text" name="resourceName" required>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category" required>
                        <option value="IT Equipment">IT Equipment</option>
                        <option value="Laboratory">Laboratory</option>
                        <option value="Classroom">Classroom</option>
                        <option value="Office">Office</option>
                        <option value="Audio-Visual">Audio-Visual</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Department</label>
                    <input type="text" name="department" required>
                </div>
                <div class="modal-actions">
                    <button type="submit" class="btn-primary">Save Asset</button>
                    <button type="button" class="btn-secondary" id="cancelAddBtn">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="editAssetModal">
        <div class="modal-content">
            <h3 class="modal-title">Edit Asset Status</h3>
            <form method="POST" action="edit_asset_process.php">
                <div class="form-group">
                    <label>Equipment ID</label>
                    <input type="text" name="equipId" id="editEquipId" readonly style="background-color: #f1f5f9;">
                </div>
                <div class="form-group">
                    <label>Resource Name</label>
                    <input type="text" name="resourceName" id="editResourceName" required>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category" id="editCategory" required>
                        <option value="IT Equipment">IT Equipment</option>
                        <option value="Laboratory">Laboratory</option>
                        <option value="Classroom">Classroom</option>
                        <option value="Office">Office</option>
                        <option value="Audio-Visual">Audio-Visual</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="editStatus" required>
                        <option value="Available">Available</option>
                        <option value="Reserved">Reserved</option>
                        <option value="In-Use">In-Use</option>
                        <option value="Under Maintenance">Under Maintenance</option>
                    </select>
                </div>
               <div class="form-group">
                <label>Department</label>
                <input type="text" name="department" id="editDepartment" required>
            </div>
                <div class="modal-actions">
                    <button type="submit" class="btn-primary">Update Asset</button>
                    <button type="button" class="btn-secondary" id="closeEditBtn">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>