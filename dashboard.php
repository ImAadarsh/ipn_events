<?php
// Include header
include 'includes/header.php';

// Get total counts by event type
$result = $conn->query("SELECT COUNT(*) as count FROM conclaves");
$conclave_count = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM yuva");
$yuva_count = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM leaderssummit");
$leaderssummit_count = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM misb");
$misb_count = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM ils");
$ils_count = $result->fetch_assoc()['count'];

// Get Quest count using the centralized connection function
$questConn = connectQuestDB();
$quest_count = 0;
$quest_result = $questConn->query("SELECT COUNT(*) as count FROM schools");
if ($quest_result) {
    $quest_count = $quest_result->fetch_assoc()['count'];
}
$questConn->close();

$total_count = $conclave_count + $yuva_count + $leaderssummit_count + $misb_count + $ils_count + $quest_count;

// Get recent registrations (latest 5 from each table)
$conclaves = fetch_all($conn, "SELECT * FROM conclaves ORDER BY created_at DESC LIMIT 5");
$yuvas = fetch_all($conn, "SELECT * FROM yuva ORDER BY created_at DESC LIMIT 5");
$leaderssummits = fetch_all($conn, "SELECT * FROM leaderssummit ORDER BY created_at DESC LIMIT 5");
$misbs = fetch_all($conn, "SELECT * FROM misb ORDER BY created_at DESC LIMIT 5");
$ills = fetch_all($conn, "SELECT * FROM ils ORDER BY created_at DESC LIMIT 5");

// Get recent Quest registrations
$questConn = connectQuestDB();
$quests = fetch_all($questConn, "SELECT * FROM schools ORDER BY created_at DESC LIMIT 5");
$questConn->close();

// Get registration trends (count by month for the current year)
$current_year = date('Y');
$months = array();
$conclave_trends = array_fill(0, 12, 0);
$yuva_trends = array_fill(0, 12, 0);
$leaderssummit_trends = array_fill(0, 12, 0);
$misb_trends = array_fill(0, 12, 0);
$ils_trends = array_fill(0, 12, 0);
$quest_trends = array_fill(0, 12, 0);

