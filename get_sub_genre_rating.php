<?php
include 'db_connect.php';

$timeCategory = $_GET['timeCategory'];
$sql = "SELECT sg.Sub_Genre, AVG(a.TVR) as avg_tvr 
        FROM audiences a 
        JOIN broadcast_times bt ON a.Broadcast_Time_ID = bt.Broadcast_Time_ID 
        JOIN sub_genres sg ON bt.Sub_Genre_ID = sg.Sub_Genre_ID 
        JOIN kategori_waktu kw ON bt.Kategori_Waktu_ID = kw.Kategori_Waktu_ID 
        WHERE kw.Kategori_Waktu = '$timeCategory'
        GROUP BY sg.Sub_Genre";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>{$row['Sub_Genre']}</td>
                <td>{$row['avg_tvr']}</td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='2'>No data available</td></tr>";
}
?>
