<?php
require_once 'db_connect.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = $_GET['id'];
$errors = [];
$success = false;

// Get current data
$stmt = $conn->prepare("SELECT * FROM rsk_sirs_bed_updates WHERE update_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $formData = array_map('trim', $_POST);
    $formData['rsk_is_synced'] = isset($_POST['rsk_is_synced']) ? 1 : 0;
    
    // Required field validation
    $requiredFields = [
        'rsk_map_id_t_tt' => 'RSK Map ID',
        'rsk_id_t_tt' => 'RSK ID', 
        'rsk_ruang' => 'Room Name'
    ];
    
    foreach ($requiredFields as $field => $name) {
        if (empty($formData[$field])) {
            $errors[$field] = "$name is required";
        }
    }
    
    // Numeric field validation
    $numericFields = [
        'rsk_jumlah_ruang', 'rsk_jumlah', 'rsk_terpakai', 'simrs_terpakai',
        'rsk_terpakai_suspek', 'rsk_terpakai_konfirmasi',
        'rsk_antrian', 'rsk_prepare', 'rsk_prepare_plan',
        'rsk_covid', 'rsk_terpakai_dbd', 'rsk_terpakai_dbd_anak',
        'rsk_jumlah_dbd'
    ];
    
    foreach ($numericFields as $field) {
        if (!is_numeric($formData[$field])) {
            $errors[$field] = 'Must be a number';
        } elseif ($formData[$field] < 0) {
            $errors[$field] = 'Cannot be negative';
        }
    }
    
    // Validate bed counts
    if (empty($errors)) {
        if ($formData['rsk_terpakai'] > $formData['rsk_jumlah']) {
            $errors['rsk_terpakai'] = 'Occupied beds cannot exceed total beds';
        }
        
        if ($formData['rsk_covid'] > $formData['rsk_terpakai']) {
            $errors['rsk_covid'] = 'COVID cases cannot exceed occupied beds';
        }
    }
    
    // If no errors, update database
    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE rsk_sirs_bed_updates SET 
            rsk_map_id_t_tt = ?, rsk_id_t_tt = ?, rsk_ruang = ?, 
            rsk_jumlah_ruang = ?, rsk_jumlah = ?, rsk_terpakai = ?, 
            simrs_terpakai = ?, rsk_terpakai_suspek = ?, rsk_terpakai_konfirmasi = ?,
            rsk_antrian = ?, rsk_prepare = ?, rsk_prepare_plan = ?, 
            rsk_covid = ?, rsk_terpakai_dbd = ?, rsk_terpakai_dbd_anak = ?,
            rsk_jumlah_dbd = ?, rsk_is_synced = ?, rsk_created_by = ?,
            rsk_updated_at = NOW()
            WHERE update_id = ?");
        
        $stmt->bind_param("sssiiiiiiiiiiiiisii", 
            $formData['rsk_map_id_t_tt'], $formData['rsk_id_t_tt'], 
            $formData['rsk_ruang'], $formData['rsk_jumlah_ruang'], 
            $formData['rsk_jumlah'], $formData['rsk_terpakai'],
            $formData['simrs_terpakai'],
            $formData['rsk_terpakai_suspek'], $formData['rsk_terpakai_konfirmasi'],
            $formData['rsk_antrian'], $formData['rsk_prepare'], 
            $formData['rsk_prepare_plan'], $formData['rsk_covid'], 
            $formData['rsk_terpakai_dbd'], $formData['rsk_terpakai_dbd_anak'],
            $formData['rsk_jumlah_dbd'], $formData['rsk_is_synced'], 
            $formData['rsk_created_by'], $id);
        
        if ($stmt->execute()) {
            $success = true;
            // Refresh data after update
            $stmt = $conn->prepare("SELECT * FROM rsk_sirs_bed_updates WHERE update_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
        } else {
            $errors['database'] = 'Error updating record: ' . $stmt->error;
        }
    }
}

