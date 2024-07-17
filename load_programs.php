<?php
include 'db_connect.php';

$subGenreID = $_POST['subGenreID'];

$sql = "SELECT p.nama_program, AVG(a.TVR) as avg_tvr
        FROM audiences a
        JOIN broadcast_times bt ON a.id_program = bt.id_program
        JOIN programs p ON bt.id_program = p.id_program
        WHERE p.Sub_Genre_ID = '$subGenreID'
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

$conn->close();
?>
