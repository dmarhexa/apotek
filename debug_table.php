<?php
require_once 'config.php';

$table = 'pengingat_obat';
$query = "DESCRIBE $table";
$result = mysqli_query($conn, $query);

if ($result) {
    echo "Columns in table '$table':\n";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} else {
    echo "Error describing table: " . mysqli_error($conn);
}
?>
