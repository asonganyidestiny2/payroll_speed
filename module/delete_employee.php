<?php
require '../config/db.php'; // your DB connection file

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $employee_id = (int) $_GET['id'];

    try {
        // First delete related attendance records
        $deleteAttendance = $pdo->prepare("DELETE FROM attendance WHERE employee_id = ?");
        $deleteAttendance->execute([$employee_id]);

        // Then delete from employees
        $deleteEmployee = $pdo->prepare("DELETE FROM employees WHERE id = ?");
        $deleteEmployee->execute([$employee_id]);

        // Redirect or confirm
        header("Location: ../module/view_employee.php?message=Employee deleted successfully");
        exit;
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
} else {
    die("Invalid request.");
}
?>
