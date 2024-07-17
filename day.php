<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Drill Down Rating TV - Day Chart</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>
    <h1>Drill Down Rating Televisi JTV</h1>
    <div id="drilldown-container">
        <div>
            <label for="dropdown">Pilih Kategori:</label>
            <select id="dropdown" onchange="location = this.value;">
                <option value="index.php">Genre</option>
                <option value="day.php">Hari</option>
            </select>
        </div>
        <div id="day-chart">
            <h2>Rating Program JTV Berdasarkan Hari</h2>
            <canvas id="dayChart" width="400" height="200"></canvas>
            <button id="backButtonDay" style="display: none;">Back</button>
        </div>
    </div>

    <?php
    include 'db_connect.php';

    // Query to fetch day and TVR data
    $sql_day = "SELECT d.day_name, AVG(a.TVR) as avg_tvr
    FROM audiences a
    JOIN broadcast_times bt ON a.id_program = bt.id_program
    JOIN programs p ON bt.id_program = p.id_program
    JOIN days d ON bt.id_day = d.id_day
    GROUP BY d.day_name
    ORDER BY d.day_name ASC"; // Urutkan berdasarkan nama hari
    $result_day = $conn->query($sql_day);

    $dayData = [];
    $dayTvrData = [];
    $dayPrograms = [];

    if ($result_day->num_rows > 0) {
        while ($row = $result_day->fetch_assoc()) {
            $dayData[] = $row['day_name'];
            $dayTvrData[] = $row['avg_tvr'];

            $day = $row['day_name'];

            // Fetch programs for each day
            $sql_prog = "SELECT p.nama_program, AVG(a.TVR) as avg_tvr
                        FROM audiences a
                        JOIN broadcast_times bt ON a.id_program = bt.id_program
                        JOIN programs p ON bt.id_program = p.id_program
                        JOIN days d ON bt.id_day = d.id_day
                        WHERE d.day_name = '$day'
                        GROUP BY p.nama_program";
            $result_prog = $conn->query($sql_prog);

            $dayPrograms[$day] = [];
            if ($result_prog->num_rows > 0) {
                while ($prog_row = $result_prog->fetch_assoc()) {
                    $dayPrograms[$day][] = [
                        'program' => $prog_row['nama_program'],
                        'tvr' => $prog_row['avg_tvr']
                    ];
                }
            }
        }
    }

    // Urutkan dayData berdasarkan nama hari
    sort($dayData);
    ?>

    <script>
        $(document).ready(function () {
            var ctxDay = document.getElementById('dayChart').getContext('2d');
            var dayChart = new Chart(ctxDay, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($dayData); ?>,
                    datasets: [{
                        label: 'Average TVR',
                        data: <?php echo json_encode($dayTvrData); ?>,
                        backgroundColor: [
                            '#FF5733',
                            '#33FF57',
                            '#5733FF',
                            '#33A7FF',
                            '#FF33A7',
                            '#A7FF33',
                            '#33FFA7',
                            '#FFA733',
                            '#A733FF',
                            '#FF33A7'
                        ],
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    onClick: function (event) {
                        var activePoints = dayChart.getElementsAtEventForMode(event, 'nearest', {
                            intersect: true
                        }, true);
                        if (activePoints.length > 0) {
                            var clickedElementIndex = activePoints[0].index;
                            var selectedDay = dayChart.data.labels[clickedElementIndex];
                            fetchProgramsByDay(selectedDay);
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            $('#backButtonDay').click(function () {
                dayChart.data.labels = <?php echo json_encode($dayData); ?>;
                dayChart.data.datasets[0].data = <?php echo json_encode($dayTvrData); ?>;
                dayChart.update();
                $('#backButtonDay').hide();
            });

            function fetchProgramsByDay(selectedDay) {
                var dayPrograms = <?php echo json_encode($dayPrograms); ?>;
                if (dayPrograms[selectedDay]) {
                    dayChart.data.labels = dayPrograms[selectedDay].map(prog => prog.program);
                    dayChart.data.datasets[0].data = dayPrograms[selectedDay].map(prog => prog.tvr);
                    dayChart.update();
                    $('#backButtonDay').show();
                }
            }
        });
    </script>
</body>

</html>