require_once 'header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-edit me-2"></i>
                            Edit Bed Record: <?php echo htmlspecialchars($row['rsk_ruang']); ?>
                        </h4>
                        <a href="view.php?id=<?php echo $row['update_id']; ?>" class="btn btn-light btn-sm">
                            <i class="fas fa-eye me-1"></i> View
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                            <i class="fas fa-check-circle me-2"></i> Bed record updated successfully!
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($errors['database'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i> <?php echo $errors['database']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Basic Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="rsk_map_id_t_tt" class="form-label">RSK Map ID <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control <?php echo isset($errors['rsk_map_id_t_tt']) ? 'is-invalid' : ''; ?>" 
                                                   id="rsk_map_id_t_tt" name="rsk_map_id_t_tt" 
                                                   value="<?php echo htmlspecialchars($row['rsk_map_id_t_tt']); ?>" required>
                                            <?php if (isset($errors['rsk_map_id_t_tt'])): ?>
                                                <div class="invalid-feedback"><?php echo $errors['rsk_map_id_t_tt']; ?></div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="rsk_id_t_tt" class="form-label">RSK ID <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control <?php echo isset($errors['rsk_id_t_tt']) ? 'is-invalid' : ''; ?>" 
                                                   id="rsk_id_t_tt" name="rsk_id_t_tt" 
                                                   value="<?php echo htmlspecialchars($row['rsk_id_t_tt']); ?>" required>
                                            <?php if (isset($errors['rsk_id_t_tt'])): ?>
                                                <div class="invalid-feedback"><?php echo $errors['rsk_id_t_tt']; ?></div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="rsk_ruang" class="form-label">Room Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control <?php echo isset($errors['rsk_ruang']) ? 'is-invalid' : ''; ?>" 
                                                   id="rsk_ruang" name="rsk_ruang" 
                                                   value="<?php echo htmlspecialchars($row['rsk_ruang']); ?>" required>
                                            <?php if (isset($errors['rsk_ruang'])): ?>
                                                <div class="invalid-feedback"><?php echo $errors['rsk_ruang']; ?></div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="rsk_jumlah_ruang" class="form-label">Room Capacity</label>
                                                    <input type="number" min="0" class="form-control <?php echo isset($errors['rsk_jumlah_ruang']) ? 'is-invalid' : ''; ?>" 
                                                           id="rsk_jumlah_ruang" name="rsk_jumlah_ruang" 
                                                           value="<?php echo htmlspecialchars($row['rsk_jumlah_ruang']); ?>">
                                                    <?php if (isset($errors['rsk_jumlah_ruang'])): ?>
                                                        <div class="invalid-feedback"><?php echo $errors['rsk_jumlah_ruang']; ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="rsk_jumlah" class="form-label">Total Beds</label>
                                                    <input type="number" min="0" class="form-control <?php echo isset($errors['rsk_jumlah']) ? 'is-invalid' : ''; ?>" 
                                                           id="rsk_jumlah" name="rsk_jumlah" 
                                                           value="<?php echo htmlspecialchars($row['rsk_jumlah']); ?>">
                                                    <?php if (isset($errors['rsk_jumlah'])): ?>
                                                        <div class="invalid-feedback"><?php echo $errors['rsk_jumlah']; ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0"><i class="fas fa-procedures me-2"></i>Occupancy Details</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="rsk_terpakai" class="form-label">Occupied Beds</label>
                                                    <input type="number" min="0" class="form-control <?php echo isset($errors['rsk_terpakai']) ? 'is-invalid' : ''; ?>" 
                                                           id="rsk_terpakai" name="rsk_terpakai" 
                                                           value="<?php echo htmlspecialchars($row['rsk_terpakai']); ?>">
                                                    <?php if (isset($errors['rsk_terpakai'])): ?>
                                                        <div class="invalid-feedback"><?php echo $errors['rsk_terpakai']; ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="simrs_terpakai" class="form-label">SIMRS Occupied Beds</label>
                                                    <input type="number" min="0" class="form-control <?php echo isset($errors['simrs_terpakai']) ? 'is-invalid' : ''; ?>" 
                                                           id="simrs_terpakai" name="simrs_terpakai" 
                                                           value="<?php echo htmlspecialchars($row['simrs_terpakai']); ?>">
                                                    <?php if (isset($errors['simrs_terpakai'])): ?>
                                                        <div class="invalid-feedback"><?php echo $errors['simrs_terpakai']; ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="rsk_covid" class="form-label">COVID Cases</label>
                                                    <input type="number" min="0" class="form-control <?php echo isset($errors['rsk_covid']) ? 'is-invalid' : ''; ?>" 
                                                           id="rsk_covid" name="rsk_covid" 
                                                           value="<?php echo htmlspecialchars($row['rsk_covid']); ?>">
                                                    <?php if (isset($errors['rsk_covid'])): ?>
                                                        <div class="invalid-feedback"><?php echo $errors['rsk_covid']; ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="rsk_terpakai_dbd" class="form-label">Dengue Cases</label>
                                                    <input type="number" min="0" class="form-control <?php echo isset($errors['rsk_terpakai_dbd']) ? 'is-invalid' : ''; ?>" 
                                                           id="rsk_terpakai_dbd" name="rsk_terpakai_dbd" 
                                                           value="<?php echo htmlspecialchars($row['rsk_terpakai_dbd']); ?>">
                                                    <?php if (isset($errors['rsk_terpakai_dbd'])): ?>
                                                        <div class="invalid-feedback"><?php echo $errors['rsk_terpakai_dbd']; ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="rsk_terpakai_dbd_anak" class="form-label">Pediatric Dengue</label>
                                                    <input type="number" min="0" class="form-control <?php echo isset($errors['rsk_terpakai_dbd_anak']) ? 'is-invalid' : ''; ?>" 
                                                           id="rsk_terpakai_dbd_anak" name="rsk_terpakai_dbd_anak" 
                                                           value="<?php echo htmlspecialchars($row['rsk_terpakai_dbd_anak']); ?>">
                                                    <?php if (isset($errors['rsk_terpakai_dbd_anak'])): ?>
                                                        <div class="invalid-feedback"><?php echo $errors['rsk_terpakai_dbd_anak']; ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="rsk_jumlah_dbd" class="form-label">Total Dengue Beds</label>
                                                    <input type="number" min="0" class="form-control <?php echo isset($errors['rsk_jumlah_dbd']) ? 'is-invalid' : ''; ?>" 
                                                           id="rsk_jumlah_dbd" name="rsk_jumlah_dbd" 
                                                           value="<?php echo htmlspecialchars($row['rsk_jumlah_dbd']); ?>">
                                                    <?php if (isset($errors['rsk_jumlah_dbd'])): ?>
                                                        <div class="invalid-feedback"><?php echo $errors['rsk_jumlah_dbd']; ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3 form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="rsk_is_synced" name="rsk_is_synced" 
                                                   value="1" <?php echo $row['rsk_is_synced'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="rsk_is_synced">Synced with SIRS</label>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="rsk_created_by" class="form-label">Created By</label>
                                            <input type="text" class="form-control" id="rsk_created_by" name="rsk_created_by" 
                                                   value="<?php echo htmlspecialchars($row['rsk_created_by']); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Additional Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label for="rsk_terpakai_suspek" class="form-label">Suspected Cases</label>
                                                    <input type="number" min="0" class="form-control <?php echo isset($errors['rsk_terpakai_suspek']) ? 'is-invalid' : ''; ?>" 
                                                           id="rsk_terpakai_suspek" name="rsk_terpakai_suspek" 
                                                           value="<?php echo htmlspecialchars($row['rsk_terpakai_suspek']); ?>">
                                                    <?php if (isset($errors['rsk_terpakai_suspek'])): ?>
                                                        <div class="invalid-feedback"><?php echo $errors['rsk_terpakai_suspek']; ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label for="rsk_terpakai_konfirmasi" class="form-label">Confirmed Cases</label>
                                                    <input type="number" min="0" class="form-control <?php echo isset($errors['rsk_terpakai_konfirmasi']) ? 'is-invalid' : ''; ?>" 
                                                           id="rsk_terpakai_konfirmasi" name="rsk_terpakai_konfirmasi" 
                                                           value="<?php echo htmlspecialchars($row['rsk_terpakai_konfirmasi']); ?>">
                                                    <?php if (isset($errors['rsk_terpakai_konfirmasi'])): ?>
                                                        <div class="invalid-feedback"><?php echo $errors['rsk_terpakai_konfirmasi']; ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label for="rsk_antrian" class="form-label">Queue</label>
                                                    <input type="number" min="0" class="form-control <?php echo isset($errors['rsk_antrian']) ? 'is-invalid' : ''; ?>" 
                                                           id="rsk_antrian" name="rsk_antrian" 
                                                           value="<?php echo htmlspecialchars($row['rsk_antrian']); ?>">
                                                    <?php if (isset($errors['rsk_antrian'])): ?>
                                                        <div class="invalid-feedback"><?php echo $errors['rsk_antrian']; ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="rsk_prepare" class="form-label">Prepared Beds</label>
                                                    <input type="number" min="0" class="form-control <?php echo isset($errors['rsk_prepare']) ? 'is-invalid' : ''; ?>" 
                                                           id="rsk_prepare" name="rsk_prepare" 
                                                           value="<?php echo htmlspecialchars($row['rsk_prepare']); ?>">
                                                    <?php if (isset($errors['rsk_prepare'])): ?>
                                                        <div class="invalid-feedback"><?php echo $errors['rsk_prepare']; ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="rsk_prepare_plan" class="form-label">Planned Beds</label>
                                                    <input type="number" min="0" class="form-control <?php echo isset($errors['rsk_prepare_plan']) ? 'is-invalid' : ''; ?>" 
                                                           id="rsk_prepare_plan" name="rsk_prepare_plan" 
                                                           value="<?php echo htmlspecialchars($row['rsk_prepare_plan']); ?>">
                                                    <?php if (isset($errors['rsk_prepare_plan'])): ?>
                                                        <div class="invalid-feedback"><?php echo $errors['rsk_prepare_plan']; ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="view.php?id=<?php echo $row['update_id']; ?>" class="btn btn-secondary me-md-2">
                            <i class="fas fa-times me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="card-footer bg-light">
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i> Last updated: <?php echo date('d M Y H:i', strtotime($row['rsk_updated_at'])); ?>
                </small>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>