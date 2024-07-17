<?php
include 'db_connect.php';

$genre = $_GET['genre'];
$sql = "SELECT kw.Kategori_Waktu, AVG(a.TVR) as avg_tvr 
        FROM audiences a 
        JOIN broadcast_times bt ON a.Broadcast_Time_ID = bt.Broadcast_Time_ID 
        JOIN kategori_waktu kw ON bt.Kategori_Waktu_ID = kw.Kategori_Waktu_ID 
        JOIN genre g ON bt.Genre_ID = g.Genre_ID 
        WHERE g.Genre = '$genre'
        GROUP BY kw.Kategori_Waktu";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "<tr data-time-category='{$row['Kategori_Waktu']}' class='time-category-row'>
                <td>{$row['Kategori_Waktu']}</td>
                <td>{$row['avg_tvr']}</td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='2'>No data available</td></tr>";
}
?>
