<?php
require_once 'db_connect.php'; // Pastikan file koneksi database Anda sudah benar

// Set header untuk mencegah caching jika script ini diakses langsung
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

try {
    // Mulai transaksi untuk memastikan atomicity
    $conn->begin_transaction();

    // LANGKAH BARU: Set semua simrs_terpakai menjadi 0 terlebih dahulu
    $sql_reset_simrs = "
        UPDATE rsk_sirs_bed_updates
        SET simrs_terpakai = 0;
    ";
    if (!$conn->query($sql_reset_simrs)) {
        throw new Exception("Error resetting simrs_terpakai: " . $conn->error);
    }
    $reset_count = $conn->affected_rows; // Jumlah baris yang direset

    // 1. Dapatkan total pasien terpakai per bangsal dari SIMRS
    $sql_get_occupied = "
        SELECT
            k.kd_bangsal,
            COUNT(ki.no_rawat) AS simrs_occupied_count
        FROM
            kamar_inap ki
        JOIN
            kamar k ON ki.kd_kamar = k.kd_kamar
        WHERE
            ki.tgl_keluar IS NULL
        GROUP BY
            k.kd_bangsal;
    ";
    $result_occupied = $conn->query($sql_get_occupied);

    if (!$result_occupied) {
        throw new Exception("Error fetching occupied beds from SIMRS: " . $conn->error);
    }

    $update_count = 0;

    // 2. Siapkan pernyataan UPDATE untuk rsk_sirs_bed_updates
    $sql_update_sirs = "
        UPDATE rsk_sirs_bed_updates
        SET
            simrs_terpakai = ?,
            rsk_updated_at = CURRENT_TIMESTAMP
        WHERE
            rsk_map_id_t_tt = ?;
    ";
    $stmt_update = $conn->prepare($sql_update_sirs);

    if (!$stmt_update) {
        throw new Exception("Error preparing update statement: " . $conn->error);
    }

    // 3. Iterasi hasil dan lakukan update
    while ($row = $result_occupied->fetch_assoc()) {
        $kd_bangsal = $row['kd_bangsal'];
        $simrs_occupied_count = $row['simrs_occupied_count'];

        // Bind parameter dan eksekusi update
        $stmt_update->bind_param("is", $simrs_occupied_count, $kd_bangsal);
        if ($stmt_update->execute()) {
            $update_count += $stmt_update->affected_rows;
        } else {
            // Jika ada error pada update tertentu, log atau tangani
            error_log("Failed to update rsk_sirs_bed_updates for kd_bangsal: $kd_bangsal. Error: " . $stmt_update->error);
        }
    }

    // Tutup statement update
    $stmt_update->close();

    // Commit transaksi jika semua berhasil
    $conn->commit();

    // Redirect kembali ke halaman utama dengan status sukses
    header("Location: index.php?status=updated_simrs&reset_count=" . $reset_count . "&update_count=" . $update_count);
    exit();

} catch (Exception $e) {
    // Rollback transaksi jika terjadi error
    $conn->rollback();
    error_log("Error updating bed status: " . $e->getMessage());
    // Redirect kembali ke halaman utama dengan status error
    header("Location: index.php?status=error&message=" . urlencode($e->getMessage()));
    exit();
} finally {
    // Pastikan koneksi ditutup
    if ($conn) {
        $conn->close();
    }
}
?>