// Populate conclave trends
$conclave_monthly = fetch_all($conn, "SELECT MONTH(created_at) as month, COUNT(*) as count FROM conclaves 
                                  WHERE YEAR(created_at) = '$current_year' 
                                  GROUP BY MONTH(created_at)");
foreach ($conclave_monthly as $row) {
    $conclave_trends[$row['month'] - 1] = (int)$row['count'];
}

// Populate yuva trends
$yuva_monthly = fetch_all($conn, "SELECT MONTH(created_at) as month, COUNT(*) as count FROM yuva 
                             WHERE YEAR(created_at) = '$current_year' 
                             GROUP BY MONTH(created_at)");
foreach ($yuva_monthly as $row) {
    $yuva_trends[$row['month'] - 1] = (int)$row['count'];
}

// Populate leaderssummit trends
$leaderssummit_monthly = fetch_all($conn, "SELECT MONTH(created_at) as month, COUNT(*) as count FROM leaderssummit 
                                      WHERE YEAR(created_at) = '$current_year' 
                                      GROUP BY MONTH(created_at)");
foreach ($leaderssummit_monthly as $row) {
    $leaderssummit_trends[$row['month'] - 1] = (int)$row['count'];
}

// Populate misb trends
$misb_monthly = fetch_all($conn, "SELECT MONTH(created_at) as month, COUNT(*) as count FROM misb 
                             WHERE YEAR(created_at) = '$current_year' 
                             GROUP BY MONTH(created_at)");
foreach ($misb_monthly as $row) {
    $misb_trends[$row['month'] - 1] = (int)$row['count'];
}

// Populate ils trends
$ils_monthly = fetch_all($conn, "SELECT MONTH(created_at) as month, COUNT(*) as count FROM ils 
                            WHERE YEAR(created_at) = '$current_year' 
                            GROUP BY MONTH(created_at)");
foreach ($ils_monthly as $row) {
    $ils_trends[$row['month'] - 1] = (int)$row['count'];
}

// Populate quest trends
$questConn = connectQuestDB();
$quest_monthly = fetch_all($questConn, "SELECT MONTH(created_at) as month, COUNT(*) as count FROM schools 
                            WHERE YEAR(created_at) = '$current_year' 
                            GROUP BY MONTH(created_at)");
foreach ($quest_monthly as $row) {
    $quest_trends[$row['month'] - 1] = (int)$row['count'];
}
$questConn->close();

// Month names
$month_names = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
?>

<!-- Compact UI Styles -->
<style>
    /* More compact layout */
    .container-fluid {
        padding: 0.5rem;
    }
    
    /* More compact stats cards */
    .stats-card {
        padding: 0.75rem 0.5rem;
        margin-bottom: 0.75rem;
    }
    
    .stats-card i {
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
    }
    
    .stats-card h3 {
        font-size: 1.5rem;
        margin-bottom: 0.25rem;
    }
    
    .stats-card p {
        font-size: 0.8rem;
        margin-bottom: 0;
    }
    
    /* More compact cards */
    .card {
        margin-bottom: 0.75rem;
    }
    
    .card-header {
        padding: 0.5rem 0.75rem;
    }
    
    .card-header h5 {
        font-size: 0.95rem;
    }
    
    .card-body {
        padding: 0.75rem;
    }
    
    /* More compact tables */
    .table {
        margin-bottom: 0;
    }
    
    .table th, .table td {
        padding: 0.4rem 0.5rem;
        font-size: 0.8rem;
    }
    
    .badge {
        padding: 0.25rem 0.5rem;
        font-size: 0.7rem;
    }
    
    .btn-sm {
        padding: 0.15rem 0.4rem;
        font-size: 0.75rem;
    }
    
    /* More compact margins between rows */
    .row {
        margin-bottom: 0.5rem;
    }
    
    /* DataTables adjustments */
    div.dataTables_wrapper div.dataTables_filter,
    div.dataTables_wrapper div.dataTables_length,
    div.dataTables_wrapper div.dataTables_info,
    div.dataTables_wrapper div.dataTables_paginate {
        margin-bottom: 0.5rem;
        font-size: 0.8rem;
    }
    
    div.dataTables_wrapper div.dataTables_filter input,
    div.dataTables_wrapper div.dataTables_length select {
        height: calc(1.5em + 0.5rem + 2px);
        padding: 0.25rem 0.5rem;
        font-size: 0.8rem;
    }
    
    .form-select-sm {
        height: calc(1.5em + 0.5rem + 2px);
        padding: 0.25rem 0.5rem;
        font-size: 0.8rem;
    }
</style>

<div class="container-fluid">
    <!-- Stats Cards -->
    <div class="row mb-4">

        <div class="col-md-4 col-xl-3">
            <div class="stats-card text-center">
                <i class="fas fa-comments"></i>
                <h3><?php echo $conclave_count; ?></h3>
                <p>IPN Conclaves</p>
            </div>
        </div>
        <div class="col-md-4 col-xl-3">
            <div class="stats-card text-center">
                <i class="fas fa-graduation-cap"></i>
                <h3><?php echo $yuva_count; ?></h3>
                <p>Yuva Summit</p>
            </div>
        </div>
        <div class="col-md-4 col-xl-3">
            <div class="stats-card text-center">
                <i class="fas fa-globe-asia"></i>
                <h3><?php echo $leaderssummit_count; ?></h3>
                <p>Leaders Summit</p>
            </div>
        </div>
        <div class="col-md-4 col-xl-3">
            <div class="stats-card text-center">
                <i class="fas fa-award"></i>
                <h3><?php echo $misb_count; ?></h3>
                <p>Impactful Schools</p>
            </div>
        </div>
        <div class="col-md-4 col-xl-3">
            <div class="stats-card text-center">
                <i class="fas fa-landmark"></i>
                <h3><?php echo $ils_count; ?></h3>
                <p>IPN Leadership</p>
            </div>
        </div>
        <div class="col-md-4 col-xl-3">
            <div class="stats-card text-center">
                <i class="fas fa-trophy"></i>
                <h3><?php echo $quest_count; ?></h3>
                <p>Quest 2025</p>
            </div>
        </div>
        <div class="col-md-4 col-xl-6">
            <div class="stats-card text-center">
                <i class="fas fa-users"></i>
                <h3><?php echo $total_count; ?></h3>
                <p>Total Registrations</p>
            </div>
        </div>
    </div>
    
    <!-- Registration Trends Chart -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Registration Trends - <?php echo $current_year; ?></h5>
                </div>
                <div class="card-body">
                    <canvas id="registrationTrendsChart" height="80"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Registrations -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Registrations</h5>
                    <div>
                        <select class="form-select form-select-sm" id="recentRegistrationsSelect">
                            <option value="all" selected>All Events</option>
                            <option value="conclaves">IPN Conclaves</option>
                            <option value="yuva">Yuva Summit</option>
                            <option value="leaderssummit">Nepal Summit</option>
                            <option value="misb">Impactful Schools</option>
                            <option value="ils">IPN Leadership</option>
                            <option value="quest">Quest 2025</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <!-- All Events Table (default) -->
                    <div id="all-table" class="registration-table">
                        <div class="table-responsive">
                            <table class="table table-hover" id="all-events-table">
                                <thead>
                                    <tr>
                                        <th>Event Type</th>
                                        <th>Name</th>
                                        <th>Email/Phone</th>
                                        <th>Institution</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($conclaves as $conclave): ?>
                                    <tr>
                                        <td><span class="badge bg-primary">Conclave</span></td>
                                        <td><?php echo $conclave['name']; ?></td>
                                        <td><?php echo $conclave['email'] ?? $conclave['mail']; ?></td>
                                        <td><?php echo $conclave['institute']; ?></td>
                                        <td><?php echo date('d M Y', strtotime($conclave['created_at'])); ?></td>
                                        <td>
                                            <a href="conclaves.php?id=<?php echo $conclave['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php foreach ($yuvas as $yuva): ?>
                                    <tr>
                                        <td><span class="badge bg-success">Yuva</span></td>
                                        <td><?php echo $yuva['full_name']; ?></td>
                                        <td><?php echo $yuva['email']; ?> / <?php echo $yuva['mobile']; ?></td>
                                        <td><?php echo $yuva['school']; ?></td>
                                        <td><?php echo date('d M Y', strtotime($yuva['created_at'])); ?></td>
                                        <td>
                                            <a href="yuva.php?id=<?php echo $yuva['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php foreach ($leaderssummits as $summit): ?>
                                    <tr>
                                        <td><span class="badge bg-info">Leaders</span></td>
                                        <td><?php echo $summit['name']; ?></td>
                                        <td><?php echo $summit['email'] ?? $summit['mail']; ?></td>
                                        <td><?php echo $summit['institute']; ?></td>
                                        <td><?php echo date('d M Y', strtotime($summit['created_at'])); ?></td>
                                        <td>
                                            <a href="leaderssummit.php?id=<?php echo $summit['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php foreach ($misbs as $misb): ?>
                                    <tr>
                                        <td><span class="badge bg-warning text-dark">MISB</span></td>
                                        <td><?php echo $misb['name']; ?></td>
                                        <td><?php echo $misb['email']; ?> / <?php echo $misb['phone']; ?></td>
                                        <td><?php echo $misb['school_name']; ?></td>
                                        <td><?php echo date('d M Y', strtotime($misb['created_at'])); ?></td>
                                        <td>
                                            <a href="misb.php?id=<?php echo $misb['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php foreach ($ills as $ils): ?>
                                    <tr>
                                        <td><span class="badge bg-danger">ILS</span></td>
                                        <td><?php echo $ils['full_name']; ?></td>
                                        <td><?php echo $ils['email']; ?> / <?php echo $ils['phone']; ?></td>
                                        <td><?php echo $ils['school_name']; ?></td>
                                        <td><?php echo date('d M Y', strtotime($ils['created_at'])); ?></td>
                                        <td>
                                            <a href="ils.php?id=<?php echo $ils['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php foreach ($quests as $quest): ?>
                                    <tr>
                                        <td><span class="badge bg-purple" style="background-color: #9c27b0;">Quest</span></td>
                                        <td><?php echo $quest['school_name']; ?></td>
                                        <td><?php echo $quest['email']; ?> / <?php echo $quest['phone']; ?></td>
                                        <td><?php echo $quest['address']; ?></td>
                                        <td><?php echo date('d M Y', strtotime($quest['created_at'])); ?></td>
                                        <td>
                                            <a href="quest.php?id=<?php echo $quest['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- IPN Conclaves Table -->
                    <div id="conclaves-table" class="registration-table" style="display: none;">
                        <div class="table-responsive">
                            <table class="table table-hover" id="conclaves-events-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Designation</th>
                                        <th>City</th>
                                        <th>Institution</th>
                                        <th>Contact</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($conclaves as $conclave): ?>
                                    <tr>
                                        <td><?php echo $conclave['name']; ?></td>
                                        <td><?php echo $conclave['designation']; ?></td>
                                        <td><?php echo $conclave['city']; ?></td>
                                        <td><?php echo $conclave['institute']; ?></td>
                                        <td><?php echo $conclave['phone']; ?></td>
                                        <td><?php echo date('d M Y', strtotime($conclave['created_at'])); ?></td>
                                        <td>
                                            <a href="conclaves.php?id=<?php echo $conclave['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Yuva Summit Table -->
                    <div id="yuva-table" class="registration-table" style="display: none;">
                        <div class="table-responsive">
                            <table class="table table-hover" id="yuva-events-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Mobile</th>
                                        <th>School</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($yuvas as $yuva): ?>
                                    <tr>
                                        <td><?php echo $yuva['full_name']; ?></td>
                                        <td><?php echo $yuva['email']; ?></td>
                                        <td><?php echo $yuva['mobile']; ?></td>
                                        <td><?php echo $yuva['school']; ?></td>
                                        <td><?php echo date('d M Y', strtotime($yuva['created_at'])); ?></td>
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
                    
                    <!-- Leaders Summit Table -->
                    <div id="leaderssummit-table" class="registration-table" style="display: none;">
                        <div class="table-responsive">
                            <table class="table table-hover" id="leaderssummit-events-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Institution</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($leaderssummits as $summit): ?>
                                    <tr>
                                        <td><?php echo $summit['name']; ?></td>
                                        <td><?php echo $summit['email'] ?? $summit['mail']; ?></td>
                                        <td><?php echo $summit['phone']; ?></td>
                                        <td><?php echo $summit['institute']; ?></td>
                                        <td><?php echo date('d M Y', strtotime($summit['created_at'])); ?></td>
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
                    
                    <!-- MISB Table -->
                    <div id="misb-table" class="registration-table" style="display: none;">
                        <div class="table-responsive">
                            <table class="table table-hover" id="misb-events-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>School Name</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($misbs as $misb): ?>
                                    <tr>
                                        <td><?php echo $misb['name']; ?></td>
                                        <td><?php echo $misb['email']; ?></td>
                                        <td><?php echo $misb['phone']; ?></td>
                                        <td><?php echo $misb['school_name']; ?></td>
                                        <td><?php echo date('d M Y', strtotime($misb['created_at'])); ?></td>
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
                    
                    <!-- ILS Table -->
                    <div id="ils-table" class="registration-table" style="display: none;">
                        <div class="table-responsive">
                            <table class="table table-hover" id="ils-events-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>School Name</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ills as $ils): ?>
                                    <tr>
                                        <td><?php echo $ils['full_name']; ?></td>
                                        <td><?php echo $ils['email']; ?></td>
                                        <td><?php echo $ils['phone']; ?></td>
                                        <td><?php echo $ils['school_name']; ?></td>
                                        <td><?php echo date('d M Y', strtotime($ils['created_at'])); ?></td>
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
                    
                    <!-- Quest Table -->
                    <div id="quest-table" class="registration-table" style="display: none;">
                        <div class="table-responsive">
                            <table class="table table-hover" id="quest-events-table">
                                <thead>
                                    <tr>
                                        <th>School Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Address</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($quests as $quest): ?>
                                    <tr>
                                        <td><?php echo $quest['school_name']; ?></td>
                                        <td><?php echo $quest['email']; ?></td>
                                        <td><?php echo $quest['phone']; ?></td>
                                        <td><?php echo $quest['address']; ?></td>
                                        <td><?php echo date('d M Y', strtotime($quest['created_at'])); ?></td>
                                        <td>
                                            <a href="quest.php?id=<?php echo $quest['id']; ?>" class="btn btn-sm btn-outline-primary">
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
        </div>
    </div>
</div>

<script>
// Registration Trends Chart
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('registrationTrendsChart').getContext('2d');
    
    const data = {
        labels: <?php echo json_encode($month_names); ?>,
        datasets: [
            {
                label: 'IPN Conclaves',
                data: <?php echo json_encode($conclave_trends); ?>,
                backgroundColor: 'rgba(63, 81, 181, 0.2)',
                borderColor: 'rgba(63, 81, 181, 1)',
                borderWidth: 2,
                tension: 0.4
            },
            {
                label: 'Yuva Summit',
                data: <?php echo json_encode($yuva_trends); ?>,
                backgroundColor: 'rgba(76, 175, 80, 0.2)',
                borderColor: 'rgba(76, 175, 80, 1)',
                borderWidth: 2,
                tension: 0.4
            },
            {
                label: 'Nepal Summit',
                data: <?php echo json_encode($leaderssummit_trends); ?>,
                backgroundColor: 'rgba(33, 150, 243, 0.2)',
                borderColor: 'rgba(33, 150, 243, 1)',
                borderWidth: 2,
                tension: 0.4
            },
            {
                label: 'Impactful Schools',
                data: <?php echo json_encode($misb_trends); ?>,
                backgroundColor: 'rgba(255, 193, 7, 0.2)',
                borderColor: 'rgba(255, 193, 7, 1)',
                borderWidth: 2,
                tension: 0.4
            },
            {
                label: 'IPN Leadership',
                data: <?php echo json_encode($ils_trends); ?>,
                backgroundColor: 'rgba(244, 67, 54, 0.2)',
                borderColor: 'rgba(244, 67, 54, 1)',
                borderWidth: 2,
                tension: 0.4
            },
            {
                label: 'Quest 2025',
                data: <?php echo json_encode($quest_trends); ?>,
                backgroundColor: 'rgba(156, 39, 176, 0.2)',
                borderColor: 'rgba(156, 39, 176, 1)',
                borderWidth: 2,
                tension: 0.4
            }
        ]
    };
    
    const config = {
        type: 'line',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        boxWidth: 12,
                        font: {
                            size: 10
                        }
                    }
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    bodyFont: {
                        size: 11
                    },
                    titleFont: {
                        size: 11
                    }
                }
            },
            scales: {
                x: {
                    display: true,
                    title: {
                        display: true,
                        text: 'Month',
                        font: {
                            size: 10
                        }
                    },
                    ticks: {
                        font: {
                            size: 9
                        }
                    }
                },
                y: {
                    display: true,
                    title: {
                        display: true,
                        text: 'Registrations',
                        font: {
                            size: 10
                        }
                    },
                    beginAtZero: true,
                    ticks: {
                        font: {
                            size: 9
                        }
                    }
                }
            }
        },
    };
    
    new Chart(ctx, config);
    
    // Initialize DataTables for visible tables only
    const allEventsTable = $('#all-events-table').DataTable({
        responsive: true,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search..."
        },
        dom: '<"d-flex justify-content-between align-items-center small mb-2"lf>rt<"d-flex justify-content-between align-items-center small mt-2"ip>'
    });
    
    // Store table instances in an object for easy access
    const tables = {
        'all': allEventsTable,
        'conclaves': null,
        'yuva': null,
        'leaderssummit': null,
        'misb': null,
        'ils': null,
        'quest': null
    };
    
    // Event type select change handler
    document.getElementById('recentRegistrationsSelect').addEventListener('change', function() {
        const value = this.value;
        
        // Hide all tables
        document.querySelectorAll('.registration-table').forEach(table => {
            table.style.display = 'none';
        });
        
        // Show selected table
        document.getElementById(value + '-table').style.display = 'block';
        
        // Initialize the table if it hasn't been initialized yet
        if (!tables[value]) {
            const tableId = value + '-events-table';
            const tableElement = document.getElementById(tableId);
            if (tableElement) {
                tables[value] = $('#' + tableId).DataTable({
                    responsive: true,
                    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                    language: {
                        search: "_INPUT_",
                        searchPlaceholder: "Search..."
                    },
                    dom: '<"d-flex justify-content-between align-items-center small mb-2"lf>rt<"d-flex justify-content-between align-items-center small mt-2"ip>'
                });
            }
        }
    });
});
</script>

<?php
// Include footer
include 'includes/footer.php';
?> 