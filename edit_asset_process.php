<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    die("Unauthorized Access");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $equipId        = $_POST['equipId'];
    $resourceName   = trim($_POST['resourceName']);
    $categoryName   = trim($_POST['category']);
    $status         = trim($_POST['status']);
    $departmentName = trim($_POST['department']);

    try {
        // Resolve category name → category_id (insert if not exists)
        $stmtCat = $pdo->prepare("SELECT category_id FROM categories WHERE category_name = ?");
        $stmtCat->execute([$categoryName]);
        $catRow = $stmtCat->fetch();
        if ($catRow) {
            $categoryId = $catRow['category_id'];
        } else {
            $pdo->prepare("INSERT INTO categories (category_name) VALUES (?)")->execute([$categoryName]);
            $categoryId = $pdo->lastInsertId();
        }

        // Resolve department name → department_id (insert if not exists)
        $stmtDept = $pdo->prepare("SELECT department_id FROM departments WHERE department_name = ?");
        $stmtDept->execute([$departmentName]);
        $deptRow = $stmtDept->fetch();
        if ($deptRow) {
            $departmentId = $deptRow['department_id'];
        } else {
            $pdo->prepare("INSERT INTO departments (department_name) VALUES (?)")->execute([$departmentName]);
            $departmentId = $pdo->lastInsertId();
        }

        $stmt = $pdo->prepare("UPDATE assets SET resource_name = ?, category_id = ?, status = ?, department_id = ? WHERE equipment_id = ?");
        $stmt->execute([$resourceName, $categoryId, $status, $departmentId, $equipId]);
        header("Location: assets.php");
        exit;
    } catch(PDOException $e) {
        die("Error updating asset: " . $e->getMessage());
    }
}
?>