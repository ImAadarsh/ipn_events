<?php
// Include header
include 'includes/header.php';

// Get total counts by event type
$conclave_count = count_rows($conn, "SELECT COUNT(*) FROM conclaves");
$yuva_count = count_rows($conn, "SELECT COUNT(*) FROM yuva");
$leaderssummit_count = count_rows($conn, "SELECT COUNT(*) FROM leaderssummit");
$misb_count = count_rows($conn, "SELECT COUNT(*) FROM misb");
$ils_count = count_rows($conn, "SELECT COUNT(*) FROM ils");

// Get Quest count from different database using the centralized function
$questConn = connectQuestDB();
$quest_count = 0;
$quest_result = $questConn->query("SELECT COUNT(*) as count FROM schools");
if ($quest_result) {
    $quest_count = $quest_result->fetch_assoc()['count'];
}

$total_count = $conclave_count + $yuva_count + $leaderssummit_count + $misb_count + $ils_count + $quest_count;

// Get registration trends (count by month for the last 2 years)
$current_year = date('Y');
$last_year = $current_year - 1;
$months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

// Initialize arrays for trends
$current_year_data = array_fill(0, 12, 0);
$last_year_data = array_fill(0, 12, 0);

// Populate current year data
$current_year_monthly = fetch_all($conn, "SELECT MONTH(created_at) as month, COUNT(*) as count 
                                      FROM (
                                          SELECT created_at FROM conclaves WHERE YEAR(created_at) = '$current_year'
                                          UNION ALL
                                          SELECT created_at FROM yuva WHERE YEAR(created_at) = '$current_year'
                                          UNION ALL
                                          SELECT created_at FROM leaderssummit WHERE YEAR(created_at) = '$current_year'
                                          UNION ALL
                                          SELECT created_at FROM misb WHERE YEAR(created_at) = '$current_year'
                                          UNION ALL
                                          SELECT created_at FROM ils WHERE YEAR(created_at) = '$current_year'
                                      ) as combined_data
                                      GROUP BY MONTH(created_at)");

foreach ($current_year_monthly as $row) {
    $current_year_data[$row['month'] - 1] = (int)$row['count'];
}

// Populate last year data
$last_year_monthly = fetch_all($conn, "SELECT MONTH(created_at) as month, COUNT(*) as count 
                                    FROM (
                                        SELECT created_at FROM conclaves WHERE YEAR(created_at) = '$last_year'
                                        UNION ALL
                                        SELECT created_at FROM yuva WHERE YEAR(created_at) = '$last_year'
                                        UNION ALL
                                        SELECT created_at FROM leaderssummit WHERE YEAR(created_at) = '$last_year'
                                        UNION ALL
                                        SELECT created_at FROM misb WHERE YEAR(created_at) = '$last_year'
                                        UNION ALL
                                        SELECT created_at FROM ils WHERE YEAR(created_at) = '$last_year'
                                    ) as combined_data
                                    GROUP BY MONTH(created_at)");

foreach ($last_year_monthly as $row) {
    $last_year_data[$row['month'] - 1] = (int)$row['count'];
}

// Populate Quest trends (from different database)
$quest_current_year_monthly = [];
$quest_last_year_monthly = [];

$quest_current_result = $questConn->query("SELECT MONTH(created_at) as month, COUNT(*) as count 
                                     FROM schools
                                     WHERE YEAR(created_at) = '$current_year'
                                     GROUP BY MONTH(created_at)");

if ($quest_current_result) {
    while ($row = $quest_current_result->fetch_assoc()) {
        $month = (int)$row['month'] - 1;
        if (isset($current_year_data[$month])) {
            $current_year_data[$month] += (int)$row['count'];
        }
    }
}

$quest_last_result = $questConn->query("SELECT MONTH(created_at) as month, COUNT(*) as count 
                                   FROM schools
                                   WHERE YEAR(created_at) = '$last_year'
                                   GROUP BY MONTH(created_at)");

if ($quest_last_result) {
    while ($row = $quest_last_result->fetch_assoc()) {
        $month = (int)$row['month'] - 1;
        if (isset($last_year_data[$month])) {
            $last_year_data[$month] += (int)$row['count'];
        }
    }
}

// Get city distribution data (top 10 cities)
$city_distribution = fetch_all($conn, "SELECT city, COUNT(*) as count 
                                FROM (
                                    SELECT city FROM conclaves WHERE city IS NOT NULL AND city != ''
                                    UNION ALL
                                    SELECT city FROM leaderssummit WHERE city IS NOT NULL AND city != ''
                                    UNION ALL
                                    SELECT city FROM ils WHERE city IS NOT NULL AND city != ''
                                    UNION ALL
                                    SELECT city FROM misb WHERE city IS NOT NULL AND city != ''
                                ) as combined_cities
                                GROUP BY city
                                ORDER BY count DESC
                                LIMIT 10");

// Add Quest city data
$quest_cities = [];
$quest_city_result = $questConn->query("SELECT city, COUNT(*) as count FROM schools WHERE city IS NOT NULL AND city != '' GROUP BY city");
if ($quest_city_result) {
    while ($row = $quest_city_result->fetch_assoc()) {
        $quest_cities[$row['city']] = (int)$row['count'];
    }
}

// Combine the city data
$combined_cities = [];
foreach ($city_distribution as $city) {
    $combined_cities[$city['city']] = (int)$city['count'];
}

foreach ($quest_cities as $city => $count) {
    if (isset($combined_cities[$city])) {
        $combined_cities[$city] += $count;
    } else {
        $combined_cities[$city] = $count;
    }
}

// Sort by count and take top 10
arsort($combined_cities);
$combined_cities = array_slice($combined_cities, 0, 10, true);

$city_labels = [];
$city_counts = [];

foreach ($combined_cities as $city => $count) {
    $city_labels[] = $city;
    $city_counts[] = $count;
}

// Get schools/institutes distribution (top 10)
$school_distribution = fetch_all($conn, "SELECT institute_name, COUNT(*) as count 
                                 FROM (
                                     SELECT institute as institute_name FROM conclaves WHERE institute IS NOT NULL AND institute != ''
                                     UNION ALL
                                     SELECT institute as institute_name FROM leaderssummit WHERE institute IS NOT NULL AND institute != ''
                                     UNION ALL
                                     SELECT school_name as institute_name FROM misb WHERE school_name IS NOT NULL AND school_name != ''
                                     UNION ALL
                                     SELECT school as institute_name FROM yuva WHERE school IS NOT NULL AND school != ''
                                     UNION ALL
                                     SELECT school_name as institute_name FROM ils WHERE school_name IS NOT NULL AND school_name != ''
                                 ) as combined_institutes
                                 GROUP BY institute_name
                                 ORDER BY count DESC
                                 LIMIT 10");

// Add Quest school data
$quest_schools = [];
$quest_school_result = $questConn->query("SELECT school_name, COUNT(*) as count FROM schools WHERE school_name IS NOT NULL AND school_name != '' GROUP BY school_name");
if ($quest_school_result) {
    while ($row = $quest_school_result->fetch_assoc()) {
        $quest_schools[$row['school_name']] = (int)$row['count'];
    }
}

// Combine the school data
$combined_schools = [];
foreach ($school_distribution as $school) {
    $combined_schools[$school['institute_name']] = (int)$school['count'];
}

foreach ($quest_schools as $school => $count) {
    if (isset($combined_schools[$school])) {
        $combined_schools[$school] += $count;
    } else {
        $combined_schools[$school] = $count;
    }
}

// Sort by count and take top 10
arsort($combined_schools);
$combined_schools = array_slice($combined_schools, 0, 10, true);

$school_labels = [];
$school_counts = [];

foreach ($combined_schools as $school => $count) {
    $school_labels[] = $school;
    $school_counts[] = $count;
}

// Get registration counts by day of week
$weekday_data = fetch_all($conn, "SELECT WEEKDAY(created_at) as weekday, COUNT(*) as count 
                            FROM (
                                SELECT created_at FROM conclaves
                                UNION ALL
                                SELECT created_at FROM yuva
                                UNION ALL
                                SELECT created_at FROM leaderssummit
                                UNION ALL
                                SELECT created_at FROM misb
                                UNION ALL
                                SELECT created_at FROM ils
                            ) as combined_data
                            GROUP BY WEEKDAY(created_at)
                            ORDER BY weekday");

$weekday_names = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];
$weekday_counts = array_fill(0, 7, 0);

foreach ($weekday_data as $row) {
    $weekday_counts[$row['weekday']] = (int)$row['count'];
}

// Get registration counts by hour of day
$hour_data = fetch_all($conn, "SELECT HOUR(created_at) as hour, COUNT(*) as count 
                        FROM (
                            SELECT created_at FROM conclaves
                            UNION ALL
                            SELECT created_at FROM yuva
                            UNION ALL
                            SELECT created_at FROM leaderssummit
                            UNION ALL
                            SELECT created_at FROM misb
                            UNION ALL
                            SELECT created_at FROM ils
                        ) as combined_data
                        GROUP BY HOUR(created_at)
                        ORDER BY hour");

$hour_labels = [];
$hour_counts = array_fill(0, 24, 0);

for ($i = 0; $i < 24; $i++) {
    $hour_labels[] = sprintf("%02d:00", $i);
}

foreach ($hour_data as $row) {
    $hour_counts[$row['hour']] = (int)$row['count'];
}

// Close Quest database connection when done
$questConn->close();
?>

<div class="container-fluid">
    <!-- Summary Stats -->
    <div class="row mb-4">

        <div class="col-md-3 col-xl-3">
            <div class="stats-card text-center">
                <i class="fas fa-comments"></i>
                <h3><?php echo $conclave_count; ?></h3>
                <p>IPN Conclaves</p>
            </div>
        </div>
        <div class="col-md-3 col-xl-3">
            <div class="stats-card text-center">
                <i class="fas fa-graduation-cap"></i>
                <h3><?php echo $yuva_count; ?></h3>
                <p>Yuva Summit</p>
            </div>
        </div>
        <div class="col-md-3 col-xl-3">
            <div class="stats-card text-center">
                <i class="fas fa-globe-asia"></i>
                <h3><?php echo $leaderssummit_count; ?></h3>
                <p>Leaders Summit</p>
            </div>
        </div>
        <div class="col-md-3 col-xl-3">
            <div class="stats-card text-center">
                <i class="fas fa-award"></i>
                <h3><?php echo $misb_count; ?></h3>
                <p>Impactful Schools</p>
            </div>
        </div>
        <div class="col-md-3 col-xl-3">
            <div class="stats-card text-center">
                <i class="fas fa-landmark"></i>
                <h3><?php echo $ils_count; ?></h3>
                <p>IPN Leadership</p>
            </div>
        </div>
        <div class="col-md-3 col-xl-3">
            <div class="stats-card text-center">
                <i class="fas fa-trophy"></i>
                <h3><?php echo $quest_count; ?></h3>
                <p>Quest 2025</p>
            </div>
        </div>
        <div class="col-md-3 col-xl-6">
            <div class="stats-card text-center">
                <i class="fas fa-users"></i>
                <h3><?php echo $total_count; ?></h3>
                <p>Total Registrations</p>
            </div>
        </div>
    </div>
    
    <!-- Event Distribution Chart -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Event Registration Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="eventDistributionChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Monthly Registration Trends - Year Comparison</h5>
                </div>
                <div class="card-body">
                    <canvas id="yearlyComparisonChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- City and School Distribution -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Top 10 Cities</h5>
                </div>
                <div class="card-body">
                    <canvas id="cityDistributionChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Top 10 Institutions</h5>
                </div>
                <div class="card-body">
                    <canvas id="schoolDistributionChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Weekday and Hour Distribution -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Registrations by Day of Week</h5>
                </div>
                <div class="card-body">
                    <canvas id="weekdayDistributionChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Registrations by Hour of Day</h5>
                </div>
                <div class="card-body">
                    <canvas id="hourDistributionChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Key Insights Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Key Insights</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="border-start border-4 border-primary ps-3">
                                <h6>Most Active Event</h6>
                                <?php 
                                $events = [
                                    'IPN Conclaves' => $conclave_count,
                                    'Yuva Summit' => $yuva_count,
                                    'Leaders Summit' => $leaderssummit_count,
                                    'Impactful Schools' => $misb_count,
                                    'IPN Leadership' => $ils_count,
                                    'Quest 2025' => $quest_count
                                ];
                                arsort($events);
                                $most_active = key($events);
                                $most_active_count = current($events);
                                ?>
                                <p class="mb-0"><?php echo $most_active; ?> with <?php echo $most_active_count; ?> registrations</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="border-start border-4 border-success ps-3">
                                <h6>Most Active City</h6>
                                <?php 
                                $top_city = isset($city_labels[0]) ? $city_labels[0] : 'N/A';
                                $top_city_count = isset($city_counts[0]) ? $city_counts[0] : 0;
                                ?>
                                <p class="mb-0"><?php echo $top_city; ?> with <?php echo $top_city_count; ?> registrations</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="border-start border-4 border-info ps-3">
                                <h6>Most Active Institution</h6>
                                <?php 
                                $top_school = isset($school_labels[0]) ? $school_labels[0] : 'N/A';
                                $top_school_count = isset($school_counts[0]) ? $school_counts[0] : 0;
                                ?>
                                <p class="mb-0"><?php echo $top_school; ?> with <?php echo $top_school_count; ?> registrations</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="border-start border-4 border-warning ps-3">
                                <h6>Peak Registration Day</h6>
                                <?php 
                                $max_weekday = array_search(max($weekday_counts), $weekday_counts);
                                ?>
                                <p class="mb-0"><?php echo $weekday_names[$max_weekday]; ?> with <?php echo $weekday_counts[$max_weekday]; ?> registrations</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="border-start border-4 border-danger ps-3">
                                <h6>Peak Registration Hour</h6>
                                <?php 
                                $max_hour = array_search(max($hour_counts), $hour_counts);
                                ?>
                                <p class="mb-0"><?php echo $hour_labels[$max_hour]; ?> with <?php echo $hour_counts[$max_hour]; ?> registrations</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="border-start border-4 border-dark ps-3">
                                <h6>Year-over-Year Growth</h6>
                                <?php 
                                $current_year_total = array_sum($current_year_data);
                                $last_year_total = array_sum($last_year_data);
                                $growth_percentage = $last_year_total > 0 ? round((($current_year_total - $last_year_total) / $last_year_total) * 100, 2) : 0;
                                ?>
                                <p class="mb-0"><?php echo $growth_percentage; ?>% compared to <?php echo $last_year; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Event Distribution Chart
    const eventDistributionCtx = document.getElementById('eventDistributionChart').getContext('2d');
    new Chart(eventDistributionCtx, {
        type: 'doughnut',
        data: {
            labels: ['IPN Conclaves', 'Yuva Summit', 'Nepal Summit', 'Impactful Schools', 'IPN Leadership'],
            datasets: [{
                data: [
                    <?php echo $conclave_count; ?>,
                    <?php echo $yuva_count; ?>,
                    <?php echo $leaderssummit_count; ?>,
                    <?php echo $misb_count; ?>,
                    <?php echo $ils_count; ?>
                ],
                backgroundColor: [
                    'rgba(63, 81, 181, 0.7)',
                    'rgba(76, 175, 80, 0.7)',
                    'rgba(33, 150, 243, 0.7)',
                    'rgba(255, 193, 7, 0.7)',
                    'rgba(244, 67, 54, 0.7)'
                ],
                borderColor: [
                    'rgba(63, 81, 181, 1)',
                    'rgba(76, 175, 80, 1)',
                    'rgba(33, 150, 243, 1)',
                    'rgba(255, 193, 7, 1)',
                    'rgba(244, 67, 54, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'right',
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
    
    // Yearly Comparison Chart
    const yearlyComparisonCtx = document.getElementById('yearlyComparisonChart').getContext('2d');
    new Chart(yearlyComparisonCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($months); ?>,
            datasets: [
                {
                    label: '<?php echo $current_year; ?>',
                    data: <?php echo json_encode($current_year_data); ?>,
                    backgroundColor: 'rgba(63, 81, 181, 0.5)',
                    borderColor: 'rgba(63, 81, 181, 1)',
                    borderWidth: 1
                },
                {
                    label: '<?php echo $last_year; ?>',
                    data: <?php echo json_encode($last_year_data); ?>,
                    backgroundColor: 'rgba(33, 150, 243, 0.5)',
                    borderColor: 'rgba(33, 150, 243, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Month'
                    }
                },
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Registrations'
                    }
                }
            }
        }
    });
    
    // City Distribution Chart
    const cityDistributionCtx = document.getElementById('cityDistributionChart').getContext('2d');
    new Chart(cityDistributionCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($city_labels); ?>,
            datasets: [{
                label: 'Registrations by City',
                data: <?php echo json_encode($city_counts); ?>,
                backgroundColor: 'rgba(76, 175, 80, 0.5)',
                borderColor: 'rgba(76, 175, 80, 1)',
                borderWidth: 1
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            scales: {
                x: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Registrations'
                    }
                },
                y: {
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
    
    // School Distribution Chart
    const schoolDistributionCtx = document.getElementById('schoolDistributionChart').getContext('2d');
    new Chart(schoolDistributionCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($school_labels); ?>,
            datasets: [{
                label: 'Registrations by Institution',
                data: <?php echo json_encode($school_counts); ?>,
                backgroundColor: 'rgba(255, 193, 7, 0.5)',
                borderColor: 'rgba(255, 193, 7, 1)',
                borderWidth: 1
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            scales: {
                x: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Registrations'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Institution'
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
    
    // Weekday Distribution Chart
    const weekdayDistributionCtx = document.getElementById('weekdayDistributionChart').getContext('2d');
    new Chart(weekdayDistributionCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($weekday_names); ?>,
            datasets: [{
                label: 'Registrations by Day of Week',
                data: <?php echo json_encode($weekday_counts); ?>,
                backgroundColor: 'rgba(33, 150, 243, 0.5)',
                borderColor: 'rgba(33, 150, 243, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Registrations'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Day of Week'
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
    
    // Hour Distribution Chart
    const hourDistributionCtx = document.getElementById('hourDistributionChart').getContext('2d');
    new Chart(hourDistributionCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($hour_labels); ?>,
            datasets: [{
                label: 'Registrations by Hour of Day',
                data: <?php echo json_encode($hour_counts); ?>,
                backgroundColor: 'rgba(244, 67, 54, 0.2)',
                borderColor: 'rgba(244, 67, 54, 1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Registrations'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Hour of Day'
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
});
</script>

<?php
// Include footer
include 'includes/footer.php';
?> 