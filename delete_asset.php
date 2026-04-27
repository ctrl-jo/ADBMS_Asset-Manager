<?php
session_start();
require 'db.php';

// Ensure only admins can delete
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    die("Unauthorized Access");
}

if (isset($_GET['id'])) {
    $equipId = $_GET['id'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM assets WHERE equipment_id = ?");
        $stmt->execute([$equipId]);
    } catch(PDOException $e) {
        die("Error deleting asset: " . $e->getMessage());
    }
}

// Send them right back to the asset page
header("Location: assets.php");
exit;
?>