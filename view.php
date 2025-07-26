<?php
require_once 'db_connect.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM rsk_sirs_bed_updates WHERE update_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    header("Location: index.php");
    exit();
}

// Format dates for display
$created_at = date('d M Y H:i', strtotime($row['rsk_created_at']));
$updated_at = date('d M Y H:i', strtotime($row['rsk_updated_at']));

// Calculate available beds
$available_beds = $row['rsk_jumlah'] - $row['rsk_terpakai'];
$occupancy_rate = $row['rsk_jumlah'] > 0 ? round(($row['rsk_terpakai'] / $row['rsk_jumlah']) * 100, 1) : 0;

require_once 'header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Status Alert -->
            <?php if (isset($_GET['status'])): ?>
                <?php 
                $status = $_GET['status'];
                $alert_class = 'alert-success';
                $message = 'Operation successful!';
                
                if ($status === 'updated') {
                    $message = 'Bed record updated successfully!';
                } elseif ($status === 'error') {
                    $alert_class = 'alert-danger';
                    $message = 'An error occurred!';
                }
                ?>
                <div class="alert <?php echo $alert_class; ?> alert-dismissible fade show mb-4" role="alert">
                    <i class="fas <?php echo $status === 'updated' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> me-2"></i>
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Main Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-bed me-2"></i>
                            Bed Details: <?php echo htmlspecialchars($row['rsk_ruang']); ?>
                        </h4>
                        <div>
                            <a href="edit.php?id=<?php echo $row['update_id']; ?>" class="btn btn-light btn-sm me-1">
                                <i class="fas fa-edit me-1"></i> Edit
                            </a>
                            <a href="index.php" class="btn btn-light btn-sm">
                                <i class="fas fa-list me-1"></i> All Beds
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Summary Cards -->
                <div class="card-body">
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <div class="card bg-light h-100">
                                <div class="card-body text-center">
                                    <h3 class="text-primary"><?php echo $row['rsk_jumlah']; ?></h3>
                                    <p class="text-muted mb-0">Total Beds</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light h-100">
                                <div class="card-body text-center">
                                    <h3 class="<?php echo $available_beds > 0 ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo $available_beds; ?>
                                    </h3>
                                    <p class="text-muted mb-0">Available Beds</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light h-100">
                                <div class="card-body text-center">
                                    <h3 class="text-warning"><?php echo $row['rsk_terpakai']; ?></h3>
                                    <p class="text-muted mb-0">Occupied Beds</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light h-100">
                                <div class="card-body text-center">
                                    <h3><?php echo $occupancy_rate; ?>%</h3>
                                    <p class="text-muted mb-0">Occupancy Rate</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Detailed Information -->
                    <div class="row g-3">
                        <!-- Basic Information -->
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Basic Information</h5>
                                </div>
                                <div class="card-body">
                                    <dl class="row">
                                        <dt class="col-sm-4">RSK Map ID</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($row['rsk_map_id_t_tt']); ?></dd>

                                        <dt class="col-sm-4">RSK ID</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($row['rsk_id_t_tt']); ?></dd>

                                        <dt class="col-sm-4">Room Name</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($row['rsk_ruang']); ?></dd>

                                        <dt class="col-sm-4">Room Capacity</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($row['rsk_jumlah_ruang']); ?></dd>

                                        <dt class="col-sm-4">Sync Status</dt>
                                        <dd class="col-sm-8">
                                            <?php if ($row['rsk_is_synced']): ?>
                                                <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> Synced</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i> Pending</span>
                                            <?php endif; ?>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>

                        <!-- Patient Statistics -->
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0"><i class="fas fa-procedures me-2"></i>Patient Statistics</h5>
                                </div>
                                <div class="card-body">
                                    <dl class="row">
                                        <dt class="col-sm-4">COVID Cases</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($row['rsk_covid']); ?></dd>

                                        <dt class="col-sm-4">Dengue Cases</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($row['rsk_terpakai_dbd']); ?></dd>

                                        <dt class="col-sm-4">Pediatric Dengue</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($row['rsk_terpakai_dbd_anak']); ?></dd>

                                        <dt class="col-sm-4">Suspected Cases</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($row['rsk_terpakai_suspek']); ?></dd>

                                        <dt class="col-sm-4">Confirmed Cases</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($row['rsk_terpakai_konfirmasi']); ?></dd>
                                    </dl>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Information -->
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Additional Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="card bg-light mb-3">
                                                <div class="card-body text-center">
                                                    <h5 class="text-primary"><?php echo htmlspecialchars($row['rsk_antrian']); ?></h5>
                                                    <p class="text-muted mb-0">Queue</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="card bg-light mb-3">
                                                <div class="card-body text-center">
                                                    <h5 class="text-primary"><?php echo htmlspecialchars($row['rsk_prepare']); ?></h5>
                                                    <p class="text-muted mb-0">Prepared Beds</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="card bg-light mb-3">
                                                <div class="card-body text-center">
                                                    <h5 class="text-primary"><?php echo htmlspecialchars($row['rsk_prepare_plan']); ?></h5>
                                                    <p class="text-muted mb-0">Planned Beds</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card Footer -->
                <div class="card-footer bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            <i class="fas fa-user me-1"></i> Created by <?php echo htmlspecialchars($row['rsk_created_by']); ?>
                        </small>
                        <small class="text-muted">
                            <i class="fas fa-clock me-1"></i> Last updated: <?php echo $updated_at; ?>
                        </small>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-3">
                <a href="edit.php?id=<?php echo $row['update_id']; ?>" class="btn btn-warning me-md-2">
                    <i class="fas fa-edit me-1"></i> Edit Record
                </a>
                <a href="delete.php?id=<?php echo $row['update_id']; ?>" class="btn btn-danger" 
                   onclick="return confirm('Are you sure you want to delete this bed record? This action cannot be undone.')">
                    <i class="fas fa-trash-alt me-1"></i> Delete Record
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>