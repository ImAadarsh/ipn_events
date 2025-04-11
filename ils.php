<?php
// Include header
include 'includes/header.php';

// Check if viewing a specific registration
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $ils = fetch_row($conn, "SELECT * FROM ils WHERE id = $id");
    
    if (!$ils) {
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
    if (!empty($_POST['full_name'])) {
        $full_name = clean($conn, $_POST['full_name']);
        $where_clause .= " AND full_name LIKE '%$full_name%'";
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

// Get all cities, school names, designations for filter dropdowns
$cities = fetch_all($conn, "SELECT DISTINCT city FROM ils WHERE city IS NOT NULL AND city != '' ORDER BY city");
$school_names = fetch_all($conn, "SELECT DISTINCT school_name FROM ils WHERE school_name IS NOT NULL AND school_name != '' ORDER BY school_name");
$designations = fetch_all($conn, "SELECT DISTINCT designation FROM ils WHERE designation IS NOT NULL AND designation != '' ORDER BY designation");

// Get registrations with applied filters
$ilss = fetch_all($conn, "SELECT * FROM ils WHERE $where_clause ORDER BY created_at DESC");

// Get counts by city for chart
$city_counts = fetch_all($conn, "SELECT city, COUNT(*) as count FROM ils GROUP BY city ORDER BY count DESC LIMIT 10");
$city_labels = [];
$city_data = [];

foreach ($city_counts as $row) {
    $city_labels[] = $row['city'] ?: 'Unknown';
    $city_data[] = (int)$row['count'];
}

// Get counts by designation for chart
$designation_counts = fetch_all($conn, "SELECT designation, COUNT(*) as count FROM ils GROUP BY designation ORDER BY count DESC LIMIT 10");
$designation_labels = [];
$designation_data = [];

foreach ($designation_counts as $row) {
    $designation_labels[] = $row['designation'] ?: 'Unknown';
    $designation_data[] = (int)$row['count'];
}

// Get registration trend by month for current year
$current_year = date('Y');
$monthly_trend = fetch_all($conn, "SELECT MONTH(created_at) as month, COUNT(*) as count FROM ils 
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

<?php if (isset($ils)): ?>
<!-- Single Registration View -->
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">IPN Leadership Summit Registration Details</h5>
            <a href="ils.php" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to List
            </a>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-muted mb-3">Personal Information</h6>
                    <table class="table table-striped">
                        <tr>
                            <th width="150">Full Name</th>
                            <td><?php echo $ils['full_name']; ?></td>
                        </tr>
                        <tr>
                            <th>Designation</th>
                            <td><?php echo $ils['designation']; ?></td>
                        </tr>
                        <tr>
                            <th>School Name</th>
                            <td><?php echo $ils['school_name']; ?></td>
                        </tr>
                        <tr>
                            <th>City</th>
                            <td><?php echo $ils['city']; ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted mb-3">Contact Information</h6>
                    <table class="table table-striped">
                        <tr>
                            <th width="150">Phone</th>
                            <td><?php echo $ils['phone']; ?></td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td><?php echo $ils['email']; ?></td>
                        </tr>
                        <tr>
                            <th>Registered On</th>
                            <td><?php echo date('d M Y H:i', strtotime($ils['created_at'])); ?></td>
                        </tr>
                        <tr>
                            <th>Last Updated</th>
                            <td><?php echo date('d M Y H:i', strtotime($ils['updated_at'])); ?></td>
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
                <h3><?php echo count($ilss); ?></h3>
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
        <div class="card-header">
            <h5 class="mb-0">Filter Registrations</h5>
        </div>
        <div class="card-body">
            <form method="post" class="row g-3">
                <div class="col-md-3">
                    <label for="full_name" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo $_POST['full_name'] ?? ''; ?>">
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
                    <a href="ils.php" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i>Clear Filters
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Data Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">IPN Leadership Summit Registrations</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover datatable-export">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>School Name</th>
                            <th>Designation</th>
                            <th>City</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ilss as $ils): ?>
                        <tr>
                            <td><?php echo $ils['id']; ?></td>
                            <td><?php echo $ils['full_name']; ?></td>
                            <td><?php echo $ils['school_name']; ?></td>
                            <td><?php echo $ils['designation']; ?></td>
                            <td><?php echo $ils['city']; ?></td>
                            <td><?php echo $ils['email']; ?></td>
                            <td><?php echo $ils['phone']; ?></td>
                            <td data-sort="<?php echo strtotime($ils['created_at']); ?>">
                                <?php echo date('d M Y', strtotime($ils['created_at'])); ?>
                            </td>
                            <td>
                                <a href="ils.php?id=<?php echo $ils['id']; ?>" class="btn btn-sm btn-outline-primary">
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
                backgroundColor: 'rgba(244, 67, 54, 0.5)',
                borderColor: 'rgba(244, 67, 54, 1)',
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
        type: 'pie',
        data: {
            labels: <?php echo json_encode($designation_labels); ?>,
            datasets: [{
                label: 'Registrations by Designation',
                data: <?php echo json_encode($designation_data); ?>,
                backgroundColor: [
                    'rgba(244, 67, 54, 0.7)',
                    'rgba(156, 39, 176, 0.7)',
                    'rgba(33, 150, 243, 0.7)',
                    'rgba(76, 175, 80, 0.7)',
                    'rgba(255, 193, 7, 0.7)',
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
</script>
<?php endif; ?>

<?php
// Include footer
include 'includes/footer.php';
?> 