<?php

// Database connection parameters
$dbHost = 'localhost';
$dbName = 'sik';
$dbUser = 'user';
$dbPass = 'pass';

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "Koneksi database berhasil!<br>"; // Untuk debugging
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

$id = "1234567"; // Kode RS dari Kemenkes
$pass = "S!pass25!!";  // ganti dengan pass SIRS

// Ambil Timestamp (UTC)
$dt = new DateTime(null, new DateTimeZone("UTC"));
$timestamp = $dt->getTimestamp();

// ---
## GET Master Referensi Data Tempat Tidur
// ---
// (Tidak digunakan langsung untuk update, tapi disimpan untuk konteks)
$url_referensi = "https://sirs.kemkes.go.id/fo/index.php/Referensi/tempat_tidur";
$curl_referensi = curl_init();
curl_setopt($curl_referensi, CURLOPT_URL, $url_referensi);
curl_setopt($curl_referensi, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl_referensi, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($curl_referensi, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($curl_referensi, CURLOPT_HTTPHEADER, array("X-rs-id: ".$id,"X-Timestamp: ".$timestamp,"X-pass: ".$pass));
$result_referensi = curl_exec($curl_referensi);

if ($result_referensi === false) {
    echo "<b>Error GET Master Referensi:</b><br>";
    echo curl_error($curl_referensi);
} else {
    echo "<b>Master Referensi Data Tempat Tidur:</b><br>";
    echo $result_referensi;
}
curl_close($curl_referensi);

echo "<br><br>";

// ---
## GET Data Tempat Tidur yang Sudah Pernah Diinputkan
// ---
$url_fasyankes = "https://sirs.kemkes.go.id/fo/index.php/Fasyankes";
$curl_fasyankes = curl_init();
curl_setopt($curl_fasyankes, CURLOPT_URL, $url_fasyankes);
curl_setopt($curl_fasyankes, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl_fasyankes, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($curl_fasyankes, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($curl_fasyankes, CURLOPT_HTTPHEADER, array("X-rs-id: ".$id,"X-Timestamp: ".$timestamp,"X-pass: ".$pass));
$result_fasyankes = curl_exec($curl_fasyankes);

if ($result_fasyankes === false) {
    echo "<b>Error GET Data Fasyankes:</b><br>";
    echo curl_error($curl_fasyankes);
} else {
    echo "<b>Data Tempat Tidur yang Sudah Pernah Diinputkan:</b><br>";
    echo $result_fasyankes;
    echo "<br><br>";

    // Dekode respons JSON
    $data_fasyankes = json_decode($result_fasyankes, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "Error dekode JSON: " . json_last_error_msg();
    } elseif (isset($data_fasyankes['fasyankes']) && is_array($data_fasyankes['fasyankes'])) {
        foreach ($data_fasyankes['fasyankes'] as $bed_data) {
            // Pemetaan Kolom:
            $rsk_id_t_tt = $bed_data['id_t_tt'] ?? null; // ID Unik dari SIRS (misal: "34968463")

            // Data yang digunakan untuk identifikasi unik (type ID dari SIRS + nama ruang)
            $sirs_type_id = $bed_data['id_tt'] ?? null; // ID Tipe Tempat Tidur dari SIRS (misal: "2" untuk VIP)
            $rsk_ruang = $bed_data['ruang'] ?? '';    // Nama Ruangan

            // Pastikan kita memiliki ID tempat tidur (dari SIRS) dan nama ruangan yang valid untuk identifikasi unik
            if (empty($rsk_id_t_tt) || empty($rsk_ruang)) {
                echo "Melewatkan record karena 'id_t_tt' (ID Unik SIRS) atau 'ruang' kosong: " . json_encode($bed_data) . "<br>";
                continue; // Lewati record jika tidak ada ID unik dari SIRS atau ruangan yang valid untuk identifikasi
            }

            // Data-data lain untuk kolom tabel Anda
            $rsk_jumlah_ruang = (int)($bed_data['jumlah_ruang'] ?? 0);
            $rsk_jumlah = (int)($bed_data['jumlah'] ?? 0);
            $rsk_terpakai = (int)($bed_data['terpakai'] ?? 0);
            $rsk_terpakai_suspek = (int)($bed_data['terpakai_suspek'] ?? 0);
            $rsk_terpakai_konfirmasi = (int)($bed_data['terpakai_konfirmasi'] ?? 0);
            $rsk_antrian = (int)($bed_data['antrian'] ?? 0);
            $rsk_prepare = (int)($bed_data['prepare'] ?? 0);
            $rsk_prepare_plan = (int)($bed_data['prepare_plan'] ?? 0);
            $rsk_covid = (int)($bed_data['covid'] ?? 0);
            $rsk_terpakai_dbd = (int)($bed_data['terpakai_dbd'] ?? 0);
            $rsk_terpakai_dbd_anak = (int)($bed_data['terpakai_dbd_anak'] ?? 0);
            $rsk_jumlah_dbd = 0; // Asumsi ini tidak disediakan oleh SIRS saat ini, atau Anda menurunkannya.

            // Konversi tglupdate ke format datetime MySQL yang valid
            $rsk_last_sync_at = !empty($bed_data['tglupdate']) ? date('Y-m-d H:i:s', strtotime($bed_data['tglupdate'])) : null;
            $rsk_is_synced = 1; // Diset true setelah diproses
            $rsk_created_by = 'SIRS_API_UPDATE'; // Atau identifikasi lain

            // Cek apakah record sudah ada menggunakan rsk_id_t_tt (dari id_t_tt SIRS) DAN rsk_ruang
            $stmt_check = $pdo->prepare("SELECT `rsk_map_id_t_tt` FROM `rsk_sirs_bed_updates` WHERE `rsk_id_t_tt` = :rsk_id_t_tt AND `rsk_ruang` = :rsk_ruang");
            $stmt_check->execute([
                ':rsk_id_t_tt' => $rsk_id_t_tt, // Menggunakan id_t_tt dari SIRS sebagai kunci pencarian
                ':rsk_ruang' => $rsk_ruang
            ]);
            $existing_record = $stmt_check->fetch(PDO::FETCH_ASSOC);

            if ($existing_record) {
                // Record sudah ada, tentukan nilai rsk_map_id_t_tt untuk update
                $current_rsk_map_id_t_tt = $existing_record['rsk_map_id_t_tt'];

                // Cek apakah rsk_map_id_t_tt saat ini di database kosong atau hanya "-"
                if (empty(trim($current_rsk_map_id_t_tt)) || $current_rsk_map_id_t_tt === '-') {
                    // Jika kosong atau "-", maka baru isi dengan "-"
                    $final_rsk_map_id_t_tt_for_update = "-";
                } else {
                    // Jika sudah terisi dengan nilai valid, pertahankan nilai tersebut
                    $final_rsk_map_id_t_tt_for_update = $current_rsk_map_id_t_tt;
                }

                // Update record yang sudah ada
                $sql = "UPDATE `rsk_sirs_bed_updates` SET
                            `rsk_map_id_t_tt` = :rsk_map_id_t_tt,
                            `rsk_jumlah_ruang` = :rsk_jumlah_ruang,
                            `rsk_jumlah` = :rsk_jumlah,
                            `rsk_terpakai` = :rsk_terpakai,
                            `rsk_terpakai_suspek` = :rsk_terpakai_suspek,
                            `rsk_terpakai_konfirmasi` = :rsk_terpakai_konfirmasi,
                            `rsk_antrian` = :rsk_antrian,
                            `rsk_prepare` = :rsk_prepare,
                            `rsk_prepare_plan` = :rsk_prepare_plan,
                            `rsk_covid` = :rsk_covid,
                            `rsk_terpakai_dbd` = :rsk_terpakai_dbd,
                            `rsk_terpakai_dbd_anak` = :rsk_terpakai_dbd_anak,
                            `rsk_jumlah_dbd` = :rsk_jumlah_dbd,
                            `rsk_is_synced` = :rsk_is_synced,
                            `rsk_last_sync_at` = :rsk_last_sync_at,
                            `rsk_updated_at` = CURRENT_TIMESTAMP
                        WHERE `rsk_id_t_tt` = :rsk_id_t_tt_where AND `rsk_ruang` = :rsk_ruang_where";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':rsk_map_id_t_tt' => $final_rsk_map_id_t_tt_for_update,
                    ':rsk_jumlah_ruang' => $rsk_jumlah_ruang,
                    ':rsk_jumlah' => $rsk_jumlah,
                    ':rsk_terpakai' => $rsk_terpakai,
                    ':rsk_terpakai_suspek' => $rsk_terpakai_suspek,
                    ':rsk_terpakai_konfirmasi' => $rsk_terpakai_konfirmasi,
                    ':rsk_antrian' => $rsk_antrian,
                    ':rsk_prepare' => $rsk_prepare,
                    ':rsk_prepare_plan' => $rsk_prepare_plan,
                    ':rsk_covid' => $rsk_covid,
                    ':rsk_terpakai_dbd' => $rsk_terpakai_dbd,
                    ':rsk_terpakai_dbd_anak' => $rsk_terpakai_dbd_anak,
                    ':rsk_jumlah_dbd' => $rsk_jumlah_dbd,
                    ':rsk_is_synced' => $rsk_is_synced,
                    ':rsk_last_sync_at' => $rsk_last_sync_at,
                    ':rsk_id_t_tt_where' => $rsk_id_t_tt, // Kunci pencarian: id_t_tt dari SIRS
                    ':rsk_ruang_where' => $rsk_ruang      // Kunci pencarian: nama ruang
                ]);
                echo "Memperbarui record dengan ID Unik SIRS: {$rsk_id_t_tt} dan Ruang: '{$rsk_ruang}'<br>";
            } else {
                // Record belum ada, insert baru. Untuk insert, rsk_map_id_t_tt selalu jadi "-".
                $sql = "INSERT INTO `rsk_sirs_bed_updates` (
                            `rsk_map_id_t_tt`, `rsk_id_t_tt`, `rsk_ruang`, `rsk_jumlah_ruang`,
                            `rsk_jumlah`, `rsk_terpakai`, `rsk_terpakai_suspek`, `rsk_terpakai_konfirmasi`,
                            `rsk_antrian`, `rsk_prepare`, `rsk_prepare_plan`, `rsk_covid`,
                            `rsk_terpakai_dbd`, `rsk_terpakai_dbd_anak`, `rsk_jumlah_dbd`,
                            `rsk_is_synced`, `rsk_last_sync_at`, `rsk_created_by`
                        ) VALUES (
                            :rsk_map_id_t_tt, :rsk_id_t_tt, :rsk_ruang, :rsk_jumlah_ruang,
                            :rsk_jumlah, :rsk_terpakai, :rsk_terpakai_suspek, :rsk_terpakai_konfirmasi,
                            :rsk_antrian, :rsk_prepare, :rsk_prepare_plan, :rsk_covid,
                            :rsk_terpakai_dbd, :rsk_terpakai_dbd_anak, :rsk_jumlah_dbd,
                            :rsk_is_synced, :rsk_last_sync_at, :rsk_created_by
                        )";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':rsk_map_id_t_tt' => $rsk_map_id_t_tt, // Untuk insert, ini akan selalu "-"
                    ':rsk_id_t_tt' => $rsk_id_t_tt, // Menyimpan id_t_tt SIRS ke rsk_id_t_tt
                    ':rsk_ruang' => $rsk_ruang,
                    ':rsk_jumlah_ruang' => $rsk_jumlah_ruang,
                    ':rsk_jumlah' => $rsk_jumlah,
                    ':rsk_terpakai' => $rsk_terpakai,
                    ':rsk_terpakai_suspek' => $rsk_terpakai_suspek,
                    ':rsk_terpakai_konfirmasi' => $rsk_terpakai_konfirmasi,
                    ':rsk_antrian' => $rsk_antrian,
                    ':rsk_prepare' => $rsk_prepare,
                    ':rsk_prepare_plan' => $rsk_prepare_plan,
                    ':rsk_covid' => $rsk_covid,
                    ':rsk_terpakai_dbd' => $rsk_terpakai_dbd,
                    ':rsk_terpakai_dbd_anak' => $rsk_terpakai_dbd_anak,
                    ':rsk_jumlah_dbd' => $rsk_jumlah_dbd,
                    ':rsk_is_synced' => $rsk_is_synced,
                    ':rsk_last_sync_at' => $rsk_last_sync_at,
                    ':rsk_created_by' => $rsk_created_by
                ]);
                echo "Memasukkan record baru dengan ID Unik SIRS: {$rsk_id_t_tt} dan Ruang: '{$rsk_ruang}'<br>";
            }
        }
        echo "<br>Semua data tempat tidur dari SIRS telah diproses dan diperbarui di database.";
    } else {
        echo "Data 'fasyankes' tidak ditemukan di respons SIRS atau formatnya tidak valid.";
    }
}
curl_close($curl_fasyankes);

echo "<br><br>";
echo "Ini GET";

?>
