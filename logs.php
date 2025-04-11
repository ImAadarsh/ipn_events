<?php
// Include header
include 'includes/header.php';

// Check if the user is an admin
if (!isAdmin()) {
    echo '<div class="alert alert-danger">You do not have permission to access this page.</div>';
    include 'includes/footer.php';
    exit();
}

// Get log files from the logs directory
$logDir = __DIR__ . '/includes/logs';
$logFiles = [];

if (file_exists($logDir)) {
    $files = scandir($logDir);
    foreach ($files as $file) {
        if (strpos($file, 'activity_') === 0) {
            $logFiles[] = $file;
        }
    }
    // Sort files by date in descending order (newest first)
    rsort($logFiles);
}

// Get the selected log file or use the most recent one by default
$selectedLog = isset($_GET['log']) ? $_GET['log'] : (count($logFiles) > 0 ? $logFiles[0] : '');
$logEntries = [];

if (!empty($selectedLog) && file_exists($logDir . '/' . $selectedLog)) {
    $logContent = file_get_contents($logDir . '/' . $selectedLog);
    $lines = explode("\n", $logContent);
    
    foreach ($lines as $line) {
        if (empty(trim($line))) continue;
        
        // Parse the log entry format: [timestamp] username | ip | action | details | status
        if (preg_match('/\[(.*?)\] (.*?) \| (.*?) \| (.*?) \| (.*?) \| (.*)/', $line, $matches)) {
            $logEntries[] = [
                'timestamp' => $matches[1],
                'username' => $matches[2],
                'ip' => $matches[3],
                'action' => $matches[4],
                'details' => $matches[5],
                'status' => $matches[6]
            ];
        }
    }
}

// Filter options
$users = [];
$actions = [];

foreach ($logEntries as $entry) {
    if (!in_array($entry['username'], $users)) {
        $users[] = $entry['username'];
    }
    if (!in_array($entry['action'], $actions)) {
        $actions[] = $entry['action'];
    }
}

// Apply filters if set
$filteredEntries = [];
$filterUser = isset($_GET['user']) ? $_GET['user'] : '';
$filterAction = isset($_GET['action']) ? $_GET['action'] : '';
$filterStatus = isset($_GET['status']) ? $_GET['status'] : '';

foreach ($logEntries as $entry) {
    $includeEntry = true;
    
    if (!empty($filterUser) && $entry['username'] !== $filterUser) {
        $includeEntry = false;
    }
    
    if (!empty($filterAction) && $entry['action'] !== $filterAction) {
        $includeEntry = false;
    }
    
    if (!empty($filterStatus) && $entry['status'] !== $filterStatus) {
        $includeEntry = false;
    }
    
    if ($includeEntry) {
        $filteredEntries[] = $entry;
    }
}

// Use filtered entries if filters are applied, otherwise use all entries
$displayEntries = (count($filteredEntries) > 0 || !empty($filterUser) || !empty($filterAction) || !empty($filterStatus)) 
    ? $filteredEntries 
    : $logEntries;
?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="card custom-card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i> User Activity Logs</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <form action="" method="get" class="mb-3">
                                <label for="log" class="form-label">Select Log File:</label>
                                <select name="log" id="log" class="form-select" onchange="this.form.submit()">
                                    <?php foreach($logFiles as $file): ?>
                                        <option value="<?php echo $file; ?>" <?php echo ($selectedLog === $file) ? 'selected' : ''; ?>>
                                            <?php echo str_replace('activity_', '', str_replace('.txt', '', $file)); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </div>
                        <div class="col-md-8">
                            <form action="" method="get" class="row g-3">
                                <input type="hidden" name="log" value="<?php echo $selectedLog; ?>">
                                
                                <div class="col-md-4">
                                    <label for="user" class="form-label">Filter by User:</label>
                                    <select name="user" id="user" class="form-select">
                                        <option value="">All Users</option>
                                        <?php foreach($users as $user): ?>
                                            <option value="<?php echo $user; ?>" <?php echo ($filterUser === $user) ? 'selected' : ''; ?>>
                                                <?php echo $user; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-4">
                                    <label for="action" class="form-label">Filter by Action:</label>
                                    <select name="action" id="action" class="form-select">
                                        <option value="">All Actions</option>
                                        <?php foreach($actions as $action): ?>
                                            <option value="<?php echo $action; ?>" <?php echo ($filterAction === $action) ? 'selected' : ''; ?>>
                                                <?php echo $action; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-4">
                                    <label for="status" class="form-label">Filter by Status:</label>
                                    <select name="status" id="status" class="form-select">
                                        <option value="">All Statuses</option>
                                        <option value="success" <?php echo ($filterStatus === 'success') ? 'selected' : ''; ?>>Success</option>
                                        <option value="failure" <?php echo ($filterStatus === 'failure') ? 'selected' : ''; ?>>Failure</option>
                                    </select>
                                </div>
                                
                                <div class="col-12">
                                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter me-2"></i>Apply Filters</button>
                                    <a href="logs.php?log=<?php echo $selectedLog; ?>" class="btn btn-sm btn-secondary"><i class="fas fa-times me-2"></i>Clear Filters</a>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered" id="logs-table">
                            <thead>
                                <tr>
                                    <th>Timestamp</th>
                                    <th>Username</th>
                                    <th>IP Address</th>
                                    <th>Action</th>
                                    <th>Details</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($displayEntries) > 0): ?>
                                    <?php foreach($displayEntries as $entry): ?>
                                        <tr>
                                            <td><?php echo $entry['timestamp']; ?></td>
                                            <td><?php echo $entry['username']; ?></td>
                                            <td><?php echo $entry['ip']; ?></td>
                                            <td>
                                                <?php 
                                                    $badge = '';
                                                    switch($entry['action']) {
                                                        case 'Login':
                                                            $badge = 'bg-info';
                                                            break;
                                                        case 'Logout':
                                                            $badge = 'bg-warning';
                                                            break;
                                                        case 'Page Access':
                                                            $badge = 'bg-secondary';
                                                            break;
                                                        case 'Database Insert':
                                                            $badge = 'bg-success';
                                                            break;
                                                        case 'Database Update':
                                                            $badge = 'bg-primary';
                                                            break;
                                                        case 'Database Delete':
                                                            $badge = 'bg-danger';
                                                            break;
                                                        default:
                                                            $badge = 'bg-dark';
                                                    }
                                                ?>
                                                <span class="badge <?php echo $badge; ?>"><?php echo $entry['action']; ?></span>
                                            </td>
                                            <td><?php echo $entry['details']; ?></td>
                                            <td>
                                                <span class="badge <?php echo ($entry['status'] === 'success') ? 'bg-success' : 'bg-danger'; ?>">
                                                    <?php echo ucfirst($entry['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No log entries found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#logs-table').DataTable({
        responsive: true,
        order: [[0, 'desc']], // Sort by timestamp descending
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        dom: '<"d-flex justify-content-between align-items-center small mb-2"lf>rt<"d-flex justify-content-between align-items-center small mt-2"ip>',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]
    });
});
</script>

<?php
// Include footer
include 'includes/footer.php';
?> 