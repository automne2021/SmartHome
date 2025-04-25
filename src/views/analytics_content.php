<?php
require_once dirname(__FILE__) . '/../../src/config/database.php';

// Set page title and CSS
$pageTitle = "Analytics Dashboard";
$additionalCss = ["analytics-modern"];

// Initialize database
$database = new Database();
$db = $database->getConnection();

// Get range parameter or default to 24h
$range = isset($_GET['range']) ? $_GET['range'] : '24h';

// Define time intervals based on range
switch ($range) {
    case '7d':
        $interval = '7 DAY';
        $groupBy = 'DATE_FORMAT(timestamp, "%Y-%m-%d")';
        $labelFormat = 'M d';
        $rangeTitle = 'Last 7 Days';
        break;
    case '30d':
        $interval = '30 DAY';
        $groupBy = 'DATE_FORMAT(timestamp, "%Y-%m-%d")';
        $labelFormat = 'M d';
        $rangeTitle = 'Last 30 Days';
        break;
    default: // 24h
        $interval = '24 HOUR';
        $groupBy = 'DATE_FORMAT(timestamp, "%H:00")';
        $labelFormat = 'H:i';
        $rangeTitle = 'Last 24 Hours';
        break;
}

// Function to safely execute query and return data
function safeQueryFetch($db, $query, $defaultReturn = [])
{
    try {
        $stmt = $db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Log the error in a production environment
        // error_log("Database query error: " . $e->getMessage());
        return $defaultReturn;
    }
}

// Function to format minutes for display
function formatMinutes($minutes)
{
    if ($minutes < 60) {
        return round($minutes) . " min";
    } else {
        $hours = floor($minutes / 60);
        $mins = round($minutes % 60);
        return $hours . "h " . $mins . "m";
    }
}

// Get temperature data
$tempQuery = "SELECT AVG(value) as value, $groupBy as time_interval 
              FROM sensor_data 
              WHERE sensor_type = 'temperature' 
              AND timestamp > DATE_SUB(NOW(), INTERVAL $interval)
              GROUP BY time_interval
              ORDER BY time_interval ASC";
$tempData = safeQueryFetch($db, $tempQuery);

// Get humidity data
$humQuery = "SELECT AVG(value) as value, $groupBy as time_interval 
             FROM sensor_data 
             WHERE sensor_type = 'humidity' 
             AND timestamp > DATE_SUB(NOW(), INTERVAL $interval)
             GROUP BY time_interval
             ORDER BY time_interval ASC";
$humData = safeQueryFetch($db, $humQuery);

// Format data for charts
$temperatureValues = [];
$temperatureLabels = [];
$humidityValues = [];
$humidityLabels = [];

foreach ($tempData as $data) {
    $temperatureValues[] = round($data['value'], 1);
    $temperatureLabels[] = $range === '24h' ?
        date($labelFormat, strtotime($data['time_interval'])) :
        date($labelFormat, strtotime($data['time_interval']));
}

foreach ($humData as $data) {
    $humidityValues[] = round($data['value'], 1);
    $humidityLabels[] = $range === '24h' ?
        date($labelFormat, strtotime($data['time_interval'])) :
        date($labelFormat, strtotime($data['time_interval']));
}

// Calculate statistics
function getStats($values)
{
    if (empty($values)) return [
        'avg' => 'N/A',
        'min' => 'N/A',
        'max' => 'N/A',
        'variance' => 'N/A'
    ];

    return [
        'avg' => round(array_sum($values) / count($values), 1),
        'min' => round(min($values), 1),
        'max' => round(max($values), 1),
        'variance' => round(max($values) - min($values), 1)
    ];
}

$tempStats = getStats($temperatureValues);
$humStats = getStats($humidityValues);

// Define date range based on the selected period
$startDate = date('Y-m-d', strtotime('-' . str_replace(['h', 'd'], ['hour', 'day'], $range)));
$endDate = date('Y-m-d');

