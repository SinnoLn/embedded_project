<?php
$servername = "localhost";
$username = "root";
$password = "12345678";
$dbname = "iot_project_db";

// 데이터베이스 연결 생성
$conn = new mysqli($servername, $username, $password, $dbname);

// 연결 확인
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $value = intval($_POST['value']);
    $sensor_type = $_POST['sensor_type'];
    if($value >= 10){  
    $sql = "INSERT INTO soil_data (soilHumid,sensor_type) VALUES ('$value','$sensor_type')";
    }
}
    if ($conn->query($sql) === TRUE) {
        echo "New record created successfully";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }

$conn->close();
?>

