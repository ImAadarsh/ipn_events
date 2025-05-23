<?php
// Include header
include 'includes/header.php';

// Check if user has permission to view conclaves
if (!canViewEvent('conclaves')) {
    echo '<div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            You do not have permission to view conclave data. 
            Please contact an administrator if you need access.
          </div>';
    include 'includes/footer.php';
    exit();
}

// Check if viewing a specific conclave
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $conclave = fetch_row($conn, "SELECT * FROM conclaves WHERE id = $id");
    
    if (!$conclave) {
        echo '<div class="alert alert-danger">Record not found!</div>';
        include 'includes/footer.php';
        exit();
    }
}

// Get all conclaves with optional filters
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

// Get all cities, institutes for filter dropdowns
$cities = fetch_all($conn, "SELECT DISTINCT city FROM conclaves WHERE city IS NOT NULL AND city != '' ORDER BY city");
$institutes = fetch_all($conn, "SELECT DISTINCT institute FROM conclaves WHERE institute IS NOT NULL AND institute != '' ORDER BY institute");

// Get conclaves with applied filters
$conclaves = fetch_all($conn, "SELECT * FROM conclaves WHERE $where_clause ORDER BY created_at DESC");

// Get counts by city for chart
$city_counts = fetch_all($conn, "SELECT city, COUNT(*) as count FROM conclaves GROUP BY city ORDER BY count DESC LIMIT 10");
$city_labels = [];
$city_data = [];

foreach ($city_counts as $row) {
    $city_labels[] = $row['city'] ?: 'Unknown';
    $city_data[] = (int)$row['count'];
}