// Get device usage data with minutes instead of hours
$usageQuery = "SELECT d.name, SUM(du.usage_minutes) as total_minutes,
              AVG(du.usage_minutes) as avg_minutes,
              COUNT(DISTINCT du.usage_date) as days_used,
              MAX(du.usage_date) as last_used
              FROM device_usage du
              JOIN devices d ON du.device_id = d.id
              WHERE du.usage_date BETWEEN :startDate AND :endDate
              GROUP BY du.device_id
              ORDER BY total_minutes DESC";

$usageStmt = $db->prepare($usageQuery);
$usageStmt->bindParam(':startDate', $startDate);
$usageStmt->bindParam(':endDate', $endDate);
$usageStmt->execute();
$usageData = $usageStmt->fetchAll(PDO::FETCH_ASSOC);

// If no device usage data is found, generate sample data
if (empty($usageData)) {
    // Sample device usage data (in minutes)
    $sampleUsageData = [
        ['device_id' => 1, 'name' => 'Front Door', 'total_minutes' => 320, 'avg_minutes' => 45.7, 'days_used' => 7, 'last_used' => date('Y-m-d')],
        ['device_id' => 2, 'name' => 'Living Room Lamp', 'total_minutes' => 1240, 'avg_minutes' => 177.1, 'days_used' => 7, 'last_used' => date('Y-m-d')],
        ['device_id' => 3, 'name' => 'Bedroom Lamp', 'total_minutes' => 840, 'avg_minutes' => 120.0, 'days_used' => 7, 'last_used' => date('Y-m-d')],
        ['device_id' => 4, 'name' => 'Ceiling Fan', 'total_minutes' => 780, 'avg_minutes' => 111.4, 'days_used' => 7, 'last_used' => date('Y-m-d')],
    ];

    $usageData = $sampleUsageData;

    // Also update deviceUsageData to use minutes-based values
    $deviceUsageData = [];
    foreach ($sampleUsageData as $device) {
        $deviceUsageData[] = [
            'name' => $device['name'],
            'hours' => round($device['total_minutes'] / 60, 1),
            'percentage' => min(100, round(($device['total_minutes'] / 60) / 24 * 100))
        ];
    }

    // Log that we're using sample data
    error_log("Using sample device usage data for analytics");
} else {
    // Convert minutes-based $usageData to hours-based $deviceUsageData for the chart
    $deviceUsageData = [];
    foreach ($usageData as $device) {
        $deviceUsageData[] = [
            'name' => $device['name'],
            'hours' => round($device['total_minutes'] / 60, 1),
            'percentage' => min(100, round(($device['total_minutes'] / 60) / 24 * 100))
        ];
    }
}

// If no data is found, provide some defaults
if (empty($deviceUsageData)) {
    $deviceUsageData = [
        ['name' => 'Living Room Light', 'hours' => 12.5, 'percentage' => 85],
        ['name' => 'Bedroom Fan', 'hours' => 8.2, 'percentage' => 65],
        ['name' => 'Ceiling Fan', 'hours' => 4.8, 'percentage' => 40]
    ];
}

// Make sure the percentage is properly calculated
foreach ($deviceUsageData as &$device) {
    if (!isset($device['percentage'])) {
        $device['percentage'] = ($device['hours'] / 24) * 100;
    }
    // Round percentage to integer
    $device['percentage'] = round($device['percentage']);
}
?>

