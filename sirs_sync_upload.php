<?php
require_once 'db_connect.php'; // Pastikan file koneksi database Anda sudah benar

// Set header untuk mencegah caching jika script ini diakses langsung
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header('Content-Type: text/plain'); // Mengubah Content-Type agar output lebih mudah dibaca di browser

// Kredensial API SIRS-Kemkes
$rs_id = "1234567"; // Kode RS dari Kemenkes
$rs_pass = "S!pass25!!"; // Password API

// URL Endpoint API SIRS-Kemkes untuk update tempat tidur
$url = "https://sirs.kemkes.go.id/fo/index.php/Fasyankes";
$method = "PUT"; // Metode HTTP yang digunakan

echo "Starting SIRS-Kemkes synchronization...\n\n";

try {
    // Mulai transaksi untuk memastikan atomicity (opsional, tapi baik untuk multiple updates)
    $conn->begin_transaction();

    // 1. Dapatkan semua data tempat tidur dari tabel rsk_sirs_bed_updates
    // Hanya ambil yang belum disinkronkan atau yang perlu disinkronkan ulang
    $sql_get_beds = "SELECT * FROM rsk_sirs_bed_updates ORDER BY rsk_updated_at ASC";
    $result_beds = $conn->query($sql_get_beds);

    if (!$result_beds) {
        throw new Exception("Error fetching bed updates from database: " . $conn->error);
    }

    $synced_count = 0;
    $failed_count = 0;

    // 2. Loop melalui setiap baris data dan kirim ke SIRS-Kemkes
    while ($row = $result_beds->fetch_assoc()) {
        echo "Processing update_id: " . $row['update_id'] . " (Ruang: " . $row['rsk_ruang'] . ")\n";

        // Dapatkan Timestamp UTC
        $dt = new DateTime(null, new DateTimeZone("UTC"));
        $timestamp = $dt->getTimestamp();

        // Siapkan data untuk dikirim dalam format JSON
        $postdata = json_encode([
            "id_t_tt" => $row['rsk_id_t_tt'],
            "ruang" => $row['rsk_ruang'],
            "jumlah_ruang" => (int)$row['rsk_jumlah_ruang'],
            "jumlah" => (int)$row['rsk_jumlah'],
            "terpakai" => (int)$row['simrs_terpakai'], // Menggunakan simrs_terpakai
            "terpakai_suspek" => (int)$row['rsk_terpakai_suspek'],
            "terpakai_konfirmasi" => (int)$row['rsk_terpakai_konfirmasi'],
            "antrian" => (int)$row['rsk_antrian'],
            "prepare" => (int)$row['rsk_prepare'],
            "prepare_plan" => (int)$row['rsk_prepare_plan'],
            "covid" => (int)$row['rsk_covid'],
            "terpakai_dbd" => (int)$row['rsk_terpakai_dbd'],
            "terpakai_dbd_anak" => (int)$row['rsk_terpakai_dbd_anak'],
            "jumlah_dbd" => (int)$row['rsk_jumlah_dbd']
        ]);

        // Inisialisasi cURL
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); // Untuk pengembangan, jangan gunakan di produksi tanpa verifikasi SSL
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // Untuk pengembangan, jangan gunakan di produksi tanpa verifikasi SSL
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            "X-rs-id: " . $rs_id,
            "X-Timestamp: " . $timestamp,
            "X-pass: " . $rs_pass,
            "Content-type: application/json",
            "Content-Length: " . strlen($postdata) // Penting untuk PUT/POST
        ]);

        $result = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE); // Dapatkan kode status HTTP

        if (curl_errno($curl)) {
            $error_msg = curl_error($curl);
            echo "cURL Error for update_id " . $row['update_id'] . ": " . $error_msg . "\n";
            error_log("SIRS Sync cURL Error for update_id " . $row['update_id'] . ": " . $error_msg);
            $sync_status = 0; // Gagal
            $failed_count++;
        } else {
            echo "SIRS API Response for update_id " . $row['update_id'] . " (HTTP " . $http_code . "): " . $result . "\n";
            
            $response_data = json_decode($result, true);
            // KOREKSI: Pengecekan status API SIRS-Kemkes
            // Memastikan 'fasyankes' ada, itu adalah array, memiliki setidaknya satu elemen, dan elemen pertama memiliki 'status' == '200'
            if ($http_code == 200 && 
                isset($response_data['fasyankes']) && 
                is_array($response_data['fasyankes']) && 
                !empty($response_data['fasyankes']) &&
                isset($response_data['fasyankes'][0]['status']) && 
                $response_data['fasyankes'][0]['status'] == '200') {
                $sync_status = 1; // Sukses
                $synced_count++;
            } else {
                $sync_status = 0; // Gagal
                $failed_count++;
                error_log("SIRS Sync API Response Error for update_id " . $row['update_id'] . " (HTTP " . $http_code . "): " . $result);
            }
        }
        curl_close($curl);

        // Update status sinkronisasi di database lokal
        $sql_update_sync_status = "
            UPDATE rsk_sirs_bed_updates
            SET
                rsk_is_synced = ?,
                rsk_last_sync_at = CURRENT_TIMESTAMP
            WHERE
                update_id = ?;
        ";
        $stmt_update_sync = $conn->prepare($sql_update_sync_status);
        if (!$stmt_update_sync) {
            error_log("Error preparing sync status update statement: " . $conn->error);
        } else {
            $stmt_update_sync->bind_param("ii", $sync_status, $row['update_id']);
            if (!$stmt_update_sync->execute()) {
                error_log("Failed to update sync status for update_id " . $row['update_id'] . ": " . $stmt_update_sync->error);
            }
            $stmt_update_sync->close();
        }
        echo "--------------------------------------------------\n";
    }

    // Commit transaksi jika semua berhasil
    $conn->commit();

    echo "\nSIRS-Kemkes synchronization finished.\n";
    echo "Successfully synced: " . $synced_count . " records.\n";
    echo "Failed to sync: " . $failed_count . " records.\n";

    // Redirect kembali ke halaman utama dengan status sukses
    header("Location: index.php?status=sirs_synced&synced_count=" . $synced_count . "&failed_count=" . $failed_count);
    exit();

} catch (Exception $e) {
    // Rollback transaksi jika terjadi error
    $conn->rollback();
    echo "\nError during SIRS-Kemkes synchronization: " . $e->getMessage() . "\n";
    error_log("Error during SIRS-Kemkes synchronization: " . $e->getMessage());
    // Redirect kembali ke halaman utama dengan status error
    header("Location: index.php?status=error&message=" . urlencode("SIRS Sync Failed: " . $e->getMessage()));
    exit();
} finally {
    // Pastikan koneksi ditutup
    if ($conn) {
        $conn->close();
    }
}
?>
