<?php
// Include header
include 'includes/header.php';

// Get Quest DB connection using the function from database.php
$questConn = connectQuestDB();

// Check if viewing a specific registration
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $school = $questConn->query("SELECT * FROM schools WHERE id = $id")->fetch_assoc();
    
    if (!$school) {
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
        $name = $questConn->real_escape_string(trim($_POST['name']));
        $where_clause .= " AND name LIKE '%$name%'";
    }
    
    // Filter by school name
    if (!empty($_POST['school_name'])) {
        $school_name = $questConn->real_escape_string(trim($_POST['school_name']));
        $where_clause .= " AND school_name LIKE '%$school_name%'";
    }
    
    // Filter by city
    if (!empty($_POST['city'])) {
        $city = $questConn->real_escape_string(trim($_POST['city']));
        $where_clause .= " AND city LIKE '%$city%'";
    }
    
    // Filter by designation
    if (!empty($_POST['designation'])) {
        $designation = $questConn->real_escape_string(trim($_POST['designation']));
        $where_clause .= " AND designation LIKE '%$designation%'";
    }
    
    // Filter by preferred month
    if (!empty($_POST['preferred_month'])) {
        $preferred_month = $questConn->real_escape_string(trim($_POST['preferred_month']));
        $where_clause .= " AND preferred_month = '$preferred_month'";
    }
    
    // Filter by date range
    if (!empty($_POST['date_from']) && !empty($_POST['date_to'])) {
        $date_from = $questConn->real_escape_string(trim($_POST['date_from']));
        $date_to = $questConn->real_escape_string(trim($_POST['date_to']));
        $where_clause .= " AND created_at BETWEEN '$date_from' AND '$date_to 23:59:59'";
    } else if (!empty($_POST['date_from'])) {
        $date_from = $questConn->real_escape_string(trim($_POST['date_from']));
        $where_clause .= " AND created_at >= '$date_from'";
    } else if (!empty($_POST['date_to'])) {
        $date_to = $questConn->real_escape_string(trim($_POST['date_to']));
        $where_clause .= " AND created_at <= '$date_to 23:59:59'";
    }
}

// Get all cities, school names, designations, and preferred months for filter dropdowns
$cities = $questConn->query("SELECT DISTINCT city FROM schools WHERE city IS NOT NULL AND city != '' ORDER BY city");
$school_names = $questConn->query("SELECT DISTINCT school_name FROM schools WHERE school_name IS NOT NULL AND school_name != '' ORDER BY school_name");
$designations = $questConn->query("SELECT DISTINCT designation FROM schools WHERE designation IS NOT NULL AND designation != '' ORDER BY designation");
$preferred_months = $questConn->query("SELECT DISTINCT preferred_month FROM schools WHERE preferred_month IS NOT NULL AND preferred_month != '' ORDER BY preferred_month");

// Get registrations with applied filters
$schools = $questConn->query("SELECT * FROM schools WHERE $where_clause ORDER BY created_at DESC");

// Get total count of registrations
$totalCount = $questConn->query("SELECT COUNT(*) as count FROM schools")->fetch_assoc()['count'];

// Get count of unique schools
$uniqueSchoolsCount = $questConn->query("SELECT COUNT(DISTINCT school_name) as count FROM schools WHERE school_name IS NOT NULL AND school_name != ''")->fetch_assoc()['count'];

// Get count of unique cities
$uniqueCitiesCount = $questConn->query("SELECT COUNT(DISTINCT city) as count FROM schools WHERE city IS NOT NULL AND city != ''")->fetch_assoc()['count'];

// Get counts by city for chart
$city_counts = $questConn->query("SELECT city, COUNT(*) as count FROM schools GROUP BY city ORDER BY count DESC LIMIT 10");
$city_labels = [];
$city_data = [];

if ($city_counts) {
    while($row = $city_counts->fetch_assoc()) {
        $city_labels[] = $row['city'] ?: 'Unknown';
        $city_data[] = (int)$row['count'];
    }
}

// Get counts by preferred month for chart
$month_counts = $questConn->query("SELECT preferred_month, COUNT(*) as count FROM schools WHERE preferred_month IS NOT NULL AND preferred_month != '' GROUP BY preferred_month ORDER BY FIELD(preferred_month, 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December')");
$month_labels = [];
$month_data = [];

