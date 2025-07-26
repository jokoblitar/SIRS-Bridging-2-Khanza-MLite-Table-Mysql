<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$id = "1234567"; // kode rs dari kemenkes
$pass = "S!pass25!!";

// Generate proper ISO 8601 timestamp (SIRS API requires this format)
$dt = new DateTime(null, new DateTimeZone("UTC"));
$timestamp = $dt->format('Y-m-d\TH:i:s\Z');  // Fixed timestamp format

function callSirsApi($url, $id, $timestamp, $pass) {
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => [
            "X-rs-id: " . $id,
            "X-Timestamp: " . $timestamp,
            "X-pass: " . $pass,
            "Accept: application/json"
        ],
        CURLOPT_TIMEOUT => 30
    ]);
    
    $response = curl_exec($curl);
    $error = curl_error($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    return [
        'success' => !$error && $http_code == 200,
        'data' => $response,
        'error' => $error ?: "HTTP Code $http_code"
    ];
}

// 1. Get Reference Data
$url_referensi = "https://sirs.kemkes.go.id/fo/index.php/Referensi/tempat_tidur";
$result = callSirsApi($url_referensi, $id, $timestamp, $pass);

if (!$result['success']) {
    die("Error GET Master Referensi: " . $result['error']);
}

// Process reference data
$referenceData = json_decode($result['data'], true);
if (json_last_error() !== JSON_ERROR_NONE) {
    die("Invalid JSON in reference data: " . json_last_error_msg());
}

// 2. Get Bed Data
$url_fasyankes = "https://sirs.kemkes.go.id/fo/index.php/Fasyankes";
$result = callSirsApi($url_fasyankes, $id, $timestamp, $pass);

if (!$result['success']) {
    die("Error GET Data Fasyankes: " . $result['error']);
}

// Process bed data (focusing on this part as requested)
$bedData = json_decode($result['data'], true);
if (json_last_error() !== JSON_ERROR_NONE) {
    die("Invalid JSON in bed data: " . json_last_error_msg());
}

// Display only the bed data as requested
echo "<h2>Data Tempat Tidur yang Sudah Pernah Diinputkan:</h2>";
echo "<pre>" . json_encode($bedData, JSON_PRETTY_PRINT) . "</pre>";

// If you need specific fields from bedData:
if (isset($bedData['fasyankes'])) {
    echo "<h3>Rincian Tempat Tidur:</h3>";
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Tipe TT</th><th>Ruang</th><th>Jumlah</th><th>Terpakai</th><th>COVID</th></tr>";
    
    foreach ($bedData['fasyankes'] as $bed) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($bed['id_tt'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($bed['tt'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($bed['ruang'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($bed['jumlah'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($bed['terpakai'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($bed['covid'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}
?>
