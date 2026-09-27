<?php
include("../../db.php");
header('Content-Type: application/json');

if (isset($_GET['identifier'])) {
    $identifier = trim($_GET['identifier']);
    
    // Search by ID number OR email
    $stmt = $conn->prepare("SELECT id, username, email, id_number, is_suspended, suspension_end_date 
                            FROM users 
                            WHERE (id_number = ? OR email = ?) AND role = 'student' 
                            LIMIT 1");
    $stmt->bind_param("ss", $identifier, $identifier);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($student = $result->fetch_assoc()) {
        // Get borrowed count
        $borrowStmt = $conn->prepare("SELECT COUNT(*) FROM borrow_records WHERE user_id=? AND status='borrowed'");
        $borrowStmt->bind_param("i", $student['id']);
        $borrowStmt->execute();
        $borrowStmt->bind_result($borrowed_count);
        $borrowStmt->fetch();
        $borrowStmt->close();
        
        echo json_encode([
            'exists' => true,
            'id' => $student['id'],
            'username' => $student['username'],
            'email' => $student['email'],
            'id_number' => $student['id_number'],
            'is_suspended' => (bool)$student['is_suspended'],
            'suspension_end_date' => $student['suspension_end_date'],
            'borrowed_count' => $borrowed_count
        ]);
    } else {
        echo json_encode(['exists' => false]);
    }
    $stmt->close();
} else {
    echo json_encode(['exists' => false]);
}
?>