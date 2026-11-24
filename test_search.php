<?php
$url = 'http://localhost/Events/view_bookings.php?search=Alice';
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$resp = curl_exec($ch);
curl_close($ch);

// Check if Alice Brown appears
if (strpos($resp, 'Alice Brown') !== false) {
    echo "SEARCH WORKS: Alice Brown found in results\n";
} else {
    echo "SEARCH FAILED: Alice Brown not found\n";
}
// Count booking records
preg_match('/<strong>(\d+) booking record\(s\)<\/strong>/', $resp, $m);
echo "Record count: " . ($m[1] ?? 'unknown') . "\n";
