<?php
include 'db_connect.php';

$program = $_GET['program'];
$sql = "SELECT g.Genre, AVG(a.TVR) as avg_tvr 
        FROM audiences a 
        JOIN broadcast_times bt ON a.Broadcast_Time_ID = bt.Broadcast_Time_ID 
        JOIN genre g ON bt.Genre_ID = g.Genre_ID 
        JOIN program p ON a.id_program = p.id_program 
        WHERE p.nama_program = '$program'
        GROUP BY g.Genre";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "<tr data-genre='{$row['Genre']}' class='genre-row'>
                <td>{$row['Genre']}</td>
                <td>{$row['avg_tvr']}</td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='2'>No data available</td></tr>";
}
?>
