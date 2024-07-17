<?php
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sub_genre_id'])) {
    $subGenreID = $_POST['sub_genre_id'];

    $sql = "SELECT p.nama_program, AVG(a.TVR) as avg_tvr
            FROM audiences a
            JOIN broadcast_times bt ON a.id_program = bt.id_program
            JOIN programs p ON bt.id_program = p.id_program
            JOIN sub_genres sg ON p.id_program = sg.Sub_Genre_ID
            WHERE sg.Sub_Genre_ID = $subGenreID
            GROUP BY p.nama_program";

    $result = $conn->query($sql);

    $programs = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $programs[] = [
                'program' => $row['nama_program'],
                'tvr' => $row['avg_tvr']
            ];
        }
    }

    echo json_encode($programs);
} else {
    echo json_encode([]);
}
?>
