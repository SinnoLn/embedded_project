<?php
// 가상의 센서 데이터 (상수)
$humidity = 85.0;
$temperature = 22.5;
$tilt_angle = 35.0;

// 가상의 공공데이터 (상수)
$weather_description = "Clear sky";
$wind_speed = 5.0;
$rainfall = 0.0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            width: 80%;
            margin: 0 auto;
            overflow: hidden;
        }
        header {
            background: #333;
            color: #fff;
            padding-top: 30px;
            min-height: 70px;
            border-bottom: #77aaff 3px solid;
        }
        header h1 {
            text-align: center;
            text-transform: uppercase;
            margin: 0;
            font-size: 24px;
        }
        .content {
            background: #fff;
            padding: 20px;
            margin-top: 20px;
        }
        .card {
            background: #fff;
            padding: 20px;
            margin: 10px 0;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .card h2 {
            margin: 0 0 10px;
        }
        .card p {
            margin: 0;
        }
        footer {
            background: #333;
            color: #fff;
            text-align: center;
            padding: 10px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <header>
        <h1>Project Dashboard</h1>
    </header>
    <div class="container">
        <div class="content">
            <div class="card">
                <h2>Sensor Data</h2>
                <p><strong>Humidity:</strong> <?php echo $humidity; ?>%</p>
                <p><strong>Temperature:</strong> <?php echo $temperature; ?>°C</p>
                <p><strong>Tilt Angle:</strong> <?php echo $tilt_angle; ?>°</p>
            </div>
            <div class="card">
                <h2>Public Weather Data</h2>
                <p><strong>Weather Description:</strong> <?php echo $weather_description; ?></p>
                <p><strong>Wind Speed:</strong> <?php echo $wind_speed; ?> m/s</p>
                <p><strong>Rainfall:</strong> <?php echo $rainfall; ?> mm</p>
            </div>
        </div>
    </div>
    <footer>
        <p>&copy; 2024 Project Dashboard</p>
    </footer>
</body>
</html>

