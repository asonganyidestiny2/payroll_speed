<?php
header('Content-Type: application/json');
require_once '../config/db.php';

$data = [
    'labels' => [],
    'datasets' => [
        [
            'label' => 'Payroll Paid (FCFA)',
            'backgroundColor' => '#3498db',
            'data' => []
        ]
    ]
];

try {
    // Query to get the total net salary paid per month
    // i use DATE_FORMAT to get the month name and SUM to calculate total for that month
    $stmt = $pdo->query("SELECT DATE_FORMAT(payment_date, '%b') as pay_month, SUM(net_salary) as total_paid
                         FROM payroll
                         WHERE payment_status = 'paid'
                         GROUP BY pay_month
                         ORDER BY MIN(payment_date)");

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Map the results to the data structure needed by Chart.js
    $labels = [];
    $amounts = [];

    // Initialize an array for all months of the year
    $allMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    $monthlyData = array_fill_keys($allMonths, 0);

    // Populate the data with fetched results
    foreach ($results as $row) {
        $monthlyData[$row['pay_month']] = (float)$row['total_paid'];
    }

    $data['labels'] = array_keys($monthlyData);
    $data['datasets'][0]['data'] = array_values($monthlyData);

    echo json_encode($data);

} catch (PDOException $e) {
    // Return an error JSON response
    echo json_encode(['error' => 'Database Error: ' . $e->getMessage()]);
}

?>