<?php
require_once 'db_connect.php';

if (!isset($_GET['id'])) {
    header("Location: index.php?status=error");
    exit();
}

$id = $_GET['id'];

// First verify the record exists
$stmt = $conn->prepare("SELECT update_id FROM rsk_sirs_bed_updates WHERE update_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php?status=error&message=Record not found");
    exit();
}

// Delete the record
$stmt = $conn->prepare("DELETE FROM rsk_sirs_bed_updates WHERE update_id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: index.php?status=deleted");
} else {
    header("Location: index.php?status=error&message=" . urlencode($stmt->error));
}
?>