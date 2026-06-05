<?php
/**
 * Permissions Debug Tool
 * 
 * Diagnostic interface for inspecting permission system
 * Access: System Admin only
 */

include('admin_elements/admin_header.php');
Roles::requireSystemAdmin();

$moduleSlug = isset($_GET['module']) ? trim($_GET['module']) : '';
$roleId = isset($_GET['role_id']) ? (int)$_GET['role_id'] : 0;

$moduleId = 0;
$moduleName = '';
$definedPerms = [];
$grantedPerms = [];
$missingPerms = [];
$allModulesData = []; // Store all modules data when role is selected without module

if ($moduleSlug !== '') {
    $moduleId = getModuleIdBySlug($moduleSlug, $mysqli);
    if ($moduleId > 0) {
        $stmt = $mysqli->prepare("SELECT module_name FROM " . DB::MODULES . " WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $moduleId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $moduleName = $row['module_name'] ?? '';
        $stmt->close();

        $stmt = $mysqli->prepare("SELECT id, slug FROM " . DB::MODULE_PERMISSIONS . " WHERE module_id = ? ORDER BY slug");
        $stmt->bind_param('i', $moduleId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $definedPerms[] = $row['slug'];
        }
        $stmt->close();

        if ($roleId > 0) {
            $stmt = $mysqli->prepare(
                "SELECT mp.slug
                 FROM " . DB::PERMISSIONS . " p
                 INNER JOIN " . DB::MODULE_PERMISSIONS . " mp ON mp.id = p.permission_id
                 WHERE p.role_id = ? AND p.module_id = ?
                 ORDER BY mp.slug"
            );
            $stmt->bind_param('ii', $roleId, $moduleId);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $grantedPerms[] = $row['slug'];
            }
            $stmt->close();
            
            // Calculate missing permissions
            $missingPerms = array_diff($definedPerms, $grantedPerms);
        }
    }
} else if ($moduleSlug === '' && $roleId > 0) {
    // Show all modules with their permission status for the selected role
    $stmt = $mysqli->prepare("SELECT id, module_name, slug FROM " . DB::MODULES . " ORDER BY module_name");
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $modId = $row['id'];
        $modSlug = $row['slug'];
        
        // Get all permissions for this module
        $permStmt = $mysqli->prepare("SELECT id, slug FROM " . DB::MODULE_PERMISSIONS . " WHERE module_id = ?");
        $permStmt->bind_param('i', $modId);
        $permStmt->execute();
        $permResult = $permStmt->get_result();
        $totalPerms = 0;
        $perms = [];
        while ($permRow = $permResult->fetch_assoc()) {
            $totalPerms++;
            $perms[] = $permRow;
        }
        $permStmt->close();
        
        // Get granted permissions for this module + role
        $grantStmt = $mysqli->prepare(
            "SELECT COUNT(*) as count FROM " . DB::PERMISSIONS . " 
                WHERE role_id = ? AND module_id = ?"
        );
        $grantStmt->bind_param('ii', $roleId, $modId);
        $grantStmt->execute();
        $grantResult = $grantStmt->get_result();
        $grantRow = $grantResult->fetch_assoc();
        $grantedCount = $grantRow['count'] ?? 0;
        $grantStmt->close();
        
        $allModulesData[] = [
            'id' => $modId,
            'name' => $row['module_name'],
            'slug' => $modSlug,
            'total_permissions' => $totalPerms,
            'granted_count' => $grantedCount,
            'permissions' => $perms
        ];
    }
    $stmt->close();
}

$roles = Roles::getAllNames();

include('admin_elements/page_header.php');
?>

