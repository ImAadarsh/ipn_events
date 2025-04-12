<?php
// Include header
include 'includes/header.php';

// Get available event types for this user
$viewable_events = getUserViewableEvents();

// Check if user has permission to view analytics
// Allow access if the user has permission to view at least one event type
if (empty($viewable_events) && !canViewEvent('analytics')) {
    echo '<div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            You do not have permission to view analytics data. 
            Please contact an administrator if you need access.
          </div>';
    include 'includes/footer.php';
    exit();
}

// Get total counts by event type
$conclave_count = 0;
$yuva_count = 0;
$leaderssummit_count = 0;
$misb_count = 0;
$ils_count = 0;
$quest_count = 0;

// Only get counts for events the user can view
if (in_array('conclaves', $viewable_events)) {
    $result = $conn->query("SELECT COUNT(*) as count FROM conclaves");
    $conclave_count = $result->fetch_assoc()['count'];
}

if (in_array('yuva', $viewable_events)) {
    $result = $conn->query("SELECT COUNT(*) as count FROM yuva");
    $yuva_count = $result->fetch_assoc()['count'];
}

if (in_array('leaderssummit', $viewable_events)) {
    $result = $conn->query("SELECT COUNT(*) as count FROM leaderssummit");
    $leaderssummit_count = $result->fetch_assoc()['count'];
}

if (in_array('misb', $viewable_events)) {
    $result = $conn->query("SELECT COUNT(*) as count FROM misb");
    $misb_count = $result->fetch_assoc()['count'];
}

if (in_array('ils', $viewable_events)) {
    $result = $conn->query("SELECT COUNT(*) as count FROM ils");
    $ils_count = $result->fetch_assoc()['count'];
}

// Get Quest count from different database using the centralized function
if (in_array('quest', $viewable_events)) {
    $questConn = connectQuestDB();
    $quest_count = 0;
    $quest_result = $questConn->query("SELECT COUNT(*) as count FROM schools");
    if ($quest_result) {
        $quest_count = $quest_result->fetch_assoc()['count'];
    }
}

$total_count = $conclave_count + $yuva_count + $leaderssummit_count + $misb_count + $ils_count + $quest_count;

// Get registration trends (count by month for the last 2 years)
$current_year = date('Y');
$last_year = $current_year - 1;
$months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

// Initialize arrays for trends
$current_year_data = array_fill(0, 12, 0);
$last_year_data = array_fill(0, 12, 0);

// Build UNION ALL query only for events the user can view
$current_year_query_parts = [];
$last_year_query_parts = [];

if (in_array('conclaves', $viewable_events)) {
    $current_year_query_parts[] = "SELECT created_at FROM conclaves WHERE YEAR(created_at) = '$current_year'";
    $last_year_query_parts[] = "SELECT created_at FROM conclaves WHERE YEAR(created_at) = '$last_year'";
}

if (in_array('yuva', $viewable_events)) {
    $current_year_query_parts[] = "SELECT created_at FROM yuva WHERE YEAR(created_at) = '$current_year'";
    $last_year_query_parts[] = "SELECT created_at FROM yuva WHERE YEAR(created_at) = '$last_year'";
}

if (in_array('leaderssummit', $viewable_events)) {
    $current_year_query_parts[] = "SELECT created_at FROM leaderssummit WHERE YEAR(created_at) = '$current_year'";
    $last_year_query_parts[] = "SELECT created_at FROM leaderssummit WHERE YEAR(created_at) = '$last_year'";
}

if (in_array('misb', $viewable_events)) {
    $current_year_query_parts[] = "SELECT created_at FROM misb WHERE YEAR(created_at) = '$current_year'";
    $last_year_query_parts[] = "SELECT created_at FROM misb WHERE YEAR(created_at) = '$last_year'";
}

if (in_array('ils', $viewable_events)) {
    $current_year_query_parts[] = "SELECT created_at FROM ils WHERE YEAR(created_at) = '$current_year'";
    $last_year_query_parts[] = "SELECT created_at FROM ils WHERE YEAR(created_at) = '$last_year'";
}

