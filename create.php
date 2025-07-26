<?php
require_once 'db_connect.php';

// Initialize variables with empty values
$formData = [
    'rsk_map_id_t_tt' => '',
    'rsk_id_t_tt' => '',
    'rsk_ruang' => '',
    'rsk_jumlah_ruang' => 0,
    'rsk_jumlah' => 0,
    'rsk_terpakai' => 0,
    'rsk_terpakai_suspek' => 0,
    'rsk_terpakai_konfirmasi' => 0,
    'rsk_antrian' => 0,
    'rsk_prepare' => 0,
    'rsk_prepare_plan' => 0,
    'rsk_covid' => 0,
    'rsk_terpakai_dbd' => 0,
    'rsk_terpakai_dbd_anak' => 0,
    'rsk_jumlah_dbd' => 0,
    'rsk_is_synced' => 0,
    'rsk_created_by' => ''
];

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $formData = array_map('trim', $_POST);
    $formData['rsk_is_synced'] = isset($_POST['rsk_is_synced']) ? 1 : 0;
    
    // Basic validation
    if (empty($formData['rsk_map_id_t_tt'])) {
        $errors['rsk_map_id_t_tt'] = 'RSK Map ID is required';
    }
    
    if (empty($formData['rsk_id_t_tt'])) {
        $errors['rsk_id_t_tt'] = 'RSK ID is required';
    }
    
    if (empty($formData['rsk_ruang'])) {
        $errors['rsk_ruang'] = 'Room name is required';
    }
    
    // Numeric field validation
    $numericFields = [
        'rsk_jumlah_ruang', 'rsk_jumlah', 'rsk_terpakai', 
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
    
    // If no errors, proceed with database insertion
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO rsk_sirs_bed_updates (
            rsk_map_id_t_tt, rsk_id_t_tt, rsk_ruang, rsk_jumlah_ruang, rsk_jumlah, 
            rsk_terpakai, rsk_terpakai_suspek, rsk_terpakai_konfirmasi, rsk_antrian, 
            rsk_prepare, rsk_prepare_plan, rsk_covid, rsk_terpakai_dbd, rsk_terpakai_dbd_anak, 
            rsk_jumlah_dbd, rsk_is_synced, rsk_created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->bind_param("sssiiiiiiiiiiiiis", 
            $formData['rsk_map_id_t_tt'], $formData['rsk_id_t_tt'], $formData['rsk_ruang'], 
            $formData['rsk_jumlah_ruang'], $formData['rsk_jumlah'],
            $formData['rsk_terpakai'], $formData['rsk_terpakai_suspek'], 
            $formData['rsk_terpakai_konfirmasi'], $formData['rsk_antrian'],
            $formData['rsk_prepare'], $formData['rsk_prepare_plan'], 
            $formData['rsk_covid'], $formData['rsk_terpakai_dbd'], 
            $formData['rsk_terpakai_dbd_anak'], $formData['rsk_jumlah_dbd'], 
            $formData['rsk_is_synced'], $formData['rsk_created_by']);
        
        if ($stmt->execute()) {
            $success = true;
            // Reset form data after successful submission
            $formData = array_fill_keys(array_keys($formData), '');
            $formData['rsk_jumlah_ruang'] = 0;
            $formData['rsk_jumlah'] = 0;
            $formData['rsk_terpakai'] = 0;
        } else {
            $errors['database'] = 'Error saving to database: ' . $stmt->error;
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
                        <h4 class="mb-0"><i class="fas fa-bed me-2"></i>Add New Bed Information</h4>
                        <a href="index.php" class="btn btn-light btn-sm">
                            <i class="fas fa-arrow-left me-1"></i> Back to List
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i> Bed information added successfully!
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($errors['database'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i> <?php echo $errors['database']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" novalidate>
                        <div class="row g-3">
                            <!-- Basic Information Section -->
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
                                                   value="<?php echo htmlspecialchars($formData['rsk_map_id_t_tt']); ?>" required>
                                            <?php if (isset($errors['rsk_map_id_t_tt'])): ?>
                                                <div class="invalid-feedback"><?php echo $errors['rsk_map_id_t_tt']; ?></div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="rsk_id_t_tt" class="form-label">RSK ID <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control <?php echo isset($errors['rsk_id_t_tt']) ? 'is-invalid' : ''; ?>" 
                                                   id="rsk_id_t_tt" name="rsk_id_t_tt" 
                                                   value="<?php echo htmlspecialchars($formData['rsk_id_t_tt']); ?>" required>
                                            <?php if (isset($errors['rsk_id_t_tt'])): ?>
                                                <div class="invalid-feedback"><?php echo $errors['rsk_id_t_tt']; ?></div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="rsk_ruang" class="form-label">Room Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control <?php echo isset($errors['rsk_ruang']) ? 'is-invalid' : ''; ?>" 
                                                   id="rsk_ruang" name="rsk_ruang" 
                                                   value="<?php echo htmlspecialchars($formData['rsk_ruang']); ?>" required>
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
                                                           value="<?php echo htmlspecialchars($formData['rsk_jumlah_ruang']); ?>">
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
                                                           value="<?php echo htmlspecialchars($formData['rsk_jumlah']); ?>">
                                                    <?php if (isset($errors['rsk_jumlah'])): ?>
                                                        <div class="invalid-feedback"><?php echo $errors['rsk_jumlah']; ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Occupancy Details Section -->
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
                                                           value="<?php echo htmlspecialchars($formData['rsk_terpakai']); ?>">
                                                    <?php if (isset($errors['rsk_terpakai'])): ?>
                                                        <div class="invalid-feedback"><?php echo $errors['rsk_terpakai']; ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="rsk_covid" class="form-label">COVID Cases</label>
                                                    <input type="number" min="0" class="form-control <?php echo isset($errors['rsk_covid']) ? 'is-invalid' : ''; ?>" 
                                                           id="rsk_covid" name="rsk_covid" 
                                                           value="<?php echo htmlspecialchars($formData['rsk_covid']); ?>">
                                                    <?php if (isset($errors['rsk_covid'])): ?>
                                                        <div class="invalid-feedback"><?php echo $errors['rsk_covid']; ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="rsk_terpakai_dbd" class="form-label">Dengue Cases</label>
                                                    <input type="number" min="0" class="form-control <?php echo isset($errors['rsk_terpakai_dbd']) ? 'is-invalid' : ''; ?>" 
                                                           id="rsk_terpakai_dbd" name="rsk_terpakai_dbd" 
                                                           value="<?php echo htmlspecialchars($formData['rsk_terpakai_dbd']); ?>">
                                                    <?php if (isset($errors['rsk_terpakai_dbd'])): ?>
                                                        <div class="invalid-feedback"><?php echo $errors['rsk_terpakai_dbd']; ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="rsk_terpakai_dbd_anak" class="form-label">Pediatric Dengue</label>
                                                    <input type="number" min="0" class="form-control <?php echo isset($errors['rsk_terpakai_dbd_anak']) ? 'is-invalid' : ''; ?>" 
                                                           id="rsk_terpakai_dbd_anak" name="rsk_terpakai_dbd_anak" 
                                                           value="<?php echo htmlspecialchars($formData['rsk_terpakai_dbd_anak']); ?>">
                                                    <?php if (isset($errors['rsk_terpakai_dbd_anak'])): ?>
                                                        <div class="invalid-feedback"><?php echo $errors['rsk_terpakai_dbd_anak']; ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3 form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="rsk_is_synced" name="rsk_is_synced" 
                                                   value="1" <?php echo $formData['rsk_is_synced'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="rsk_is_synced">Synced with SIRS</label>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="rsk_created_by" class="form-label">Created By</label>
                                            <input type="text" class="form-control" id="rsk_created_by" name="rsk_created_by" 
                                                   value="<?php echo htmlspecialchars($formData['rsk_created_by']); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Additional Information Section -->
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
                                                           value="<?php echo htmlspecialchars($formData['rsk_terpakai_suspek']); ?>">
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
                                                           value="<?php echo htmlspecialchars($formData['rsk_terpakai_konfirmasi']); ?>">
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
                                                       value="<?php echo htmlspecialchars($formData['rsk_antrian']); ?>">
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
                                                       value="<?php echo htmlspecialchars($formData['rsk_prepare']); ?>">
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
                                                       value="<?php echo htmlspecialchars($formData['rsk_prepare_plan']); ?>">
                                                <?php if (isset($errors['rsk_prepare_plan'])): ?>
                                                    <div class="invalid-feedback"><?php echo $errors['rsk_prepare_plan']; ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="rsk_jumlah_dbd" class="form-label">Total Dengue Beds</label>
                                        <input type="number" min="0" class="form-control <?php echo isset($errors['rsk_jumlah_dbd']) ? 'is-invalid' : ''; ?>" 
                                               id="rsk_jumlah_dbd" name="rsk_jumlah_dbd" 
                                               value="<?php echo htmlspecialchars($formData['rsk_jumlah_dbd']); ?>">
                                        <?php if (isset($errors['rsk_jumlah_dbd'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['rsk_jumlah_dbd']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Form Submission -->
                        <div class="col-12">
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="reset" class="btn btn-secondary me-md-2">
                                    <i class="fas fa-undo me-1"></i> Reset
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Save Bed Information
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="card-footer bg-light">
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i> Fields marked with <span class="text-danger">*</span> are required
                </small>
            </div>
        </div>
    </div>
</div>
                                                    