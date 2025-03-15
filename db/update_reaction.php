<?php
include '../db/db_conn.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $reaction = $_POST['reaction'];
    $criterion = $_POST['criterion'];

    // Log the reaction and criterion values to ensure they're correct
    error_log("Reaction: " . $reaction);
    error_log("Criterion: " . $criterion);

    // Update reaction count
    $updateQuery = "UPDATE reactions SET {$reaction}_count = {$reaction}_count + 1 WHERE criterion = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("s", $criterion);

    if ($stmt->execute()) {
        // Fetch the updated count
        $selectQuery = "SELECT {$reaction}_count FROM reactions WHERE criterion = ?";
        $stmt = $conn->prepare($selectQuery);
        $stmt->bind_param("s", $criterion);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row && isset($row["{$reaction}_count"])) {
            echo json_encode(['success' => true, 'count' => $row["{$reaction}_count"]]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No reaction data found.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update reaction.']);
    }
}
?>