// Populate current year data if there are viewable events
if (!empty($current_year_query_parts)) {
    $current_year_query = "SELECT MONTH(created_at) as month, COUNT(*) as count 
                        FROM (" . implode(" UNION ALL ", $current_year_query_parts) . ") as combined_data
                        GROUP BY MONTH(created_at)";
    
    $current_year_monthly = fetch_all($conn, $current_year_query);
    
    foreach ($current_year_monthly as $row) {
        $current_year_data[$row['month'] - 1] = (int)$row['count'];
    }
}

// Populate last year data if there are viewable events
if (!empty($last_year_query_parts)) {
    $last_year_query = "SELECT MONTH(created_at) as month, COUNT(*) as count 
                     FROM (" . implode(" UNION ALL ", $last_year_query_parts) . ") as combined_data
                     GROUP BY MONTH(created_at)";
    
    $last_year_monthly = fetch_all($conn, $last_year_query);
    
    foreach ($last_year_monthly as $row) {
        $last_year_data[$row['month'] - 1] = (int)$row['count'];
    }
}

// Populate Quest trends (from different database)
if (in_array('quest', $viewable_events)) {
    $questConn = connectQuestDB();
    
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
}

// Get city distribution data (top 10 cities) - only for viewable events
$city_query_parts = [];

if (in_array('conclaves', $viewable_events)) {
    $city_query_parts[] = "SELECT city FROM conclaves WHERE city IS NOT NULL AND city != ''";
}

if (in_array('leaderssummit', $viewable_events)) {
    $city_query_parts[] = "SELECT city FROM leaderssummit WHERE city IS NOT NULL AND city != ''";
}

if (in_array('ils', $viewable_events)) {
    $city_query_parts[] = "SELECT city FROM ils WHERE city IS NOT NULL AND city != ''";
}

if (in_array('misb', $viewable_events)) {
    $city_query_parts[] = "SELECT city FROM misb WHERE city IS NOT NULL AND city != ''";
}

$city_distribution = [];
if (!empty($city_query_parts)) {
    $city_query = "SELECT city, COUNT(*) as count 
                FROM (" . implode(" UNION ALL ", $city_query_parts) . ") as combined_cities
                GROUP BY city
                ORDER BY count DESC
                LIMIT 10";
                
    $city_distribution = fetch_all($conn, $city_query);
}