<div class="analytics-dashboard">
    <div class="dashboard-header-content">
        <h1><i class="fas fa-chart-line"></i> Analytics Dashboard</h1>
        <div class="dashboard-controls">
            <div class="time-range-selector">
                <a href="?action=analytics&range=24h" class="range-btn <?php echo $range === '24h' ? 'active' : ''; ?>">24 Hours</a>
                <a href="?action=analytics&range=7d" class="range-btn <?php echo $range === '7d' ? 'active' : ''; ?>">7 Days</a>
                <a href="?action=analytics&range=30d" class="range-btn <?php echo $range === '30d' ? 'active' : ''; ?>">30 Days</a>
                <button class="range-btn" id="custom-range-btn">Custom</button>
            </div>
            <div class="date-display">
                <i class="fas fa-calendar-alt"></i> <?php echo $rangeTitle; ?>
            </div>
        </div>
    </div>

    <div class="dashboard-stats-overview">
        <div class="overview-card temperature">
            <div class="overview-icon">
                <i class="fas fa-temperature-high"></i>
            </div>
            <div class="overview-data">
                <div class="overview-title">Avg. Temperature</div>
                <div class="overview-value"><?php echo $tempStats['avg']; ?>°C</div>
                <div class="overview-trend">
                    <?php if ($tempStats['avg'] !== 'N/A' && !empty($temperatureValues) && end($temperatureValues) > $tempStats['avg']): ?>
                        <i class="fas fa-arrow-up"></i> Above Average
                    <?php elseif ($tempStats['avg'] !== 'N/A' && !empty($temperatureValues)): ?>
                        <i class="fas fa-arrow-down"></i> Below Average
                    <?php else: ?>
                        <i class="fas fa-minus"></i> No Data
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="overview-card humidity">
            <div class="overview-icon">
                <i class="fas fa-tint"></i>
            </div>
            <div class="overview-data">
                <div class="overview-title">Avg. Humidity</div>
                <div class="overview-value"><?php echo $humStats['avg']; ?>%</div>
                <div class="overview-trend">
                    <?php if ($humStats['avg'] !== 'N/A' && !empty($humidityValues) && end($humidityValues) > $humStats['avg']): ?>
                        <i class="fas fa-arrow-up"></i> Above Average
                    <?php elseif ($humStats['avg'] !== 'N/A' && !empty($humidityValues)): ?>
                        <i class="fas fa-arrow-down"></i> Below Average
                    <?php else: ?>
                        <i class="fas fa-minus"></i> No Data
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="analytics-grid">
        <!-- Temperature Analysis Card -->
        <div class="analytics-card temperature-card">
            <div class="card-header">
                <h2><i class="fas fa-temperature-high"></i> Temperature Analysis</h2>
                <div class="card-actions">
                    <button class="btn-card-action" data-action="download" data-chart="temperature">
                        <i class="fas fa-download"></i>
                    </button>
                    <button class="btn-card-action" data-action="expand" data-chart="temperature">
                        <i class="fas fa-expand-alt"></i>
                    </button>
                </div>
            </div>
            <div class="chart-container">
                <?php if (empty($temperatureValues)): ?>
                    <div class="no-data-message">
                        <i class="fas fa-chart-area"></i>
                        <p>No temperature data available for this period</p>
                    </div>
                <?php else: ?>
                    <canvas id="temperatureChart"></canvas>
                <?php endif; ?>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-calculator"></i></div>
                    <div class="stat-label">Average</div>
                    <div class="stat-value"><?php echo $tempStats['avg']; ?>°C</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-arrow-down"></i></div>
                    <div class="stat-label">Minimum</div>
                    <div class="stat-value"><?php echo $tempStats['min']; ?>°C</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-arrow-up"></i></div>
                    <div class="stat-label">Maximum</div>
                    <div class="stat-value"><?php echo $tempStats['max']; ?>°C</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-exchange-alt"></i></div>
                    <div class="stat-label">Variance</div>
                    <div class="stat-value"><?php echo $tempStats['variance']; ?>°C</div>
                </div>
            </div>
        </div>

        <!-- Humidity Analysis Card -->
        <div class="analytics-card humidity-card">
            <div class="card-header">
                <h2><i class="fas fa-tint"></i> Humidity Analysis</h2>
                <div class="card-actions">
                    <button class="btn-card-action" data-action="download" data-chart="humidity">
                        <i class="fas fa-download"></i>
                    </button>
                    <button class="btn-card-action" data-action="expand" data-chart="humidity">
                        <i class="fas fa-expand-alt"></i>
                    </button>
                </div>
            </div>
            <div class="chart-container">
                <?php if (empty($humidityValues)): ?>
                    <div class="no-data-message">
                        <i class="fas fa-chart-area"></i>
                        <p>No humidity data available for this period</p>
                    </div>
                <?php else: ?>
                    <canvas id="humidityChart"></canvas>
                <?php endif; ?>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-calculator"></i></div>
                    <div class="stat-label">Average</div>
                    <div class="stat-value"><?php echo $humStats['avg']; ?>%</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-arrow-down"></i></div>
                    <div class="stat-label">Minimum</div>
                    <div class="stat-value"><?php echo $humStats['min']; ?>%</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-arrow-up"></i></div>
                    <div class="stat-label">Maximum</div>
                    <div class="stat-value"><?php echo $humStats['max']; ?>%</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-exchange-alt"></i></div>
                    <div class="stat-label">Variance</div>
                    <div class="stat-value"><?php echo $humStats['variance']; ?>%</div>
                </div>
            </div>
        </div>

        <!-- Device Usage Analysis Card -->
        <div class="analytics-card device-card full-width">
            <div class="card-header">
                <h2><i class="fas fa-plug"></i> Device Usage</h2>
                <div class="card-actions">
                    <button class="btn-card-action" data-action="download" data-chart="device">
                        <i class="fas fa-download"></i>
                    </button>
                    <button class="btn-card-action" data-action="expand" data-chart="device">
                        <i class="fas fa-expand-alt"></i>
                    </button>
                </div>
            </div>
            <div class="chart-container device-usage-container">
                <div class="device-chart-wrapper">
                    <canvas id="deviceUsageChart"></canvas>
                </div>
                <div class="device-usage-table">
                    <div class="device-usage-list">
                        <?php if (empty($usageData)): ?>
                            <div class="no-data-message">
                                <i class="fas fa-plug"></i>
                                <p>No device usage data available for this period</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($usageData as $device): ?>
                                <div class="device-usage-item">
                                    <?php
                                    $icon = strpos($device['name'], 'Light') !== false || strpos($device['name'], 'Lamp') !== false ?
                                        'lightbulb' : (strpos($device['name'], 'Fan') !== false ?
                                            'fan' : (strpos($device['name'], 'Door') !== false ?
                                                'door-open' : 'plug'));

                                    // Calculate percentage (max usage across devices as 100%)
                                    $maxUsage = max(array_column($usageData, 'total_minutes'));
                                    $percentage = $maxUsage > 0 ? ($device['total_minutes'] / $maxUsage) * 100 : 0;
                                    ?>
                                    <div class="device-icon"><i class="fas fa-<?php echo $icon; ?>"></i></div>
                                    <div class="device-details">
                                        <div class="device-name"><?php echo htmlspecialchars($device['name']); ?></div>
                                        <div class="device-usage-bar">
                                            <div class="device-usage-progress" style="width: <?php echo round($percentage); ?>%"></div>
                                        </div>
                                        <div class="device-usage-time"><?php echo formatMinutes($device['total_minutes']); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Date Range Modal -->
    <div id="dateRangeModal" class="date-range-modal">
        <div class="date-range-container">
            <div class="date-range-header">
                <h3>Select Custom Date Range</h3>
                <button id="closeModal" class="date-range-close"><i class="fas fa-times"></i></button>
            </div>
            <form id="dateRangeForm">
                <div class="date-range-inputs">
                    <div class="date-input-group">
                        <label for="startDate">Start Date</label>
                        <input type="date" id="startDate" name="startDate" required>
                    </div>
                    <div class="date-input-group">
                        <label for="endDate">End Date</label>
                        <input type="date" id="endDate" name="endDate" required>
                    </div>
                </div>
                <div class="date-range-actions">
                    <button type="button" id="cancelRange" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary">Apply</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Chart.js Configuration
        Chart.defaults.font.family = "'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif";
        Chart.defaults.font.size = 12;
        Chart.defaults.color = '#78909C';

        // Temperature Chart
        <?php if (!empty($temperatureValues)): ?>
            const tempCtx = document.getElementById('temperatureChart').getContext('2d');
            const tempGradient = tempCtx.createLinearGradient(0, 0, 0, 400);
            tempGradient.addColorStop(0, 'rgba(255, 99, 132, 0.6)');
            tempGradient.addColorStop(1, 'rgba(255, 99, 132, 0)');

            const tempChart = new Chart(tempCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($temperatureLabels); ?>,
                    datasets: [{
                        label: 'Temperature (°C)',
                        data: <?php echo json_encode($temperatureValues); ?>,
                        fill: {
                            target: 'origin',
                            above: tempGradient
                        },
                        borderColor: 'rgb(255, 99, 132)',
                        tension: 0.4,
                        borderWidth: 2,
                        pointRadius: 3,
                        pointBackgroundColor: 'rgb(255, 99, 132)',
                        pointHoverRadius: 5,
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            align: 'end',
                            labels: {
                                boxWidth: 12,
                                usePointStyle: true,
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            titleFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            bodyFont: {
                                size: 13
                            },
                            padding: 10,
                            backgroundColor: 'rgba(255, 255, 255, 0.9)',
                            titleColor: '#333',
                            bodyColor: '#666',
                            borderColor: 'rgba(0, 0, 0, 0.1)',
                            borderWidth: 1,
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.parsed.y + ' °C';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            suggestedMin: Math.min(...<?php echo json_encode($temperatureValues); ?>) - 2,
                            suggestedMax: Math.max(...<?php echo json_encode($temperatureValues); ?>) + 2,
                            ticks: {
                                padding: 10
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.04)',
                                drawBorder: false
                            }
                        },
                        x: {
                            ticks: {
                                padding: 10
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.04)',
                                drawBorder: false
                            }
                        }
                    },
                    interaction: {
                        mode: 'nearest',
                        axis: 'x',
                        intersect: false
                    },
                    elements: {
                        line: {
                            tension: 0.4
                        }
                    }
                }
            });
        <?php endif; ?>

        // Humidity Chart
        <?php if (!empty($humidityValues)): ?>
            const humCtx = document.getElementById('humidityChart').getContext('2d');
            const humGradient = humCtx.createLinearGradient(0, 0, 0, 400);
            humGradient.addColorStop(0, 'rgba(54, 162, 235, 0.6)');
            humGradient.addColorStop(1, 'rgba(54, 162, 235, 0)');

            const humChart = new Chart(humCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($humidityLabels); ?>,
                    datasets: [{
                        label: 'Humidity (%)',
                        data: <?php echo json_encode($humidityValues); ?>,
                        fill: {
                            target: 'origin',
                            above: humGradient
                        },
                        borderColor: 'rgb(54, 162, 235)',
                        tension: 0.4,
                        borderWidth: 2,
                        pointRadius: 3,
                        pointBackgroundColor: 'rgb(54, 162, 235)',
                        pointHoverRadius: 5,
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            align: 'end',
                            labels: {
                                boxWidth: 12,
                                usePointStyle: true
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            titleFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            bodyFont: {
                                size: 13
                            },
                            padding: 10,
                            backgroundColor: 'rgba(255, 255, 255, 0.9)',
                            titleColor: '#333',
                            bodyColor: '#666',
                            borderColor: 'rgba(0, 0, 0, 0.1)',
                            borderWidth: 1,
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.parsed.y + '%';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            suggestedMin: Math.min(...<?php echo json_encode($humidityValues); ?>) - 5,
                            suggestedMax: Math.max(...<?php echo json_encode($humidityValues); ?>) + 5,
                            ticks: {
                                padding: 10
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.04)',
                                drawBorder: false
                            }
                        },
                        x: {
                            ticks: {
                                padding: 10
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.04)',
                                drawBorder: false
                            }
                        }
                    },
                    interaction: {
                        mode: 'nearest',
                        axis: 'x',
                        intersect: false
                    },
                    elements: {
                        line: {
                            tension: 0.4
                        }
                    }
                }
            });
        <?php endif; ?>

        // Device Usage Chart
        const deviceUsageCtx = document.getElementById('deviceUsageChart').getContext('2d');

        const deviceUsageChart = new Chart(deviceUsageCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($deviceUsageData, 'name')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($deviceUsageData, 'hours')); ?>,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(255, 206, 86, 0.7)',
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(153, 102, 255, 0.7)'
                    ],
                    borderColor: 'white',
                    borderWidth: 2,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 10,
                            padding: 15,
                            font: {
                                size: 11
                            }
                        }
                    },
                    tooltip: {
                        titleFont: {
                            size: 13
                        },
                        bodyFont: {
                            size: 12
                        },
                        padding: 10,
                        backgroundColor: 'rgba(255, 255, 255, 0.9)',
                        titleColor: '#333',
                        bodyColor: '#666',
                        borderColor: 'rgba(0, 0, 0, 0.1)',
                        borderWidth: 1,
                        callbacks: {
                            label: function(context) {
                                const device = <?php echo json_encode($deviceUsageData); ?>[context.dataIndex];
                                const minutes = Math.round(context.raw * 60);
                                const hours = context.raw;
                                return context.label + ': ' + hours + 'h (' + minutes + ' min)';
                            }
                        }
                    }
                }
            }
        });

        // Custom Date Range Modal
        const customRangeBtn = document.getElementById('custom-range-btn');
        const dateRangeModal = document.getElementById('dateRangeModal');
        const closeModalBtn = document.getElementById('closeModal');
        const cancelRangeBtn = document.getElementById('cancelRange');
        const dateRangeForm = document.getElementById('dateRangeForm');

        // Set default dates (last 7 days)
        const today = new Date();
        const sevenDaysAgo = new Date(today);
        sevenDaysAgo.setDate(today.getDate() - 7);

        document.getElementById('startDate').value = sevenDaysAgo.toISOString().split('T')[0];
        document.getElementById('endDate').value = today.toISOString().split('T')[0];

        // Show modal
        customRangeBtn.addEventListener('click', function() {
            dateRangeModal.classList.add('active');
        });

        // Hide modal
        function hideModal() {
            dateRangeModal.classList.remove('active');
        }

        closeModalBtn.addEventListener('click', hideModal);
        cancelRangeBtn.addEventListener('click', hideModal);

        // Handle clicks outside modal to close it
        dateRangeModal.addEventListener('click', function(event) {
            if (event.target === dateRangeModal) {
                hideModal();
            }
        });

        // Handle form submission
        dateRangeForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;

            if (startDate && endDate) {
                // Redirect with custom date parameters
                window.location.href = `?action=analytics&startDate=${startDate}&endDate=${endDate}`;
            }
        });

        // Chart download functionality
        document.querySelectorAll('.btn-card-action[data-action="download"]').forEach(btn => {
            btn.addEventListener('click', function() {
                const chartType = this.dataset.chart;
                let canvas;

                switch (chartType) {
                    case 'temperature':
                        canvas = document.getElementById('temperatureChart');
                        break;
                    case 'humidity':
                        canvas = document.getElementById('humidityChart');
                        break;
                    case 'device':
                        canvas = document.getElementById('deviceUsageChart');
                        break;
                }

                if (canvas) {
                    // Create download link
                    const link = document.createElement('a');
                    link.download = `smarthome-${chartType}-analysis.png`;
                    link.href = canvas.toDataURL('image/png');
                    link.click();
                }
            });
        });

        // Chart expand functionality
        document.querySelectorAll('.btn-card-action[data-action="expand"]').forEach(btn => {
            btn.addEventListener('click', function() {
                const chartType = this.dataset.chart;
                const card = this.closest('.analytics-card');

                if (card) {
                    if (card.classList.contains('expanded')) {
                        card.classList.remove('expanded');
                        this.innerHTML = '<i class="fas fa-expand-alt"></i>';
                    } else {
                        // First, collapse any expanded cards
                        document.querySelectorAll('.analytics-card.expanded').forEach(expandedCard => {
                            expandedCard.classList.remove('expanded');
                            expandedCard.querySelector('.btn-card-action[data-action="expand"]').innerHTML =
                                '<i class="fas fa-expand-alt"></i>';
                        });

                        // Then expand this card
                        card.classList.add('expanded');
                        this.innerHTML = '<i class="fas fa-compress-alt"></i>';
                    }
                }
            });
        });
    });
</script>