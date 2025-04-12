<?php
// Include header
include 'includes/header.php';

// Check if user is admin
if (!isAdmin()) {
    echo '<div class="alert alert-danger">You do not have permission to access this page.</div>';
    include 'includes/footer.php';
    exit();
}

// Process permission updates
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_permissions'])) {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Clear existing permissions for the user
            $user_id = clean($conn, $_POST['user_id']);
            $conn->query("DELETE FROM user_permissions WHERE user_id = '$user_id'");
            
            // Insert new permissions
            if (isset($_POST['permissions'])) {
                foreach ($_POST['permissions'] as $event_type => $permissions) {
                    $can_view = isset($permissions['view']) ? 1 : 0;
                    $can_export = isset($permissions['export']) ? 1 : 0;
                    
                    $sql = "INSERT INTO user_permissions (user_id, event_type, can_view, can_export) 
                            VALUES ('$user_id', '$event_type', $can_view, $can_export)";
                    $conn->query($sql);
                }
            }
            
            // Commit transaction
            $conn->commit();
            $success_message = "Permissions updated successfully!";
            
            // Log the action
            logUserActivity($_SESSION['username'], 'Update Permissions', "Updated permissions for user: $user_id");
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $error_message = "Error updating permissions: " . $e->getMessage();
            
            // Log the error
            logUserActivity($_SESSION['username'], 'Update Permissions', "Error updating permissions for user: $user_id", 'failure');
        }
    }
}

// Get all users
$users = fetch_all($conn, "SELECT username, role FROM ipn_events_dash_users ORDER BY username");

// Get available event types
$event_types = [
    'conclaves' => 'Conclaves',
    'yuva' => 'YUVA',
    'leaderssummit' => 'Leaders Summit',
    'misb' => 'MISB',
    'ils' => 'ILS',
    'quest' => 'Quest'
];

// Get selected user's permissions if any
$selected_user = isset($_GET['user']) ? clean($conn, $_GET['user']) : '';
$user_permissions = [];

if (!empty($selected_user)) {
    $permissions_result = fetch_all($conn, "SELECT * FROM user_permissions WHERE user_id = '$selected_user'");
    
    foreach ($permissions_result as $permission) {
        $user_permissions[$permission['event_type']] = [
            'view' => $permission['can_view'],
            'export' => $permission['can_export']
        ];
    }
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Manage User Permissions</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Use this page to manage which users can view and export data for each event type.</p>
                    
                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success"><?php echo $success_message; ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-4 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Select User</h6>
                                </div>
                                <div class="card-body">
                                    <div class="list-group">
                                        <?php foreach ($users as $user): ?>
                                            <a href="?user=<?php echo $user['username']; ?>" 
                                               class="list-group-item list-group-item-action <?php echo ($selected_user == $user['username']) ? 'active' : ''; ?>">
                                                <?php echo $user['username']; ?>
                                                <span class="badge bg-<?php echo ($user['role'] == 'admin') ? 'primary' : 'secondary'; ?> float-end">
                                                    <?php echo ucfirst($user['role']); ?>
                                                </span>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-8">
                            <?php if (!empty($selected_user)): ?>
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">Permissions for <?php echo $selected_user; ?></h6>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST" action="">
                                            <input type="hidden" name="user_id" value="<?php echo $selected_user; ?>">
                                            
                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>Event Type</th>
                                                        <th class="text-center">View</th>
                                                        <th class="text-center">Export</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($event_types as $type => $label): ?>
                                                        <tr>
                                                            <td><?php echo $label; ?></td>
                                                            <td class="text-center">
                                                                <div class="form-check d-flex justify-content-center">
                                                                    <input type="checkbox" 
                                                                           class="form-check-input" 
                                                                           name="permissions[<?php echo $type; ?>][view]" 
                                                                           <?php echo (isset($user_permissions[$type]) && $user_permissions[$type]['view']) ? 'checked' : ''; ?>>
                                                                </div>
                                                            </td>
                                                            <td class="text-center">
                                                                <div class="form-check d-flex justify-content-center">
                                                                    <input type="checkbox" 
                                                                           class="form-check-input" 
                                                                           name="permissions[<?php echo $type; ?>][export]" 
                                                                           <?php echo (isset($user_permissions[$type]) && $user_permissions[$type]['export']) ? 'checked' : ''; ?>>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                            
                                            <div class="d-flex justify-content-end mt-3">
                                                <button type="submit" name="update_permissions" class="btn btn-primary">
                                                    <i class="fas fa-save me-1"></i> Save Permissions
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i> Select a user from the list to manage their permissions.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Toggle all checkboxes in a column
    $('.toggle-all').change(function() {
        let column = $(this).data('column');
        let isChecked = $(this).prop('checked');
        
        $(`input[name$="[${column}]"]`).prop('checked', isChecked);
    });
});
</script>

<?php
// Include footer
include 'includes/footer.php';
?> 