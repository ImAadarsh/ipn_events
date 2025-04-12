<?php
// Include header
include 'includes/header.php';

// Check if user has permission to view leaderssummit data
if (!canViewEvent('leaderssummit')) {
    echo '<div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            You do not have permission to view Leaderssummit data. 
            Please contact an administrator if you need access.
          </div>';
    include 'includes/footer.php';
    exit();
}


// Check if viewing a specific registration
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $summit = fetch_row($conn, "SELECT * FROM leaderssummit WHERE id = $id");
    
    if (!$summit) {
        echo '<div class="alert alert-danger">Record not found!</div>';
        include 'includes/footer.php';
        exit();
    }
}

// Get all registrations with optional filters
$where_clause = "1=1"; // Default condition that's always true

// Apply filters if submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filter'])) {
    // Filter by name
    if (!empty($_POST['name'])) {
        $name = clean($conn, $_POST['name']);
        $where_clause .= " AND name LIKE '%$name%'";
    }
    
    // Filter by city
    if (!empty($_POST['city'])) {
        $city = clean($conn, $_POST['city']);
        $where_clause .= " AND city LIKE '%$city%'";
    }
    
    // Filter by institute
    if (!empty($_POST['institute'])) {
        $institute = clean($conn, $_POST['institute']);
        $where_clause .= " AND institute LIKE '%$institute%'";
    }
    
    // Filter by designation
    if (!empty($_POST['designation'])) {
        $designation = clean($conn, $_POST['designation']);
        $where_clause .= " AND designation LIKE '%$designation%'";
    }
    
    // Filter by date range
    if (!empty($_POST['date_from']) && !empty($_POST['date_to'])) {
        $date_from = clean($conn, $_POST['date_from']);
        $date_to = clean($conn, $_POST['date_to']);
        $where_clause .= " AND created_at BETWEEN '$date_from' AND '$date_to 23:59:59'";
    } else if (!empty($_POST['date_from'])) {
        $date_from = clean($conn, $_POST['date_from']);
        $where_clause .= " AND created_at >= '$date_from'";
    } else if (!empty($_POST['date_to'])) {
        $date_to = clean($conn, $_POST['date_to']);
        $where_clause .= " AND created_at <= '$date_to 23:59:59'";
    }
}

// Get all cities, institutes, designations for filter dropdowns
$cities = fetch_all($conn, "SELECT DISTINCT city FROM leaderssummit WHERE city IS NOT NULL AND city != '' ORDER BY city");
$institutes = fetch_all($conn, "SELECT DISTINCT institute FROM leaderssummit WHERE institute IS NOT NULL AND institute != '' ORDER BY institute");
$designations = fetch_all($conn, "SELECT DISTINCT designation FROM leaderssummit WHERE designation IS NOT NULL AND designation != '' ORDER BY designation");

// Get registrations with applied filters
$summits = fetch_all($conn, "SELECT * FROM leaderssummit WHERE $where_clause ORDER BY created_at DESC");

// Get counts by city for chart
$city_counts = fetch_all($conn, "SELECT city, COUNT(*) as count FROM leaderssummit GROUP BY city ORDER BY count DESC LIMIT 10");
$city_labels = [];
$city_data = [];

foreach ($city_counts as $row) {
    $city_labels[] = $row['city'] ?: 'Unknown';
    $city_data[] = (int)$row['count'];
}

// Get counts by designation for chart
$designation_counts = fetch_all($conn, "SELECT designation, COUNT(*) as count FROM leaderssummit GROUP BY designation ORDER BY count DESC LIMIT 10");
$designation_labels = [];
$designation_data = [];

foreach ($designation_counts as $row) {
    $designation_labels[] = $row['designation'] ?: 'Unknown';
    $designation_data[] = (int)$row['count'];
}

