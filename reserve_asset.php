<?php
session_start();
require 'db.php';

// Ensure only logged-in Staff/Faculty can reserve items
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Staff') {
    die("Unauthorized Access");
}

// Check if BOTH the ID and the location parameter were sent
if (isset($_GET['id']) && isset($_GET['location'])) {
    $equipId      = $_GET['id'];
    $locationName = trim($_GET['location']);
    $userId       = $_SESSION['user_id'];

    try {
        // Resolve location name → location_id (insert if not exists)
        $stmtLoc = $pdo->prepare("SELECT location_id FROM locations WHERE location_name = ?");
        $stmtLoc->execute([$locationName]);
        $locRow = $stmtLoc->fetch();
        if ($locRow) {
            $locationId = $locRow['location_id'];
        } else {
            $pdo->prepare("INSERT INTO locations (location_name) VALUES (?)")->execute([$locationName]);
            $locationId = $pdo->lastInsertId();
        }

        // Update the asset: assign to the user (by ID), change status, and update location_id
        $stmt = $pdo->prepare("UPDATE assets SET status = 'Reserved', assigned_to = ?, location_id = ? WHERE equipment_id = ? AND status = 'Available'");
        $stmt->execute([$userId, $locationId, $equipId]);

    } catch(PDOException $e) {
        die("Error reserving asset: " . $e->getMessage());
    }
}

// Send them back to their dashboard
header("Location: faculty-dashboard.php");
exit;
?>