// Get registration trend by month for current year
$current_year = date('Y');
$monthly_trend = fetch_all($conn, "SELECT MONTH(created_at) as month, COUNT(*) as count FROM conclaves 
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

<?php if (isset($conclave)): ?>
<!-- Single Conclave View -->
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Conclave Registration Details</h5>
            <a href="conclaves.php" class="btn btn-sm btn-outline-secondary">
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
                            <td><?php echo $conclave['name']; ?></td>
                        </tr>
                        <tr>
                            <th>Designation</th>
                            <td><?php echo $conclave['designation']; ?></td>
                        </tr>
                        <tr>
                            <th>City</th>
                            <td><?php echo $conclave['city']; ?></td>
                        </tr>
                        <tr>
                            <th>Institution</th>
                            <td><?php echo $conclave['institute']; ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted mb-3">Contact Information</h6>
                    <table class="table table-striped">
                        <tr>
                            <th width="150">Phone</th>
                            <td><?php echo $conclave['phone']; ?></td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td><?php echo $conclave['email'] ?? $conclave['mail']; ?></td>
                        </tr>
                        <tr>
                            <th>Registered On</th>
                            <td><?php echo date('d M Y H:i', strtotime($conclave['created_at'])); ?></td>
                        </tr>
                        <tr>
                            <th>Last Updated</th>
                            <td><?php echo date('d M Y H:i', strtotime($conclave['updated_at'])); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<!-- Conclaves List View -->
<div class="container-fluid">
    <!-- Page Statistics -->
    <div class="row mb-4">
        <div class="col-md-4 col-xl-4">
            <div class="stats-card text-center">
                <i class="fas fa-users"></i>
                <h3><?php echo count($conclaves); ?></h3>
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
                <div class="card-header">
                    <h5 class="mb-0">Registrations by City</h5>
                </div>
                <div class="card-body" style="height: 300px;">
                    <canvas id="cityChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Monthly Trend (<?php echo $current_year; ?>)</h5>
                </div>
                <div class="card-body" style="height: 300px;">
                    <canvas id="monthlyTrendChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filter Card -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Filter Registrations</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="" class="row g-3">
                <div class="col-md-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control form-control-sm" id="name" name="name" value="<?php echo isset($_POST['name']) ? $_POST['name'] : ''; ?>">
                </div>
                <div class="col-md-3">
                    <label for="city" class="form-label">City</label>
                    <select style="height: 45px;" class="form-select form-select-sm" id="city" name="city">
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
                    <select style="height: 45px;"  class="form-select form-select-sm" id="institute" name="institute">
                        <option value="">All Institutions</option>
                        <?php foreach ($institutes as $institute): ?>
                            <option value="<?php echo $institute['institute']; ?>" <?php echo (isset($_POST['institute']) && $_POST['institute'] == $institute['institute']) ? 'selected' : ''; ?>>
                                <?php echo $institute['institute']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="date_from" class="form-label">Date From</label>
                    <input type="date" class="form-control form-control-sm" id="date_from" name="date_from" value="<?php echo isset($_POST['date_from']) ? $_POST['date_from'] : ''; ?>">
                </div>
                <div class="col-md-3">
                    <label for="date_to" class="form-label">Date To</label>
                    <input type="date" class="form-control form-control-sm" id="date_to" name="date_to" value="<?php echo isset($_POST['date_to']) ? $_POST['date_to'] : ''; ?>">
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <button type="submit" name="filter" class="btn btn-primary btn-sm me-2">
                        <i class="fas fa-filter me-1"></i> Apply Filters
                    </button>
                    <a href="conclaves.php" class="btn btn-secondary btn-sm">
                        <i class="fas fa-times me-1"></i> Clear Filters
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Data Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Conclave Registrations</h5>
            
            <?php if (canExportEvent('conclaves')): ?>
            <div class="btn-group">
                <button class="btn btn-sm btn-success" id="exportCSV">
                    <i class="fas fa-file-csv me-1"></i> Export CSV
                </button>
                <button class="btn btn-sm btn-danger" id="exportPDF">
                    <i class="fas fa-file-pdf me-1"></i> Export PDF
                </button>
                <button class="btn btn-sm btn-primary" id="exportExcel">
                    <i class="fas fa-file-excel me-1"></i> Export Excel
                </button>
            </div>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-bordered table-hover" id="conclavesTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Institution</th>
                            <th>City</th>
                            <th>Phone</th>
                            <th>Registered On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($conclaves as $row): ?>
                            <tr>
                                <td><?php echo $row['name']; ?></td>
                                <td><?php echo $row['institute']; ?></td>
                                <td><?php echo $row['city']; ?></td>
                                <td><?php echo $row['phone']; ?></td>
                                <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <a href="conclaves.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">
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

<!-- Chart Initialization -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // City Chart
    var cityCtx = document.getElementById('cityChart').getContext('2d');
    var cityChart = new Chart(cityCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($city_labels); ?>,
            datasets: [{
                label: 'Registrations',
                data: <?php echo json_encode($city_data); ?>,
                backgroundColor: 'rgba(79, 70, 229, 0.7)',
                borderColor: 'rgba(79, 70, 229, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of Registrations'
                    },
                    ticks: {
                        precision: 0
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'City'
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
    
    // Monthly Trend Chart
    var monthlyCtx = document.getElementById('monthlyTrendChart').getContext('2d');
    var monthlyChart = new Chart(monthlyCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($months); ?>,
            datasets: [{
                label: 'Registrations',
                data: <?php echo json_encode($monthly_data); ?>,
                fill: {
                    target: 'origin',
                    above: 'rgba(79, 70, 229, 0.1)'
                },
                borderColor: 'rgba(79, 70, 229, 1)',
                borderWidth: 2,
                pointBackgroundColor: 'rgba(79, 70, 229, 1)',
                pointRadius: 4,
                pointHoverRadius: 6,
                tension: 0.2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of Registrations'
                    },
                    ticks: {
                        precision: 0
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Month'
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
    
    // Initialize DataTable
    var table = $('#conclavesTable').DataTable({
        dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        order: [[4, 'desc']], // Sort by registration date
        responsive: true,
        buttons: [
            {
                extend: 'csvHtml5',
                text: 'Export CSV',
                filename: 'conclaves_export_<?php echo date("Y-m-d"); ?>',
                className: 'd-none',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4]
                }
            },
            {
                extend: 'pdfHtml5',
                text: 'Export PDF',
                filename: 'conclaves_export_<?php echo date("Y-m-d"); ?>',
                className: 'd-none',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4]
                },
                customize: function(doc) {
                    doc.content.splice(0, 1, {
                        text: 'IPN Foundation - Conclave Registrations',
                        fontSize: 16,
                        alignment: 'center',
                        margin: [0, 0, 0, 12]
                    });
                }
            },
            {
                extend: 'excelHtml5',
                text: 'Export Excel',
                filename: 'conclaves_export_<?php echo date("Y-m-d"); ?>',
                className: 'd-none',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4]
                }
            }
        ]
    });
    
    <?php if (canExportEvent('conclaves')): ?>
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
});
</script>
<?php endif; ?>

<?php
// Include footer
include 'includes/footer.php';
?> 