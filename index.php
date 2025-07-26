<?php
require_once 'db_connect.php';
require_once 'header.php';

// Handle status messages
if (isset($_GET['status'])) {
    $status = $_GET['status'];
    $alert_class = 'alert-success';
    $message = 'Operation successful!';
    
    if ($status === 'created') {
        $message = 'Bed record created successfully!';
    } elseif ($status === 'updated') {
        $message = 'Bed record updated successfully!';
    } elseif ($status === 'deleted') {
        $message = 'Bed record deleted successfully!';
    } elseif ($status === 'updated_simrs') { // Tambahkan ini
        $reset_count = isset($_GET['reset_count']) ? (int)$_GET['reset_count'] : 0;
        $update_count = isset($_GET['update_count']) ? (int)$_GET['update_count'] : 0;
        $message = 'SIMRS bed status updated successfully! ' . $update_count . ' records updated, ' . $reset_count . ' records reset.';
    } elseif ($status === 'error') {
        $alert_class = 'alert-danger';
        $message = 'An error occurred!';
        if (isset($_GET['message'])) {
            $message .= ': ' . htmlspecialchars($_GET['message']);
        }
    }
    
    echo '<div class="alert '.$alert_class.' alert-dismissible fade show" role="alert">
            '.$message.'
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
}

// Get all bed updates
$sql = "SELECT * FROM rsk_sirs_bed_updates ORDER BY rsk_updated_at DESC";
$result = $conn->query($sql);

// Initialize total variables
$total_beds_sum = 0;
$occupied_beds_sum = 0;
$simrs_occupied_beds_sum = 0; // Initialize for SIMRS occupied
$covid_beds_sum = 0;
?>

<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap">
        <h5 class="mb-0 me-auto">
            <i class="fas fa-bed me-2"></i>Bed Availability
        </h5>
        <!-- Live Clock Display -->
        <div id="live-clock" class="text-muted small me-3"></div>

        <div class="d-flex flex-wrap gap-2">
            <a href="create.php" class="btn btn-primary btn-sm">
                <i class="fas fa-plus me-1"></i> Add New
            </a>
            <a href="sirs_sync_upload.php" target="_blank" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Kirim Data ke SIRS KEMKES">
                <i class="fas fa-paper-plane me-2"></i> Kirim-2-SIRS-KEMKES
            </a>
            <a href="update_bed_status.php" target="_blank" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Update Data dari SIMRS">
                <i class="fas fa-sync-alt me-2"></i> Sync-SIMRS
            </a>
            <a href="sirs_sync.php" target="_blank" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Sinkronisasi Data SIRS">
                <i class="fas fa-sync-alt me-2"></i> Sync-SIRS
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover table-sm">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>MapRS</th>
                        <th>Room</th>
                        <th>Total<br> Beds</th>
                        <th>SIRS<br>Occupied</th>
                        <th>SIMRS<br>Occupied</th>
                        <th>COVID</th>
                        <th>Sync<br>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($result->num_rows > 0):
                        while ($row = $result->fetch_assoc()): 
                            // Accumulate totals
                            $total_beds_sum += $row['rsk_jumlah'];
                            $occupied_beds_sum += $row['rsk_terpakai'];
                            $simrs_occupied_beds_sum += $row['simrs_terpakai'];
                            $covid_beds_sum += $row['rsk_covid'];
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['update_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['rsk_map_id_t_tt']); ?></td>
                        <td><?php echo htmlspecialchars($row['rsk_ruang']); ?></td>
                        <td><?php echo htmlspecialchars($row['rsk_jumlah']); ?></td>
                        <td><?php echo htmlspecialchars($row['rsk_terpakai']); ?></td>
                        <td><?php echo htmlspecialchars($row['simrs_terpakai']); ?></td>
                        <td><?php echo htmlspecialchars($row['rsk_covid']); ?></td>
                        <td>
                            <?php if ($row['rsk_is_synced']): ?>
                                <span class="badge bg-success status-badge">Synced</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark status-badge">Pending</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="view.php?id=<?php echo $row['update_id']; ?>" class="btn btn-sm btn-info btn-sm-block" data-bs-toggle="tooltip" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="edit.php?id=<?php echo $row['update_id']; ?>" class="btn btn-sm btn-warning btn-sm-block" data-bs-toggle="tooltip" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="delete.php?id=<?php echo $row['update_id']; ?>" class="btn btn-sm btn-danger btn-sm-block" data-bs-toggle="tooltip" title="Delete" onclick="return confirm('Are you sure you want to delete this record?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <!-- Total row -->
                    <tr class="table-dark">
                        <td colspan="3" class="text-end fw-bold">Total:</td>
                        <td class="fw-bold"><?php echo $total_beds_sum; ?></td>
                        <td class="fw-bold"><?php echo $occupied_beds_sum; ?></td>
                        <td class="fw-bold"><?php echo $simrs_occupied_beds_sum; ?></td>
                        <td class="fw-bold"><?php echo $covid_beds_sum; ?></td>
                        <td colspan="2"></td> <!-- Empty cells for Sync Status and Actions -->
                    </tr>
                    <?php else: ?>
                    <tr>
                        <td colspan="9" class="text-center">No bed records found.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- JavaScript for Live Clock -->
<script>
    function updateLiveClock() {
        const now = new Date();
        const options = { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric', 
            hour: '2-digit', 
            minute: '2-digit', 
            second: '2-digit', 
            hour12: false 
        };
        const formattedDateTime = now.toLocaleDateString('id-ID', options); // 'id-ID' for Indonesian locale
        document.getElementById('live-clock').textContent = formattedDateTime;
    }

    // Update the clock every second
    setInterval(updateLiveClock, 1000);

    // Initial call to display the clock immediately
    updateLiveClock();
</script>

<?php require_once 'footer.php'; ?>