<style>
    .debug-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem;
        border-radius: 0.5rem;
        margin-bottom: 2rem;
    }
    
    .debug-header h2 {
        margin-bottom: 0.5rem;
        font-size: 1.75rem;
        font-weight: 700;
    }
    
    .debug-header p {
        margin-bottom: 0;
        opacity: 0.9;
        font-size: 0.95rem;
    }
    
    .filter-card {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        padding: 1.5rem;
        border-radius: 0.5rem;
        margin-bottom: 2rem;
    }
    
    .filter-card .form-label {
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
    }
    
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .info-card {
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 0.5rem;
        padding: 1.5rem;
        transition: all 0.3s ease;
    }
    
    .info-card:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .info-card-header {
        display: flex;
        align-items: center;
        margin-bottom: 1rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #f0f0f0;
    }
    
    .info-card-icon {
        font-size: 1.5rem;
        margin-right: 0.75rem;
        width: 2.5rem;
        height: 2.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.5rem;
    }
    
    .info-card-title {
        font-size: 0.9rem;
        font-weight: 600;
        color: #666;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin: 0;
    }
    
    .info-card-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #2c3e50;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .permission-badge {
        display: inline-block;
        padding: 0.5rem 0.75rem;
        border-radius: 0.25rem;
        font-size: 0.85rem;
        font-weight: 600;
        margin-right: 0.5rem;
        margin-bottom: 0.5rem;
        white-space: nowrap;
    }
    
    .permission-granted {
        background-color: #d4edda;
        color: #155724;
        border-left: 3px solid #28a745;
    }
    
    .permission-denied {
        background-color: #f8d7da;
        color: #721c24;
        border-left: 3px solid #dc3545;
    }
    
    .permission-icon {
        margin-right: 0.25rem;
    }
    
    .permissions-section {
        margin-top: 1.5rem;
    }
    
    .permissions-section h6 {
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 1rem;
        font-size: 0.95rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: flex;
        align-items: center;
    }
    
    .permissions-list {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    
    .empty-state {
        text-align: center;
        padding: 3rem 1.5rem;
        background: #f8f9fa;
        border-radius: 0.5rem;
        border: 2px dashed #dee2e6;
    }
    
    .empty-state i {
        font-size: 3rem;
        color: #ccc;
        margin-bottom: 1rem;
        display: block;
    }
    
    .empty-state p {
        color: #999;
        margin: 0;
    }
    
    .stat-badge {
        display: inline-block;
        background: #e7f3ff;
        color: #0066cc;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        font-weight: 600;
        font-size: 0.85rem;
        margin-left: 0.5rem;
    }
    
    .comparison-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .comparison-table thead {
        background: #f8f9fa;
    }
    
    .comparison-table th {
        padding: 1rem;
        text-align: left;
        font-weight: 600;
        border-bottom: 2px solid #dee2e6;
        color: #2c3e50;
    }
    
    .comparison-table td {
        padding: 1rem;
        border-bottom: 1px solid #dee2e6;
    }
    
    .comparison-table tbody tr:hover {
        background: #f9f9f9;
    }
    
    .status-check {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.5rem;
        height: 1.5rem;
        border-radius: 0.25rem;
        font-size: 0.8rem;
        font-weight: 700;
    }
    
    .status-yes {
        background: #d4edda;
        color: #155724;
    }
    
    .status-no {
        background: #f8d7da;
        color: #721c24;
    }
</style>

<div class="content-wrapper">
    <div class="content-inner">
        <div class="content">
            
            <!-- Header -->
            <div class="debug-header">
                <h2>
                    <i class="ph-shield-check"></i> Permissions Debug Tool
                </h2>
                <p>Inspect and diagnose the role-based permission system</p>
            </div>

            <!-- Filter Form -->
            <div class="filter-card">
                <form method="get" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">
                            <i class="ph-folder"></i> Module Slug
                        </label>
                        <input 
                            type="text" 
                            name="module" 
                            class="form-control" 
                            value="<?php echo e($moduleSlug); ?>" 
                            placeholder="e.g. companies, customers, invoices"
                            list="moduleList"
                        >
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">
                            <i class="ph-user"></i> Role
                        </label>
                        <select name="role_id" class="form-select">
                            <option value="0">Select a role...</option>
                            <?php foreach ($roles as $id => $name): ?>
                                <option value="<?php echo (int)$id; ?>" <?php echo $roleId === (int)$id ? 'selected' : ''; ?>>
                                    <?php echo e($name); ?> <span class="text-muted">(ID: <?php echo (int)$id; ?>)</span>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="ph-magnifying-glass"></i> Check Permissions
                        </button>
                        <a href="permissions_debug.php" class="btn btn-outline-secondary">
                            <i class="ph-arrow-clockwise"></i> Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- Results -->
            <?php if ($moduleSlug === '' && $roleId > 0 && !empty($allModulesData)): ?>
                
                <!-- All Modules Overview for Selected Role -->
                <div class="info-grid">
                    <div class="info-card">
                        <div class="info-card-header">
                            <div class="info-card-icon" style="background: #fff3cd; color: #856404;">
                                <i class="ph-user"></i>
                            </div>
                            <h6 class="info-card-title">Selected Role</h6>
                        </div>
                        <div class="info-card-value"><?php echo e($roles[$roleId] ?? 'Unknown'); ?></div>
                        <small class="text-muted">ID: <?php echo $roleId; ?></small>
                    </div>

                    <div class="info-card">
                        <div class="info-card-header">
                            <div class="info-card-icon" style="background: #e2e3e5; color: #383d41;">
                                <i class="ph-list"></i>
                            </div>
                            <h6 class="info-card-title">Total Modules</h6>
                        </div>
                        <div class="info-card-value"><?php echo count($allModulesData); ?></div>
                        <small class="text-muted">In system</small>
                    </div>

                    <div class="info-card">
                        <div class="info-card-header">
                            <div class="info-card-icon" style="background: #d4edda; color: #155724;">
                                <i class="ph-check-circle"></i>
                            </div>
                            <h6 class="info-card-title">Total Granted Permissions</h6>
                        </div>
                        <div class="info-card-value">
                            <?php 
                            $totalGranted = 0;
                            $totalDefined = 0;
                            foreach ($allModulesData as $mod) {
                                $totalGranted += $mod['granted_count'];
                                $totalDefined += $mod['total_permissions'];
                            }
                            echo $totalGranted . ' / ' . $totalDefined;
                            ?>
                        </div>
                        <small class="text-muted">
                            <?php 
                            if ($totalDefined > 0) {
                                $percentage = round(($totalGranted / $totalDefined) * 100);
                                echo $percentage . '% complete';
                            }
                            ?>
                        </small>
                    </div>
                </div>

                <!-- All Modules Table -->
                <div class="card">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="ph-table"></i> All Modules & Permission Status
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="comparison-table">
                                <thead>
                                    <tr>
                                        <th>Module</th>
                                        <th style="text-align: center;">Granted Permissions</th>
                                        <th style="text-align: center;">Total Permissions</th>
                                        <th style="text-align: center;">Completion</th>
                                        <th style="text-align: center;">Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($allModulesData as $mod): ?>
                                        <?php 
                                        $percentage = $mod['total_permissions'] > 0 
                                            ? round(($mod['granted_count'] / $mod['total_permissions']) * 100)
                                            : 0;
                                        $isComplete = $mod['granted_count'] === $mod['total_permissions'] && $mod['total_permissions'] > 0;
                                        ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo e($mod['name']); ?></strong>
                                                <br>
                                                <small class="text-muted">Slug: <code><?php echo e($mod['slug']); ?></code></small>
                                            </td>
                                            <td style="text-align: center;">
                                                <span class="badge bg-success"><?php echo $mod['granted_count']; ?></span>
                                            </td>
                                            <td style="text-align: center;">
                                                <span class="badge bg-secondary"><?php echo $mod['total_permissions']; ?></span>
                                            </td>
                                            <td style="text-align: center;">
                                                <div style="width: 100px; margin: 0 auto;">
                                                    <div class="progress" style="height: 20px;">
                                                        <div 
                                                            class="progress-bar <?php echo $isComplete ? 'bg-success' : ($percentage >= 50 ? 'bg-warning' : 'bg-danger'); ?>" 
                                                            role="progressbar" 
                                                            style="width: <?php echo $percentage; ?>%" 
                                                            aria-valuenow="<?php echo $percentage; ?>" 
                                                            aria-valuemin="0" 
                                                            aria-valuemax="100">
                                                            <?php echo $percentage; ?>%
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td style="text-align: center;">
                                                <a href="?module=<?php echo urlencode($mod['slug']); ?>&role_id=<?php echo $roleId; ?>" class="btn btn-sm btn-info">
                                                    <i class="ph-magnifying-glass"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            <?php elseif ($moduleSlug !== ''): ?>
                
                <?php if ($moduleId > 0): ?>
                    
                    <!-- Summary Cards -->
                    <div class="info-grid">
                        <div class="info-card">
                            <div class="info-card-header">
                                <div class="info-card-icon" style="background: #e7f3ff; color: #0066cc;">
                                    <i class="ph-folder"></i>
                                </div>
                                <h6 class="info-card-title">Module</h6>
                            </div>
                            <div class="info-card-value"><?php echo e($moduleName); ?></div>
                            <small class="text-muted">ID: <?php echo $moduleId; ?></small>
                        </div>

                        <?php if ($roleId > 0): ?>
                            <div class="info-card">
                                <div class="info-card-header">
                                    <div class="info-card-icon" style="background: #fff3cd; color: #856404;">
                                        <i class="ph-user"></i>
                                    </div>
                                    <h6 class="info-card-title">Role</h6>
                                </div>
                                <div class="info-card-value"><?php echo e($roles[$roleId] ?? 'Unknown'); ?></div>
                                <small class="text-muted">ID: <?php echo $roleId; ?></small>
                            </div>

                            <div class="info-card">
                                <div class="info-card-header">
                                    <div class="info-card-icon" style="background: #d4edda; color: #155724;">
                                        <i class="ph-check-circle"></i>
                                    </div>
                                    <h6 class="info-card-title">Granted</h6>
                                </div>
                                <div class="info-card-value"><?php echo count($grantedPerms); ?> / <?php echo count($definedPerms); ?></div>
                                <small class="text-muted">
                                    <?php 
                                    if (count($definedPerms) > 0) {
                                        $percentage = round((count($grantedPerms) / count($definedPerms)) * 100);
                                        echo $percentage . '% complete';
                                    } else {
                                        echo "No permissions defined";
                                    }
                                    ?>
                                </small>
                            </div>
                        <?php else: ?>
                            <div class="info-card">
                                <div class="info-card-header">
                                    <div class="info-card-icon" style="background: #e2e3e5; color: #383d41;">
                                        <i class="ph-list"></i>
                                    </div>
                                    <h6 class="info-card-title">Defined Permissions</h6>
                                </div>
                                <div class="info-card-value"><?php echo count($definedPerms); ?></div>
                                <small class="text-muted">Total in module</small>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($roleId > 0): ?>
                        
                        <!-- Comparison Table -->
                        <div class="card">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">
                                    <i class="ph-table"></i> Permission Comparison
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="comparison-table">
                                        <thead>
                                            <tr>
                                                <th>Permission</th>
                                                <th style="width: 120px; text-align: center;">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($definedPerms as $perm): ?>
                                                <tr>
                                                    <td>
                                                        <code><?php echo e($perm); ?></code>
                                                    </td>
                                                    <td style="text-align: center;">
                                                        <?php if (in_array($perm, $grantedPerms)): ?>
                                                            <span class="status-check status-yes" title="Granted">
                                                                <i class="ph-check"></i>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="status-check status-no" title="Not Granted">
                                                                <i class="ph-x"></i>
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                    <?php else: ?>
                        
                        <!-- Permissions Overview -->
                        <div class="card">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">
                                    <i class="ph-key"></i> Defined Permissions
                                </h6>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($definedPerms)): ?>
                                    <div class="permissions-list">
                                        <?php foreach ($definedPerms as $perm): ?>
                                            <span class="permission-badge permission-granted">
                                                <i class="ph-check-circle permission-icon"></i>
                                                <?php echo e($perm); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <i class="ph-warning"></i>
                                        <p>No permissions defined for this module</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    <?php endif; ?>

                <?php else: ?>
                    
                    <!-- Module Not Found -->
                    <div class="alert alert-warning alert-icon" role="alert">
                        <i class="ph-warning-circle"></i>
                        <div>
                            <strong>Module not found</strong>
                            <p class="mb-0">The module slug "<code><?php echo e($moduleSlug); ?></code>" does not exist. Check the spelling or choose from available modules.</p>
                        </div>
                    </div>

                <?php endif; ?>

            <?php else: ?>
                
                <!-- Empty State -->
                <div class="empty-state">
                    <i class="ph-magnifying-glass"></i>
                    <h5 class="mt-3">Start your permission inspection</h5>
                    <p>
                        <?php if ($roleId > 0): ?>
                            Select a module slug to see detailed permissions for <strong><?php echo e($roles[$roleId] ?? 'this role'); ?></strong>, 
                            or leave it empty to see all modules.
                        <?php else: ?>
                            Select a role (and optionally a module) to inspect permissions
                        <?php endif; ?>
                    </p>
                </div>

            <?php endif; ?>

            <!-- Info Section -->
            <div class="card mt-4 bg-light border-light">
                <div class="card-body">
                    <h6 class="mb-3">
                        <i class="ph-info"></i> System Information
                    </h6>
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Total Roles:</strong> <?php echo count($roles); ?></p>
                            <p class="mb-0"><strong>Access Level:</strong> System Admin only</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Architecture:</strong> Three-table mapping (modules → permissions → mapping)</p>
                            <p class="mb-0"><strong>Last Updated:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <?php include('admin_elements/copyright.php'); ?>
    </div>
</div>

<?php include('admin_elements/admin_footer.php'); ?>
