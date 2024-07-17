<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Drill Down Rating TV</title>
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
        <div id="genre-chart">
            <h2>Rating Program JTV Berdasarkan Genre</h2>
            <canvas id="genreChart" width="400" height="200"></canvas>
            <button id="backButton" style="display: none;">Back</button>
        </div>
    </div>

    <?php
    include 'db_connect.php';

    // Query to fetch genre and TVR data
    $sql = "SELECT g.Genre, AVG(a.TVR) as avg_tvr 
            FROM audiences a 
            JOIN broadcast_times bt ON a.id_program = bt.id_program 
            JOIN programs p ON bt.id_program = p.id_program
            JOIN sub_genres sg ON p.id_program = sg.Sub_Genre_ID
            JOIN genres g ON sg.Genre_ID = g.Genre_ID 
            GROUP BY g.Genre";
    $result = $conn->query($sql);

    $genreData = [];
    $genreTvrData = [];
    $genreSubGenres = [];

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $genreData[] = $row['Genre'];
            $genreTvrData[] = $row['avg_tvr'];

            $genre = $row['Genre'];
            $sql_sub = "SELECT sg.Sub_Genre_ID, sg.Sub_Genre, AVG(a.TVR) as avg_tvr 
                        FROM audiences a 
                        JOIN broadcast_times bt ON a.id_program = bt.id_program 
                        JOIN programs p ON bt.id_program = p.id_program
                        JOIN sub_genres sg ON p.id_program = sg.Sub_Genre_ID
                        JOIN genres g ON sg.Genre_ID = g.Genre_ID 
                        WHERE g.Genre = '$genre'
                        GROUP BY sg.Sub_Genre";
            $result_sub = $conn->query($sql_sub);

            $genreSubGenres[$genre] = [];
            if ($result_sub->num_rows > 0) {
                while ($sub_row = $result_sub->fetch_assoc()) {
                    $subGenreID = $sub_row['Sub_Genre_ID'];
                    $subGenre = $sub_row['Sub_Genre'];
                    $avg_tvr = $sub_row['avg_tvr'];

                    // Fetch programs for each sub-genre
                    $sql_prog = "SELECT p.nama_program, AVG(a.TVR) as avg_tvr
                                 FROM audiences a
                                 JOIN broadcast_times bt ON a.id_program = bt.id_program
                                 JOIN programs p ON bt.id_program = p.id_program
                                 JOIN sub_genres sg ON p.id_program = sg.Sub_Genre_ID
                                 WHERE sg.Sub_Genre_ID = '$subGenreID'
                                 GROUP BY p.nama_program";
                    $result_prog = $conn->query($sql_prog);

                    $subGenrePrograms = [];
                    if ($result_prog->num_rows > 0) {
                        while ($prog_row = $result_prog->fetch_assoc()) {
                            $subGenrePrograms[] = [
                                'program' => $prog_row['nama_program'],
                                'tvr' => $prog_row['avg_tvr']
                            ];
                        }
                    }

                    $genreSubGenres[$genre][] = [
                        'subGenreID' => $subGenreID,
                        'subGenre' => $subGenre,
                        'tvr' => $avg_tvr,
                        'programs' => $subGenrePrograms
                    ];
                }
            }
        }
    }
    ?>

    <script>
        $(document).ready(function() {
            var genreData = <?php echo json_encode($genreData); ?>;
            var genreTvrData = <?php echo json_encode($genreTvrData); ?>;
            var genreSubGenres = <?php echo json_encode($genreSubGenres); ?>;
            var currentLevel = 'genre';
            var currentGenre = null;
            var currentSubGenre = null;

            var ctx = document.getElementById('genreChart').getContext('2d');
            var genreChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: genreData,
                    datasets: [{
                        label: 'Average TVR',
                        data: genreTvrData,
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
                    onClick: function(event) {
                        var activePoints = genreChart.getElementsAtEventForMode(event, 'nearest', {
                            intersect: true
                        }, true);
                        if (activePoints.length > 0) {
                            var clickedElementIndex = activePoints[0].index;
                            if (currentLevel === 'genre') {
                                currentGenre = genreChart.data.labels[clickedElementIndex];
                                currentLevel = 'subGenre';
                                genreChart.data.labels = genreSubGenres[currentGenre].map(sub => sub.subGenre);
                                genreChart.data.datasets[0].data = genreSubGenres[currentGenre].map(sub => sub.tvr);
                                genreChart.data.datasets[0].label = 'Average TVR per Sub-Genre';
                                genreChart.update();
                                $('#backButton').show();
                            } else if (currentLevel === 'subGenre') {
                                currentSubGenre = genreChart.data.labels[clickedElementIndex];
                                var subGenreID = genreSubGenres[currentGenre][clickedElementIndex].subGenreID;
                                fetchProgramsBySubGenre(subGenreID);
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            $('#backButton').click(function() {
                if (currentLevel === 'subGenre') {
                    currentLevel = 'genre';
                    $('#backButton').hide();
                    genreChart.data.labels = genreData;
                    genreChart.data.datasets[0].data = genreTvrData;
                    genreChart.data.datasets[0].label = 'Average TVR';
                    genreChart.update();
                } else if (currentLevel === 'program') {
                    currentLevel = 'subGenre';
                    fetchSubGenresByGenre(currentGenre);
                }
            });

            function fetchProgramsBySubGenre(subGenreID) {
                // Fetch programs data for the selected sub-genre
                $.ajax({
                    url: 'load_programs.php',
                    type: 'POST',
                    data: {
                        subGenreID: subGenreID
                    },
                    success: function(response) {
                        var programs = JSON.parse(response);
                        var programLabels = programs.map(prog => prog.program);
                        var programTvrData = programs.map(prog => prog.tvr);

                        genreChart.data.labels = programLabels;
                        genreChart.data.datasets[0].data = programTvrData;
                        genreChart.data.datasets[0].label = 'Average TVR per Program';
                        genreChart.update();

                        // Set current level to program
                        currentLevel = 'program';
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching programs:', error);
                    }
                });
            }

            $('#dropdown').change(function() {
                var selectedValue = $(this).val();
                if (selectedValue === 'genre') {
                    // Handle genre selection
                    currentLevel = 'genre';
                    genreChart.data.labels = genreData;
                    genreChart.data.datasets[0].data = genreTvrData;
                    genreChart.data.datasets[0].label = 'Average TVR';
                    genreChart.update();
                } else if (selectedValue === 'hari') {
                    // Handle land area selection
                    // Fetch and display data related to land area
                } 
            });
        });
    </script>
</body>

</html>
