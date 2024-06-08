<?php
// sensor_data.php
$mysqli = new mysqli("localhost", "root", "12345678", "iot_project_db");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$sensor_type = $_POST['sensor_type']; // 'moisture' or 'tilt'

$ax = $_POST['ax'];
$ay = $_POST['ay'];
$az = $_POST['az'];
$incli = $_POST['incli'];
$timestamp = date('Y-m-d H:i:s');

// 예외 처리 추가
try {
    $stmt = $mysqli->prepare("INSERT INTO mpu_data (sensor_type,ax,ay,az,incline, timestamp) VALUES (?,?, ?,?, ?,?)");
    if (!$stmt) {
        throw new Exception("Prepare statement failed: " . $mysqli->error);
    }

    $stmt->bind_param("sdddds", $sensor_type, $ax,$ay,$az,$incli, $timestamp);

    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    echo "Data received successfully.";

    $stmt->close();
    $mysqli->close();

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    $mysqli->close();
}
?>
