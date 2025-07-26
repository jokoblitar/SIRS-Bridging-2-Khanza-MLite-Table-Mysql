<?php
function renderBedForm($data = []) {
    // Set default values
    $defaults = [
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
    
    // Merge with provided data
    $values = array_merge($defaults, $data);
    ?>
    
    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label for="rsk_map_id_t_tt" class="form-label">RSK Map ID</label>
                <input type="text" class="form-control" id="rsk_map_id_t_tt" name="rsk_map_id_t_tt" 
                       value="<?php echo htmlspecialchars($values['rsk_map_id_t_tt']); ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="rsk_id_t_tt" class="form-label">RSK ID</label>
                <input type="text" class="form-control" id="rsk_id_t_tt" name="rsk_id_t_tt" 
                       value="<?php echo htmlspecialchars($values['rsk_id_t_tt']); ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="rsk_ruang" class="form-label">Room Name</label>
                <input type="text" class="form-control" id="rsk_ruang" name="rsk_ruang" 
                       value="<?php echo htmlspecialchars($values['rsk_ruang']); ?>" required>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="rsk_jumlah_ruang" class="form-label">Room Capacity</label>
                        <input type="number" class="form-control" id="rsk_jumlah_ruang" name="rsk_jumlah_ruang" 
                               value="<?php echo htmlspecialchars($values['rsk_jumlah_ruang']); ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="rsk_jumlah" class="form-label">Total Beds</label>
                        <input type="number" class="form-control" id="rsk_jumlah" name="rsk_jumlah" 
                               value="<?php echo htmlspecialchars($values['rsk_jumlah']); ?>">
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="rsk_terpakai" class="form-label">Occupied Beds</label>
                        <input type="number" class="form-control" id="rsk_terpakai" name="rsk_terpakai" 
                               value="<?php echo htmlspecialchars($values['rsk_terpakai']); ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="rsk_covid" class="form-label">COVID Cases</label>
                        <input type="number" class="form-control" id="rsk_covid" name="rsk_covid" 
                               value="<?php echo htmlspecialchars($values['rsk_covid']); ?>">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="rsk_terpakai_dbd" class="form-label">Dengue Cases</label>
                        <input type="number" class="form-control" id="rsk_terpakai_dbd" name="rsk_terpakai_dbd" 
                               value="<?php echo htmlspecialchars($values['rsk_terpakai_dbd']); ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="rsk_terpakai_dbd_anak" class="form-label">Pediatric Dengue</label>
                        <input type="number" class="form-control" id="rsk_terpakai_dbd_anak" name="rsk_terpakai_dbd_anak" 
                               value="<?php echo htmlspecialchars($values['rsk_terpakai_dbd_anak']); ?>">
                    </div>
                </div>
            </div>
            
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="rsk_is_synced" name="rsk_is_synced" 
                       value="1" <?php echo $values['rsk_is_synced'] ? 'checked' : ''; ?>>
                <label class="form-check-label" for="rsk_is_synced">Synced with SIRS</label>
            </div>
            
            <div class="mb-3">
                <label for="rsk_created_by" class="form-label">Created By</label>
                <input type="text" class="form-control" id="rsk_created_by" name="rsk_created_by" 
                       value="<?php echo htmlspecialchars($values['rsk_created_by']); ?>">
            </div>
        </div>
    </div>
    
    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
        <a href="index.php" class="btn btn-secondary me-md-2">
            <i class="fas fa-arrow-left me-1"></i> Cancel
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-1"></i> Save
        </button>
    </div>
    <?php
}
?>