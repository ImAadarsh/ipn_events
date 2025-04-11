<?php
// Include header
include 'includes/header.php';

// Check if viewing a specific registration
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $misb = fetch_row($conn, "SELECT * FROM misb WHERE id = $id");
    
    if (!$misb) {
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
    
    // Filter by school name
    if (!empty($_POST['school_name'])) {
        $school_name = clean($conn, $_POST['school_name']);
        $where_clause .= " AND school_name LIKE '%$school_name%'";
    }
    
    // Filter by city
    if (!empty($_POST['city'])) {
        $city = clean($conn, $_POST['city']);
        $where_clause .= " AND city LIKE '%$city%'";
    }
    
    // Filter by principal name
    if (!empty($_POST['principal_name'])) {
        $principal_name = clean($conn, $_POST['principal_name']);
        $where_clause .= " AND principal_name LIKE '%$principal_name%'";
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

// Get all cities, school names, principal names for filter dropdowns
$cities = fetch_all($conn, "SELECT DISTINCT city FROM misb WHERE city IS NOT NULL AND city != '' ORDER BY city");
$school_names = fetch_all($conn, "SELECT DISTINCT school_name FROM misb WHERE school_name IS NOT NULL AND school_name != '' ORDER BY school_name");
$principal_names = fetch_all($conn, "SELECT DISTINCT principal_name FROM misb WHERE principal_name IS NOT NULL AND principal_name != '' ORDER BY principal_name");

// Get registrations with applied filters
$misbs = fetch_all($conn, "SELECT * FROM misb WHERE $where_clause ORDER BY created_at DESC");

// Get counts by city for chart
$city_counts = fetch_all($conn, "SELECT city, COUNT(*) as count FROM misb GROUP BY city ORDER BY count DESC LIMIT 10");
$city_labels = [];
$city_data = [];

foreach ($city_counts as $row) {
    $city_labels[] = $row['city'] ?: 'Unknown';
    $city_data[] = (int)$row['count'];
}

// Get registration trend by month for current year
$current_year = date('Y');
$monthly_trend = fetch_all($conn, "SELECT MONTH(created_at) as month, COUNT(*) as count FROM misb 
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

<?php if (isset($misb)): ?>
<!-- Single Registration View -->
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Impactful School Brands Registration Details</h5>
            <a href="misb.php" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to List
            </a>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-muted mb-3">School Information</h6>
                    <table class="table table-striped">
                        <tr>
                            <th width="150">School Name</th>
                            <td><?php echo $misb['school_name']; ?></td>
                        </tr>
                        <tr>
                            <th>Principal Name</th>
                            <td><?php echo $misb['principal_name']; ?></td>
                        </tr>
                        <tr>
                            <th>Contact Person</th>
                            <td><?php echo $misb['name']; ?></td>
                        </tr>
                        <tr>
                            <th>City</th>
                            <td><?php echo $misb['city']; ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted mb-3">Contact Information</h6>
                    <table class="table table-striped">
                        <tr>
                            <th width="150">Phone</th>
                            <td><?php echo $misb['phone']; ?></td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td><?php echo $misb['email']; ?></td>
                        </tr>
                        <tr>
                            <th>Message</th>
                            <td><?php echo $misb['message']; ?></td>
                        </tr>
                        <tr>
                            <th>Registered On</th>
                            <td><?php echo date('d M Y H:i', strtotime($misb['created_at'])); ?></td>
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
                <h3><?php echo count($misbs); ?></h3>
                <p>Total Registrations</p>
            </div>
        </div>
        <div class="col-md-4 col-xl-4">
            <div class="stats-card text-center">
                <i class="fas fa-school"></i>
                <h3><?php echo count($school_names); ?></h3>
                <p>Unique Schools</p>
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
                <div class="card-body">
                    <canvas id="cityChart" height="250"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Monthly Registration Trend (<?php echo $current_year; ?>)</h5>
                </div>
                <div class="card-body">
                    <canvas id="trendChart" height="250"></canvas>
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
            <form method="post" class="row g-3">
                <div class="col-md-3">
                    <label for="name" class="form-label">Contact Person</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo $_POST['name'] ?? ''; ?>">
                </div>
                <div class="col-md-3">
                    <label for="school_name" class="form-label">School Name</label>
                    <select class="form-select" id="school_name" name="school_name">
                        <option value="">All Schools</option>
                        <?php foreach ($school_names as $school): ?>
                            <option value="<?php echo $school['school_name']; ?>" <?php echo (isset($_POST['school_name']) && $_POST['school_name'] == $school['school_name']) ? 'selected' : ''; ?>>
                                <?php echo $school['school_name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
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
                    <label for="principal_name" class="form-label">Principal</label>
                    <select class="form-select" id="principal_name" name="principal_name">
                        <option value="">All Principals</option>
                        <?php foreach ($principal_names as $principal): ?>
                            <option value="<?php echo $principal['principal_name']; ?>" <?php echo (isset($_POST['principal_name']) && $_POST['principal_name'] == $principal['principal_name']) ? 'selected' : ''; ?>>
                                <?php echo $principal['principal_name']; ?>
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
                    <a href="misb.php" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i>Clear Filters
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Data Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Impactful School Brands Registrations</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover datatable-export">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>School Name</th>
                            <th>Principal</th>
                            <th>Contact Person</th>
                            <th>City</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($misbs as $misb): ?>
                        <tr>
                            <td><?php echo $misb['id']; ?></td>
                            <td><?php echo $misb['school_name']; ?></td>
                            <td><?php echo $misb['principal_name']; ?></td>
                            <td><?php echo $misb['name']; ?></td>
                            <td><?php echo $misb['city']; ?></td>
                            <td><?php echo $misb['email']; ?></td>
                            <td><?php echo $misb['phone']; ?></td>
                            <td data-sort="<?php echo strtotime($misb['created_at']); ?>">
                                <?php echo date('d M Y', strtotime($misb['created_at'])); ?>
                            </td>
                            <td>
                                <a href="misb.php?id=<?php echo $misb['id']; ?>" class="btn btn-sm btn-outline-primary">
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
                backgroundColor: 'rgba(255, 193, 7, 0.5)',
                borderColor: 'rgba(255, 193, 7, 1)',
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
    
    // Monthly Trend Chart
    const trendChartCtx = document.getElementById('trendChart').getContext('2d');
    new Chart(trendChartCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($months); ?>,
            datasets: [{
                label: 'Monthly Registrations',
                data: <?php echo json_encode($monthly_data); ?>,
                backgroundColor: 'rgba(255, 193, 7, 0.2)',
                borderColor: 'rgba(255, 193, 7, 1)',
                borderWidth: 2,
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        title: function(tooltipItems) {
                            return months[tooltipItems[0].dataIndex] + ' ' + <?php echo $current_year; ?>;
                        }
                    }
                }
            },
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
            }
        }
    });
});
</script>
<?php endif; ?>

<?php
// Include footer
include 'includes/footer.php';
?>