// Add Quest city data if user has access
$quest_cities = [];
if (in_array('quest', $viewable_events)) {
    $quest_city_result = $questConn->query("SELECT city, COUNT(*) as count FROM schools WHERE city IS NOT NULL AND city != '' GROUP BY city");
    if ($quest_city_result) {
        while ($row = $quest_city_result->fetch_assoc()) {
            $quest_cities[$row['city']] = (int)$row['count'];
        }
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

// Get schools/institutes distribution (top 10) - only for viewable events
$school_query_parts = [];

if (in_array('conclaves', $viewable_events)) {
    $school_query_parts[] = "SELECT institute as institute_name FROM conclaves WHERE institute IS NOT NULL AND institute != ''";
}

if (in_array('leaderssummit', $viewable_events)) {
    $school_query_parts[] = "SELECT institute as institute_name FROM leaderssummit WHERE institute IS NOT NULL AND institute != ''";
}

if (in_array('misb', $viewable_events)) {
    $school_query_parts[] = "SELECT school_name as institute_name FROM misb WHERE school_name IS NOT NULL AND school_name != ''";
}

if (in_array('yuva', $viewable_events)) {
    $school_query_parts[] = "SELECT school as institute_name FROM yuva WHERE school IS NOT NULL AND school != ''";
}

if (in_array('ils', $viewable_events)) {
    $school_query_parts[] = "SELECT school_name as institute_name FROM ils WHERE school_name IS NOT NULL AND school_name != ''";
}

$school_distribution = [];
if (!empty($school_query_parts)) {
    $school_query = "SELECT institute_name, COUNT(*) as count 
                   FROM (" . implode(" UNION ALL ", $school_query_parts) . ") as combined_institutes
                   GROUP BY institute_name
                   ORDER BY count DESC
                   LIMIT 10";
                   
    $school_distribution = fetch_all($conn, $school_query);
}

// Add Quest school data if user has access
$quest_schools = [];
if (in_array('quest', $viewable_events)) {
    $quest_school_result = $questConn->query("SELECT school_name, COUNT(*) as count FROM schools WHERE school_name IS NOT NULL AND school_name != '' GROUP BY school_name");
    if ($quest_school_result) {
        while ($row = $quest_school_result->fetch_assoc()) {
            $quest_schools[$row['school_name']] = (int)$row['count'];
        }
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

// Get registration counts by day of week - only for viewable events
$weekday_query_parts = [];

if (in_array('conclaves', $viewable_events)) {
    $weekday_query_parts[] = "SELECT created_at FROM conclaves";
}

if (in_array('yuva', $viewable_events)) {
    $weekday_query_parts[] = "SELECT created_at FROM yuva";
}

if (in_array('leaderssummit', $viewable_events)) {
    $weekday_query_parts[] = "SELECT created_at FROM leaderssummit";
}

if (in_array('misb', $viewable_events)) {
    $weekday_query_parts[] = "SELECT created_at FROM misb";
}

if (in_array('ils', $viewable_events)) {
    $weekday_query_parts[] = "SELECT created_at FROM ils";
}

$weekday_data = [];
if (!empty($weekday_query_parts)) {
    $weekday_query = "SELECT WEEKDAY(created_at) as weekday, COUNT(*) as count 
                   FROM (" . implode(" UNION ALL ", $weekday_query_parts) . ") as combined_data
                   GROUP BY WEEKDAY(created_at)
                   ORDER BY weekday";
                   
    $weekday_data = fetch_all($conn, $weekday_query);
}

$weekday_names = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];
$weekday_counts = array_fill(0, 7, 0);

foreach ($weekday_data as $row) {
    $weekday_counts[$row['weekday']] = (int)$row['count'];
}

// Add Quest weekday data if user has access
if (in_array('quest', $viewable_events)) {
    $quest_weekday_result = $questConn->query("SELECT WEEKDAY(created_at) as weekday, COUNT(*) as count 
                                         FROM schools 
                                         GROUP BY WEEKDAY(created_at)");
    if ($quest_weekday_result) {
        while ($row = $quest_weekday_result->fetch_assoc()) {
            $weekday = (int)$row['weekday'];
            if (isset($weekday_counts[$weekday])) {
                $weekday_counts[$weekday] += (int)$row['count'];
            }
        }
    }
}

// Get registration counts by hour of day - only for viewable events
$hour_query_parts = [];

if (in_array('conclaves', $viewable_events)) {
    $hour_query_parts[] = "SELECT created_at FROM conclaves";
}

if (in_array('yuva', $viewable_events)) {
    $hour_query_parts[] = "SELECT created_at FROM yuva";
}

if (in_array('leaderssummit', $viewable_events)) {
    $hour_query_parts[] = "SELECT created_at FROM leaderssummit";
}

if (in_array('misb', $viewable_events)) {
    $hour_query_parts[] = "SELECT created_at FROM misb";
}

if (in_array('ils', $viewable_events)) {
    $hour_query_parts[] = "SELECT created_at FROM ils";
}

$hour_data = [];
if (!empty($hour_query_parts)) {
    $hour_query = "SELECT HOUR(created_at) as hour, COUNT(*) as count 
                FROM (" . implode(" UNION ALL ", $hour_query_parts) . ") as combined_data
                GROUP BY HOUR(created_at)
                ORDER BY hour";
                
    $hour_data = fetch_all($conn, $hour_query);
}

$hour_labels = [];
$hour_counts = array_fill(0, 24, 0);

for ($i = 0; $i < 24; $i++) {
    $hour_labels[] = sprintf("%02d:00", $i);
}

foreach ($hour_data as $row) {
    $hour_counts[$row['hour']] = (int)$row['count'];
}

// Add Quest hour data if user has access
if (in_array('quest', $viewable_events)) {
    $quest_hour_result = $questConn->query("SELECT HOUR(created_at) as hour, COUNT(*) as count 
                                      FROM schools 
                                      GROUP BY HOUR(created_at)");
    if ($quest_hour_result) {
        while ($row = $quest_hour_result->fetch_assoc()) {
            $hour = (int)$row['hour'];
            if (isset($hour_counts[$hour])) {
                $hour_counts[$hour] += (int)$row['count'];
            }
        }
    }
}

// Close Quest database connection when done
if (in_array('quest', $viewable_events)) {
    $questConn->close();
}
?>

<div class="container-fluid">
    <!-- Summary Stats -->
    <div class="row mb-4">
        <?php if (in_array('conclaves', $viewable_events)): ?>
        <div class="col-md-3 col-xl-3">
            <div class="stats-card text-center">
                <i class="fas fa-comments"></i>
                <h3><?php echo $conclave_count; ?></h3>
                <p>IPN Conclaves</p>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (in_array('yuva', $viewable_events)): ?>
        <div class="col-md-3 col-xl-3">
            <div class="stats-card text-center">
                <i class="fas fa-graduation-cap"></i>
                <h3><?php echo $yuva_count; ?></h3>
                <p>Yuva Summit</p>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (in_array('leaderssummit', $viewable_events)): ?>
        <div class="col-md-3 col-xl-3">
            <div class="stats-card text-center">
                <i class="fas fa-globe-asia"></i>
                <h3><?php echo $leaderssummit_count; ?></h3>
                <p>Leaders Summit Nepal</p>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (in_array('misb', $viewable_events)): ?>
        <div class="col-md-3 col-xl-3">
            <div class="stats-card text-center">
                <i class="fas fa-award"></i>
                <h3><?php echo $misb_count; ?></h3>
                <p>Impactful Schools</p>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (in_array('ils', $viewable_events)): ?>
        <div class="col-md-3 col-xl-3">
            <div class="stats-card text-center">
                <i class="fas fa-landmark"></i>
                <h3><?php echo $ils_count; ?></h3>
                <p>IPN Leadership</p>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (in_array('quest', $viewable_events)): ?>
        <div class="col-md-3 col-xl-3">
            <div class="stats-card text-center">
                <i class="fas fa-trophy"></i>
                <h3><?php echo $quest_count; ?></h3>
                <p>Quest 2025</p>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="col-md-3 col-xl-6">
            <div class="stats-card text-center">
                <i class="fas fa-users"></i>
                <h3><?php echo $total_count; ?></h3>
                <p>Total Registrations</p>
            </div>
        </div>
    </div>
    
    <?php if (!empty($viewable_events)): ?>
    <!-- Event Distribution Chart -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Event Registration Distribution</h5>
                </div>
                <div class="card-body" style="height: 300px;">
                    <canvas id="eventDistributionChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Monthly Registration Trends - Year Comparison</h5>
                </div>
                <div class="card-body" style="height: 300px;">
                    <canvas id="yearlyComparisonChart"></canvas>
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
                <div class="card-body" style="height: 300px;">
                    <canvas id="cityDistributionChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Top 10 Institutions</h5>
                </div>
                <div class="card-body" style="height: 300px;">
                    <canvas id="schoolDistributionChart"></canvas>
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
                <div class="card-body" style="height: 300px;">
                    <canvas id="weekdayDistributionChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Registrations by Hour of Day</h5>
                </div>
                <div class="card-body" style="height: 300px;">
                    <canvas id="hourDistributionChart"></canvas>
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
                                $events = [];
                                if (in_array('conclaves', $viewable_events)) {
                                    $events['IPN Conclaves'] = $conclave_count;
                                }
                                if (in_array('yuva', $viewable_events)) {
                                    $events['Yuva Summit'] = $yuva_count;
                                }
                                if (in_array('leaderssummit', $viewable_events)) {
                                    $events['Leaders Summit Nepal'] = $leaderssummit_count;
                                }
                                if (in_array('misb', $viewable_events)) {
                                    $events['Impactful Schools'] = $misb_count;
                                }
                                if (in_array('ils', $viewable_events)) {
                                    $events['IPN Leadership'] = $ils_count;
                                }
                                if (in_array('quest', $viewable_events)) {
                                    $events['Quest 2025'] = $quest_count;
                                }
                                
                                if (!empty($events)) {
                                    arsort($events);
                                    $most_active = key($events);
                                    $most_active_count = current($events);
                                ?>
                                <p class="mb-0"><?php echo $most_active; ?> with <?php echo $most_active_count; ?> registrations</p>
                                <?php } else { ?>
                                <p class="mb-0">No data available</p>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="border-start border-4 border-success ps-3">
                                <h6>Most Active City</h6>
                                <?php 
                                $top_city = isset($city_labels[0]) ? $city_labels[0] : 'N/A';
                                $top_city_count = isset($city_counts[0]) ? $city_counts[0] : 0;
                                if ($top_city_count > 0) {
                                ?>
                                <p class="mb-0"><?php echo $top_city; ?> with <?php echo $top_city_count; ?> registrations</p>
                                <?php } else { ?>
                                <p class="mb-0">No data available</p>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="border-start border-4 border-info ps-3">
                                <h6>Most Active Institution</h6>
                                <?php 
                                $top_school = isset($school_labels[0]) ? $school_labels[0] : 'N/A';
                                $top_school_count = isset($school_counts[0]) ? $school_counts[0] : 0;
                                if ($top_school_count > 0) {
                                ?>
                                <p class="mb-0"><?php echo $top_school; ?> with <?php echo $top_school_count; ?> registrations</p>
                                <?php } else { ?>
                                <p class="mb-0">No data available</p>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="border-start border-4 border-warning ps-3">
                                <h6>Peak Registration Day</h6>
                                <?php 
                                $max_weekday = array_search(max($weekday_counts), $weekday_counts);
                                $max_weekday_count = max($weekday_counts);
                                if ($max_weekday_count > 0) {
                                ?>
                                <p class="mb-0"><?php echo $weekday_names[$max_weekday]; ?> with <?php echo $weekday_counts[$max_weekday]; ?> registrations</p>
                                <?php } else { ?>
                                <p class="mb-0">No data available</p>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="border-start border-4 border-danger ps-3">
                                <h6>Peak Registration Hour</h6>
                                <?php 
                                $max_hour = array_search(max($hour_counts), $hour_counts);
                                $max_hour_count = max($hour_counts);
                                if ($max_hour_count > 0) {
                                ?>
                                <p class="mb-0"><?php echo $hour_labels[$max_hour]; ?> with <?php echo $hour_counts[$max_hour]; ?> registrations</p>
                                <?php } else { ?>
                                <p class="mb-0">No data available</p>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="border-start border-4 border-dark ps-3">
                                <h6>Year-over-Year Growth</h6>
                                <?php 
                                $current_year_total = array_sum($current_year_data);
                                $last_year_total = array_sum($last_year_data);
                                $growth_percentage = $last_year_total > 0 ? round((($current_year_total - $last_year_total) / $last_year_total) * 100, 2) : 0;
                                if ($current_year_total > 0 || $last_year_total > 0) {
                                ?>
                                <p class="mb-0"><?php echo $growth_percentage; ?>% compared to <?php echo $last_year; ?></p>
                                <?php } else { ?>
                                <p class="mb-0">No data available</p>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="row">
        <div class="col-12">
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                You don't have access to any event data. Please contact an administrator to request access.
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($viewable_events)): ?>
    // Event Distribution Chart
    const eventDistributionCtx = document.getElementById('eventDistributionChart').getContext('2d');
    new Chart(eventDistributionCtx, {
        type: 'pie',
        data: {
            labels: [
                <?php if (in_array('conclaves', $viewable_events)): ?>'IPN Conclaves',<?php endif; ?>
                <?php if (in_array('yuva', $viewable_events)): ?>'Yuva Summit',<?php endif; ?>
                <?php if (in_array('leaderssummit', $viewable_events)): ?>'Leaders Summit Nepal',<?php endif; ?>
                <?php if (in_array('misb', $viewable_events)): ?>'Impactful Schools',<?php endif; ?>
                <?php if (in_array('ils', $viewable_events)): ?>'IPN Leadership',<?php endif; ?>
                <?php if (in_array('quest', $viewable_events)): ?>'Quest 2025'<?php endif; ?>
            ],
            datasets: [{
                data: [
                    <?php if (in_array('conclaves', $viewable_events)): ?><?php echo $conclave_count; ?>,<?php endif; ?>
                    <?php if (in_array('yuva', $viewable_events)): ?><?php echo $yuva_count; ?>,<?php endif; ?>
                    <?php if (in_array('leaderssummit', $viewable_events)): ?><?php echo $leaderssummit_count; ?>,<?php endif; ?>
                    <?php if (in_array('misb', $viewable_events)): ?><?php echo $misb_count; ?>,<?php endif; ?>
                    <?php if (in_array('ils', $viewable_events)): ?><?php echo $ils_count; ?>,<?php endif; ?>
                    <?php if (in_array('quest', $viewable_events)): ?><?php echo $quest_count; ?><?php endif; ?>
                ],
                backgroundColor: [
                    <?php if (in_array('conclaves', $viewable_events)): ?>'rgba(255, 99, 132, 0.7)',<?php endif; ?>
                    <?php if (in_array('yuva', $viewable_events)): ?>'rgba(54, 162, 235, 0.7)',<?php endif; ?>
                    <?php if (in_array('leaderssummit', $viewable_events)): ?>'rgba(255, 206, 86, 0.7)',<?php endif; ?>
                    <?php if (in_array('misb', $viewable_events)): ?>'rgba(75, 192, 192, 0.7)',<?php endif; ?>
                    <?php if (in_array('ils', $viewable_events)): ?>'rgba(153, 102, 255, 0.7)',<?php endif; ?>
                    <?php if (in_array('quest', $viewable_events)): ?>'rgba(255, 159, 64, 0.7)'<?php endif; ?>
                ],
                borderColor: [
                    <?php if (in_array('conclaves', $viewable_events)): ?>'rgba(255, 99, 132, 1)',<?php endif; ?>
                    <?php if (in_array('yuva', $viewable_events)): ?>'rgba(54, 162, 235, 1)',<?php endif; ?>
                    <?php if (in_array('leaderssummit', $viewable_events)): ?>'rgba(255, 206, 86, 1)',<?php endif; ?>
                    <?php if (in_array('misb', $viewable_events)): ?>'rgba(75, 192, 192, 1)',<?php endif; ?>
                    <?php if (in_array('ils', $viewable_events)): ?>'rgba(153, 102, 255, 1)',<?php endif; ?>
                    <?php if (in_array('quest', $viewable_events)): ?>'rgba(255, 159, 64, 1)'<?php endif; ?>
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'right',
                }
            }
        }
    });
    
    // Monthly Comparison Chart
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
            maintainAspectRatio: true,
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
            maintainAspectRatio: true,
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
            maintainAspectRatio: true,
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
            maintainAspectRatio: true,
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
            maintainAspectRatio: true,
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
    <?php endif; ?>
});
</script>

<?php
// Include footer
include 'includes/footer.php';
?> 