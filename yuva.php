<?php
// Include header
include 'includes/header.php';

// Check if viewing a specific registration
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $yuva = fetch_row($conn, "SELECT * FROM yuva WHERE id = $id");
    
    if (!$yuva) {
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
    
    // Filter by school
    if (!empty($_POST['school'])) {
        $school = clean($conn, $_POST['school']);
        $where_clause .= " AND school LIKE '%$school%'";
    }
    
    // Filter by grade
    if (!empty($_POST['grade'])) {
        $grade = clean($conn, $_POST['grade']);
        $where_clause .= " AND grade = '$grade'";
    }
    
    // Filter by topic
    if (!empty($_POST['topic'])) {
        $topic = clean($conn, $_POST['topic']);
        $where_clause .= " AND topic LIKE '%$topic%'";
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

// Get all schools and grades for filter dropdowns
$schools = fetch_all($conn, "SELECT DISTINCT school FROM yuva WHERE school IS NOT NULL AND school != '' ORDER BY school");
$grades = fetch_all($conn, "SELECT DISTINCT grade FROM yuva WHERE grade IS NOT NULL AND grade != '' ORDER BY grade");
$topics = fetch_all($conn, "SELECT DISTINCT topic FROM yuva WHERE topic IS NOT NULL AND topic != '' ORDER BY topic");

// Get registrations with applied filters
$yuvas = fetch_all($conn, "SELECT * FROM yuva WHERE $where_clause ORDER BY created_at DESC");

// Get counts by school for chart
$school_counts = fetch_all($conn, "SELECT school, COUNT(*) as count FROM yuva GROUP BY school ORDER BY count DESC LIMIT 10");
$school_labels = [];
$school_data = [];

foreach ($school_counts as $row) {
    $school_labels[] = $row['school'] ?: 'Unknown';
    $school_data[] = (int)$row['count'];
}

// Get counts by grade for chart
$grade_counts = fetch_all($conn, "SELECT grade, COUNT(*) as count FROM yuva GROUP BY grade ORDER BY grade");
$grade_labels = [];
$grade_data = [];

foreach ($grade_counts as $row) {
    $grade_labels[] = $row['grade'] ?: 'Unknown';
    $grade_data[] = (int)$row['count'];
}

// Get registration trend by month for current year
$current_year = date('Y');
$monthly_trend = fetch_all($conn, "SELECT MONTH(created_at) as month, COUNT(*) as count FROM yuva 
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

<?php if (isset($yuva)): ?>
<!-- Single Registration View -->
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Yuva Summit Registration Details</h5>
            <a href="yuva.php" class="btn btn-sm btn-outline-secondary">
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
                            <td><?php echo $yuva['full_name']; ?></td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td><?php echo $yuva['email']; ?></td>
                        </tr>
                        <tr>
                            <th>Mobile</th>
                            <td><?php echo $yuva['mobile']; ?></td>
                        </tr>
                        <tr>
                            <th>School</th>
                            <td><?php echo $yuva['school']; ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted mb-3">Academic Information</h6>
                    <table class="table table-striped">
                        <tr>
                            <th width="150">Grade</th>
                            <td><?php echo $yuva['grade']; ?></td>
                        </tr>
                        <tr>
                            <th>Topic</th>
                            <td><?php echo $yuva['topic']; ?></td>
                        </tr>
                        <tr>
                            <th>Registered On</th>
                            <td><?php echo date('d M Y H:i', strtotime($yuva['created_at'])); ?></td>
                        </tr>
                        <tr>
                            <th>Last Updated</th>
                            <td><?php echo date('d M Y H:i', strtotime($yuva['updated_at'])); ?></td>
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
                <h3><?php echo count($yuvas); ?></h3>
                <p>Total Registrations</p>
            </div>
        </div>
        <div class="col-md-4 col-xl-4">
            <div class="stats-card text-center">
                <i class="fas fa-school"></i>
                <h3><?php echo count($schools); ?></h3>
                <p>Unique Schools</p>
            </div>
        </div>
        <div class="col-md-4 col-xl-4">
            <div class="stats-card text-center">
                <i class="fas fa-book"></i>
                <h3><?php echo count($topics); ?></h3>
                <p>Unique Topics</p>
            </div>
        </div>
    </div>
    
    <!-- Charts Row -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Registrations by School</h5>
                </div>
                <div class="card-body">
                    <canvas id="schoolChart" height="250"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Registrations by Grade</h5>
                </div>
                <div class="card-body">
                    <canvas id="gradeChart" height="250"></canvas>
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
                    <label for="full_name" class="form-label">Name</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo $_POST['full_name'] ?? ''; ?>">
                </div>
                <div class="col-md-3">
                    <label for="school" class="form-label">School</label>
                    <select class="form-select" id="school" name="school">
                        <option value="">All Schools</option>
                        <?php foreach ($schools as $school): ?>
                            <option value="<?php echo $school['school']; ?>" <?php echo (isset($_POST['school']) && $_POST['school'] == $school['school']) ? 'selected' : ''; ?>>
                                <?php echo $school['school']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="grade" class="form-label">Grade</label>
                    <select class="form-select" id="grade" name="grade">
                        <option value="">All Grades</option>
                        <?php foreach ($grades as $grade): ?>
                            <option value="<?php echo $grade['grade']; ?>" <?php echo (isset($_POST['grade']) && $_POST['grade'] == $grade['grade']) ? 'selected' : ''; ?>>
                                <?php echo $grade['grade']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="topic" class="form-label">Topic</label>
                    <select class="form-select" id="topic" name="topic">
                        <option value="">All Topics</option>
                        <?php foreach ($topics as $topic): ?>
                            <option value="<?php echo $topic['topic']; ?>" <?php echo (isset($_POST['topic']) && $_POST['topic'] == $topic['topic']) ? 'selected' : ''; ?>>
                                <?php echo $topic['topic']; ?>
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
                    <a href="yuva.php" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i>Clear Filters
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Data Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Yuva Summit Registrations</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover datatable-export">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Mobile</th>
                            <th>School</th>
                            <th>Grade</th>
                            <th>Topic</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($yuvas as $yuva): ?>
                        <tr>
                            <td><?php echo $yuva['id']; ?></td>
                            <td><?php echo $yuva['full_name']; ?></td>
                            <td><?php echo $yuva['email']; ?></td>
                            <td><?php echo $yuva['mobile']; ?></td>
                            <td><?php echo $yuva['school']; ?></td>
                            <td><?php echo $yuva['grade']; ?></td>
                            <td><?php echo $yuva['topic']; ?></td>
                            <td data-sort="<?php echo strtotime($yuva['created_at']); ?>">
                                <?php echo date('d M Y', strtotime($yuva['created_at'])); ?>
                            </td>
                            <td>
                                <a href="yuva.php?id=<?php echo $yuva['id']; ?>" class="btn btn-sm btn-outline-primary">
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
    // School Chart
    const schoolChartCtx = document.getElementById('schoolChart').getContext('2d');
    new Chart(schoolChartCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($school_labels); ?>,
            datasets: [{
                label: 'Registrations by School',
                data: <?php echo json_encode($school_data); ?>,
                backgroundColor: 'rgba(76, 175, 80, 0.5)',
                borderColor: 'rgba(76, 175, 80, 1)',
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
                        text: 'School'
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
    
    // Grade Chart
    const gradeChartCtx = document.getElementById('gradeChart').getContext('2d');
    new Chart(gradeChartCtx, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode($grade_labels); ?>,
            datasets: [{
                label: 'Registrations by Grade',
                data: <?php echo json_encode($grade_data); ?>,
                backgroundColor: [
                    'rgba(63, 81, 181, 0.7)',
                    'rgba(76, 175, 80, 0.7)',
                    'rgba(33, 150, 243, 0.7)',
                    'rgba(255, 193, 7, 0.7)',
                    'rgba(244, 67, 54, 0.7)',
                    'rgba(156, 39, 176, 0.7)',
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