if ($month_counts) {
    while($row = $month_counts->fetch_assoc()) {
        $month_labels[] = $row['preferred_month'];
        $month_data[] = (int)$row['count'];
    }
}

// Get registration trend by month for current year
$current_year = date('Y');
$monthly_trend = $questConn->query("SELECT MONTH(created_at) as month, COUNT(*) as count FROM schools 
                               WHERE YEAR(created_at) = '$current_year' 
                               GROUP BY MONTH(created_at) 
                               ORDER BY month");

$months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
$monthly_data = array_fill(0, 12, 0);

if ($monthly_trend) {
    while($row = $monthly_trend->fetch_assoc()) {
        $month_index = $row['month'] - 1;
        if (isset($monthly_data[$month_index])) {
            $monthly_data[$month_index] = (int)$row['count'];
        }
    }
}
?>

<?php if (isset($school)): ?>
<!-- Single Registration View -->
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Quest 2025 Registration Details</h5>
            <a href="quest.php" class="btn btn-sm btn-outline-secondary">
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
                            <td><?php echo $school['school_name']; ?></td>
                        </tr>
                        <tr>
                            <th>Principal Name</th>
                            <td><?php echo $school['principal_name']; ?></td>
                        </tr>
                        <tr>
                            <th>City</th>
                            <td><?php echo $school['city']; ?></td>
                        </tr>
                        <tr>
                            <th>Preferred Month</th>
                            <td><?php echo $school['preferred_month']; ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted mb-3">Contact Information</h6>
                    <table class="table table-striped">
                        <tr>
                            <th width="150">Contact Name</th>
                            <td><?php echo $school['name']; ?></td>
                        </tr>
                        <tr>
                            <th>Designation</th>
                            <td><?php echo $school['designation']; ?></td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td><?php echo $school['email']; ?></td>
                        </tr>
                        <tr>
                            <th>Mobile</th>
                            <td><?php echo $school['mobile']; ?></td>
                        </tr>
                        <tr>
                            <th>Registered On</th>
                            <td><?php echo date('d M Y H:i', strtotime($school['created_at'])); ?></td>
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
                <h3><?php echo $totalCount; ?></h3>
                <p>Total Registrations</p>
            </div>
        </div>
        <div class="col-md-4 col-xl-4">
            <div class="stats-card text-center">
                <i class="fas fa-school"></i>
                <h3><?php echo $uniqueSchoolsCount; ?></h3>
                <p>Unique Schools</p>
            </div>
        </div>
        <div class="col-md-4 col-xl-4">
            <div class="stats-card text-center">
                <i class="fas fa-map-marker-alt"></i>
                <h3><?php echo $uniqueCitiesCount; ?></h3>
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
                    <h5 class="mb-0">Preferred Months Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="monthChart" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Monthly Registration Trend (<?php echo $current_year; ?>)</h5>
                </div>
                <div class="card-body">
                    <canvas id="trendChart" height="100"></canvas>
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
                <div class="col-md-4">
                    <label for="name" class="form-label">Contact Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo $_POST['name'] ?? ''; ?>">
                </div>
                <div class="col-md-4">
                    <label for="school_name" class="form-label">School Name</label>
                    <select class="form-select" id="school_name" name="school_name">
                        <option value="">All Schools</option>
                        <?php while($school = $school_names->fetch_assoc()): ?>
                            <option value="<?php echo $school['school_name']; ?>" <?php echo (isset($_POST['school_name']) && $_POST['school_name'] == $school['school_name']) ? 'selected' : ''; ?>>
                                <?php echo $school['school_name']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="city" class="form-label">City</label>
                    <select class="form-select" id="city" name="city">
                        <option value="">All Cities</option>
                        <?php while($city = $cities->fetch_assoc()): ?>
                            <option value="<?php echo $city['city']; ?>" <?php echo (isset($_POST['city']) && $_POST['city'] == $city['city']) ? 'selected' : ''; ?>>
                                <?php echo $city['city']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="designation" class="form-label">Designation</label>
                    <select class="form-select" id="designation" name="designation">
                        <option value="">All Designations</option>
                        <?php while($designation = $designations->fetch_assoc()): ?>
                            <option value="<?php echo $designation['designation']; ?>" <?php echo (isset($_POST['designation']) && $_POST['designation'] == $designation['designation']) ? 'selected' : ''; ?>>
                                <?php echo $designation['designation']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="preferred_month" class="form-label">Preferred Month</label>
                    <select class="form-select" id="preferred_month" name="preferred_month">
                        <option value="">All Months</option>
                        <?php while($month = $preferred_months->fetch_assoc()): ?>
                            <option value="<?php echo $month['preferred_month']; ?>" <?php echo (isset($_POST['preferred_month']) && $_POST['preferred_month'] == $month['preferred_month']) ? 'selected' : ''; ?>>
                                <?php echo $month['preferred_month']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="date_from" class="form-label">Date From</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $_POST['date_from'] ?? ''; ?>">
                </div>
                <div class="col-md-4">
                    <label for="date_to" class="form-label">Date To</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $_POST['date_to'] ?? ''; ?>">
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button type="submit" name="filter" class="btn btn-primary me-2">
                        <i class="fas fa-filter me-2"></i>Apply Filters
                    </button>
                    <a href="quest.php" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i>Clear Filters
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Data Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Quest 2025 Registrations</h5>
            <div>
                <button type="button" class="btn btn-sm btn-success export-csv">
                    <i class="fas fa-file-csv me-1"></i> Export CSV
                </button>
                <button type="button" class="btn btn-sm btn-primary export-excel">
                    <i class="fas fa-file-excel me-1"></i> Export Excel
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover datatable-export">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Contact_Full_Name</th>
                            <th>Registered_School_Name</th>
                            <th>Principal_Name</th>
                            <th>Designation</th>
                            <th>City</th>
                            <th>Email</th>
                            <th>Mobile</th>
                            <th>Preferred Month</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Reset the query result pointer
                        $schools = $questConn->query("SELECT * FROM schools WHERE $where_clause ORDER BY created_at DESC");
                        
                        if ($schools) {
                            while($school = $schools->fetch_assoc()): 
                        ?>
                        <tr>
                            <td><?php echo $school['id']; ?></td>
                            <td><?php echo $school['name']; ?></td>
                            <td><?php echo $school['school_name']; ?></td>
                            <td><?php echo $school['principal_name']; ?></td>
                            <td><?php echo $school['designation']; ?></td>
                            <td><?php echo $school['city']; ?></td>
                            <td><?php echo $school['email']; ?></td>
                            <td><?php echo $school['mobile']; ?></td>
                            <td><?php echo $school['preferred_month']; ?></td>
                            <td data-sort="<?php echo strtotime($school['created_at']); ?>">
                                <?php echo date('d M Y', strtotime($school['created_at'])); ?>
                            </td>
                            <td>
                                <a href="quest.php?id=<?php echo $school['id']; ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php 
                            endwhile;
                        }
                        ?>
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
                backgroundColor: 'rgba(79, 70, 229, 0.6)',
                borderColor: 'rgba(79, 70, 229, 1)',
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
    
    // Preferred Month Chart
    const monthChartCtx = document.getElementById('monthChart').getContext('2d');
    new Chart(monthChartCtx, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode($month_labels); ?>,
            datasets: [{
                label: 'Preferred Months',
                data: <?php echo json_encode($month_data); ?>,
                backgroundColor: [
                    'rgba(79, 70, 229, 0.7)',
                    'rgba(6, 182, 212, 0.7)',
                    'rgba(16, 185, 129, 0.7)',
                    'rgba(245, 158, 11, 0.7)',
                    'rgba(249, 115, 22, 0.7)',
                    'rgba(239, 68, 68, 0.7)',
                    'rgba(156, 39, 176, 0.7)',
                    'rgba(33, 150, 243, 0.7)',
                    'rgba(76, 175, 80, 0.7)',
                    'rgba(255, 193, 7, 0.7)',
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
    
    // Monthly Trend Chart
    const trendChartCtx = document.getElementById('trendChart').getContext('2d');
    new Chart(trendChartCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($months); ?>,
            datasets: [{
                label: 'Monthly Registrations',
                data: <?php echo json_encode($monthly_data); ?>,
                backgroundColor: 'rgba(79, 70, 229, 0.2)',
                borderColor: 'rgba(79, 70, 229, 1)',
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
    
    // Custom export buttons
    $('.export-csv').on('click', function() {
        $('.buttons-csv').click();
    });
    
    $('.export-excel').on('click', function() {
        $('.buttons-excel').click();
    });
});
</script>
<?php endif; ?>

<?php
// Close the Quest database connection
$questConn->close();

// Include footer
include 'includes/footer.php';
?> 