// Get registration trend by month for current year
$current_year = date('Y');
$monthly_trend = fetch_all($conn, "SELECT MONTH(created_at) as month, COUNT(*) as count FROM leaderssummit 
                               WHERE YEAR(created_at) = '$current_year' 
                               GROUP BY MONTH(created_at) 
                               ORDER BY month");

$months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
$monthly_data = array_fill(0, 12, 0);

foreach ($monthly_trend as $row) {
    $month_index = $row['month'] - 1;
    $monthly_data[$month_index] = (int)$row['count'];
}
?>

<?php if (isset($summit)): ?>
<!-- Single Registration View -->
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Nepal Summit Registration Details</h5>
            <a href="leaderssummit.php" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to List
            </a>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-muted mb-3">Personal Information</h6>
                    <table class="table table-striped">
                        <tr>
                            <th width="150">Name</th>
                            <td><?php echo $summit['name']; ?></td>
                        </tr>
                        <tr>
                            <th>Designation</th>
                            <td><?php echo $summit['designation']; ?></td>
                        </tr>
                        <tr>
                            <th>City</th>
                            <td><?php echo $summit['city']; ?></td>
                        </tr>
                        <tr>
                            <th>Institution</th>
                            <td><?php echo $summit['institute']; ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted mb-3">Contact Information</h6>
                    <table class="table table-striped">
                        <tr>
                            <th width="150">Phone</th>
                            <td><?php echo $summit['phone']; ?></td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td><?php echo $summit['email'] ?? $summit['mail']; ?></td>
                        </tr>
                        <tr>
                            <th>Registered On</th>
                            <td><?php echo date('d M Y H:i', strtotime($summit['created_at'])); ?></td>
                        </tr>
                        <tr>
                            <th>Last Updated</th>
                            <td><?php echo date('d M Y H:i', strtotime($summit['updated_at'])); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<!-- Registrations List View -->
<div class="container-fluid">
    <!-- Page Statistics -->
    <div class="row mb-4">
        <div class="col-md-4 col-xl-4">
            <div class="stats-card text-center">
                <i class="fas fa-users"></i>
                <h3><?php echo count($summits); ?></h3>
                <p>Total Registrations</p>
            </div>
        </div>
        <div class="col-md-4 col-xl-4">
            <div class="stats-card text-center">
                <i class="fas fa-building"></i>
                <h3><?php echo count($institutes); ?></h3>
                <p>Unique Institutions</p>
            </div>
        </div>
        <div class="col-md-4 col-xl-4">
            <div class="stats-card text-center">
                <i class="fas fa-map-marker-alt"></i>
                <h3><?php echo count($cities); ?></h3>
                <p>Cities Represented</p>
            </div>
        </div>
    </div>
    
    <!-- Charts Row -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
          
                    <h5 class="mb-0">Registrations by City</h5>
    
        </div>
                <div class="card-body">
                    <canvas id="cityChart" height="250"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
        
                    <h5 class="mb-0">Registrations by Designation</h5>
    
            

        </div>
                <div class="card-body">
                    <canvas id="designationChart" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filter Card -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Filter Registrations</h5>
        </div>
        <div class="card-body">
            <form method="post" class="row g-3">
                <div class="col-md-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo $_POST['name'] ?? ''; ?>">
                </div>
                <div class="col-md-3">
                    <label for="city" class="form-label">City</label>
                    <select class="form-select" id="city" name="city">
                        <option value="">All Cities</option>
                        <?php foreach ($cities as $city): ?>
                            <option value="<?php echo $city['city']; ?>" <?php echo (isset($_POST['city']) && $_POST['city'] == $city['city']) ? 'selected' : ''; ?>>
                                <?php echo $city['city']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="institute" class="form-label">Institution</label>
                    <select class="form-select" id="institute" name="institute">
                        <option value="">All Institutions</option>
                        <?php foreach ($institutes as $inst): ?>
                            <option value="<?php echo $inst['institute']; ?>" <?php echo (isset($_POST['institute']) && $_POST['institute'] == $inst['institute']) ? 'selected' : ''; ?>>
                                <?php echo $inst['institute']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="designation" class="form-label">Designation</label>
                    <select class="form-select" id="designation" name="designation">
                        <option value="">All Designations</option>
                        <?php foreach ($designations as $designation): ?>
                            <option value="<?php echo $designation['designation']; ?>" <?php echo (isset($_POST['designation']) && $_POST['designation'] == $designation['designation']) ? 'selected' : ''; ?>>
                                <?php echo $designation['designation']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="date_from" class="form-label">Date From</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $_POST['date_from'] ?? ''; ?>">
                </div>
                <div class="col-md-3">
                    <label for="date_to" class="form-label">Date To</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $_POST['date_to'] ?? ''; ?>">
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button type="submit" name="filter" class="btn btn-primary me-2">
                        <i class="fas fa-filter me-2"></i>Apply Filters
                    </button>
                    <a href="leaderssummit.php" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i>Clear Filters
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Data Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Nepal Summit Nepal Registrations</h5>
            
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover datatable-export">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Designation</th>
                            <th>Institution</th>
                            <th>City</th>
                            <th>Contact</th>
                            <th>Email</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($summits as $summit): ?>
                        <tr>
                            <td><?php echo $summit['id']; ?></td>
                            <td><?php echo $summit['name']; ?></td>
                            <td><?php echo $summit['designation']; ?></td>
                            <td><?php echo $summit['institute']; ?></td>
                            <td><?php echo $summit['city']; ?></td>
                            <td><?php echo $summit['phone']; ?></td>
                            <td><?php echo $summit['email'] ?? $summit['mail']; ?></td>
                            <td data-sort="<?php echo strtotime($summit['created_at']); ?>">
                                <?php echo date('d M Y', strtotime($summit['created_at'])); ?>
                            </td>
                            <td>
                                <a href="leaderssummit.php?id=<?php echo $summit['id']; ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // City Chart
    const cityChartCtx = document.getElementById('cityChart').getContext('2d');
    new Chart(cityChartCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($city_labels); ?>,
            datasets: [{
                label: 'Registrations by City',
                data: <?php echo json_encode($city_data); ?>,
                backgroundColor: 'rgba(33, 150, 243, 0.5)',
                borderColor: 'rgba(33, 150, 243, 1)',
                borderWidth: 1
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    title: {
                        display: true,
                        text: 'City'
                    }
                },
                x: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of Registrations'
                    }
                }
            }
        }
    });
    
    // Designation Chart
    const designationChartCtx = document.getElementById('designationChart').getContext('2d');
    new Chart(designationChartCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($designation_labels); ?>,
            datasets: [{
                label: 'Registrations by Designation',
                data: <?php echo json_encode($designation_data); ?>,
                backgroundColor: [
                    'rgba(33, 150, 243, 0.7)',
                    'rgba(76, 175, 80, 0.7)',
                    'rgba(255, 193, 7, 0.7)',
                    'rgba(244, 67, 54, 0.7)',
                    'rgba(156, 39, 176, 0.7)',
                    'rgba(63, 81, 181, 0.7)',
                    'rgba(0, 188, 212, 0.7)',
                    'rgba(255, 87, 34, 0.7)',
                    'rgba(121, 85, 72, 0.7)',
                    'rgba(158, 158, 158, 0.7)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'right'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
});

    // Initialize DataTable with export buttons
    var table = $('.datatable-export').DataTable({
        dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        order: [[7, 'desc']], // Sort by date
        responsive: true,
        buttons: [
            {
                extend: 'csvHtml5',
                text: 'Export CSV',
                filename: 'leaderssummit_export_<?php echo date("Y-m-d"); ?>',
                className: 'd-none',
                exportOptions: {
                    columns: 'visible'
                }
            },
            {
                extend: 'pdfHtml5',
                text: 'Export PDF',
                filename: 'leaderssummit_export_<?php echo date("Y-m-d"); ?>',
                className: 'd-none',
                exportOptions: {
                    columns: 'visible'
                },
                customize: function(doc) {
                    doc.content.splice(0, 1, {
                        text: 'IPN Foundation - Leaderssummit Registrations',
                        fontSize: 16,
                        alignment: 'center',
                        margin: [0, 0, 0, 12]
                    });
                }
            },
            {
                extend: 'excelHtml5',
                text: 'Export Excel',
                filename: 'leaderssummit_export_<?php echo date("Y-m-d"); ?>',
                className: 'd-none',
                exportOptions: {
                    columns: 'visible'
                }
            }
        ]
    });
    
    <?php if (canExportEvent('leaderssummit')): ?>
    // Bind export buttons
    document.getElementById('exportCSV').addEventListener('click', function() {
        table.button('.buttons-csv').trigger();
    });
    
    document.getElementById('exportPDF').addEventListener('click', function() {
        table.button('.buttons-pdf').trigger();
    });
    
    document.getElementById('exportExcel').addEventListener('click', function() {
        table.button('.buttons-excel').trigger();
    });
    <?php endif; ?>
</script>
<?php endif; ?>

<?php
// Include footer
include 'includes/footer.php';
?> 