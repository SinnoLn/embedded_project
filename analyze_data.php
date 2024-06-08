<?php
header('Content-Type: application/json; charset=utf-8');

function log_message($message) {
    echo "$message\n";
}

// 데이터베이스 연결 설정
$servername = "localhost";
$username = "root";
$password = "12345678";
$dbname = "iot_project_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    log_message("데이터베이스 연결 실패: " . $conn->connect_error);
    die(json_encode(['status' => 'error', 'message' => '데이터베이스 연결 실패']));
}

// 기상 데이터와 지하수 데이터 가져오기
$weather_data_url = 'http://223.130.162.254/collect_weather_data.php';
$groundwater_data_url = 'http://223.130.162.254/collect_groundwater_data.php';

$weather_data_response = file_get_contents($weather_data_url);
$groundwater_data_response = file_get_contents($groundwater_data_url);

if ($weather_data_response === FALSE || $groundwater_data_response === FALSE) {
    log_message("데이터 가져오기 실패");
    die(json_encode(['status' => 'error', 'message' => '데이터 가져오기 실패']));
}

function extract_json_body($response) {
    $start = strpos($response, '{');
    $end = strrpos($response, '}') + 1;
    if ($start !== false && $end !== false) {
        return substr($response, $start, $end - $start);
    }
    return $response;
}

$weather_data = json_decode(extract_json_body($weather_data_response), true);
$groundwater_data = json_decode(extract_json_body($groundwater_data_response), true);

if ($weather_data === NULL) {
    log_message("기상 데이터 파싱 실패: " . json_last_error_msg());
    die(json_encode(['status' => 'error', 'message' => '기상 데이터 파싱 실패: ' . json_last_error_msg()]));
}

if ($groundwater_data === NULL) {
    log_message("지하수 데이터 파싱 실패: " . json_last_error_msg());
    die(json_encode(['status' => 'error', 'message' => '지하수 데이터 파싱 실패: ' . json_last_error_msg()]));
}

// 임계값 설정
$threshold_rain = 50.0; // mm
$threshold_humidity = 90.0; // %
$threshold_tilt = 15.0; // degrees
$groundwater_risk = false; // 지하수 위험 여부 초기화

// 기상 데이터 분석
$rain_total = 0.0;
$high_humidity = false;

if (isset($weather_data['response']['body']['items']['item'])) {
    foreach ($weather_data['response']['body']['items']['item'] as $item) {
        if ($item['category'] == 'RN1') {
            $rain_total += $item['obsrValue'];
        }
        if ($item['category'] == 'REH' && $item['obsrValue'] > $threshold_humidity) {
            $high_humidity = true;
        }
    }
} else {
    log_message("기상 데이터 형식이 올바르지 않습니다.");
    die(json_encode(['status' => 'error', 'message' => '기상 데이터 형식이 올바르지 않습니다.']));
}

if (isset($groundwater_data['response']['result']['featureCollection']['features'])) {
    if ($groundwater_data['response']['status'] === "OK") {
        $groundwater_risk = false; // 임시로 지하수 위험을 없애기 위해 설정
    }
} else {
    log_message("지하수 데이터 형식이 올바르지 않습니다.");
    die(json_encode(['status' => 'error', 'message' => '지하수 데이터 형식이 올바르지 않습니다.']));
}

// 센서 데이터 가져오기 (fake_mpu_data)
$sensor_data_mpu6050 = [];
$sql_mpu = "SELECT * FROM fake_mpu_data ORDER BY timestamp DESC LIMIT 48";
$result_mpu = $conn->query($sql_mpu);

if ($result_mpu->num_rows > 0) {
    while ($row = $result_mpu->fetch_assoc()) {
        $sensor_data_mpu6050[] = [
            'id' => $row['id'],
            'sensor_type' => $row['sensor_type'],
            'ax' => $row['ax'],
            'ay' => $row['ay'],
            'az' => $row['az'],
            'timestamp' => $row['timestamp'],
            'incline' => $row['incline']
        ];
    }
} else {
    log_message("MPU 데이터가 없습니다.");
    die(json_encode(['status' => 'error', 'message' => 'MPU 데이터가 없습니다.']));
}

// 센서 데이터 가져오기 (fake_soil_data)
$sensor_data_ppa800 = [];
$sql_soil = "SELECT * FROM fake_soil_data ORDER BY timestamp DESC LIMIT 48";
$result_soil = $conn->query($sql_soil);

if ($result_soil->num_rows > 0) {
    while ($row = $result_soil->fetch_assoc()) {
        $sensor_data_ppa800[] = [
            'id' => $row['id'],
            'sensor_type' => $row['sensor_type'],
            'humidity' => $row['soilHumid'],
            'timestamp' => $row['timestamp']
        ];
    }
} else {
    log_message("토양 데이터가 없습니다.");
    die(json_encode(['status' => 'error', 'message' => '토양 데이터가 없습니다.']));
}

$conn->close();

// 일정 범위 내에 있는지 확인하는 함수
function is_within_range($point, $bbox, $range = 0.03) {
    return ($point[0] >= ($bbox[0] - $range) && $point[0] <= ($bbox[2] + $range)) &&
        ($point[1] >= ($bbox[1] - $range) && $point[1] <= ($bbox[3] + $range));}

// 기울기 계산 및 임계값 확인
$high_tilt = false;
foreach ($sensor_data_mpu6050 as $data) {
    if ($data['incline'] > $threshold_tilt) {
        $high_tilt = true;
        break;
    }
}

// 토양습도 임계값 확인
$high_soil_humidity = false;
foreach ($sensor_data_ppa800 as $data) {
    if ($data['humidity'] > $threshold_humidity) {
        $high_soil_humidity = true;
        break;
    }
}

// 지하수 데이터와 기상 데이터 비교하여 위험 여부 확인
$groundwater_risk = false;
foreach ($groundwater_data['response']['result']['featureCollection']['features'] as $feature) {
    $coordinates = $feature['geometry']['coordinates'][0];
    foreach ($coordinates as $coord) {
        foreach ($weather_data['response']['body']['items']['item'] as $weather_item) {
            $nx = $weather_item['nx'];
            $ny = $weather_item['ny'];
            $bbox = $groundwater_data['response']['result']['featureCollection']['bbox'];

            if (is_within_range([$nx, $ny], $bbox)) {
                $groundwater_risk = true;
                break 3;
            }
        }
    }
}

// 분석 결과 생성
$message = '';
$alert_status = 'safe';

if ($rain_total >= $threshold_rain && $high_humidity && $high_tilt) {
    $alert_status = 'danger';
    $message = "산사태 경고: 청주 지역에서 높은 위험이 감지되었습니다!\n";
    $message .= " - 강수량: $rain_total mm\n";
    $message .= " - 높은 습도: 네\n";
    $message .= " - 높은 기울기: 네\n";
    $message .= " - 지하수 위험: " . ($groundwater_risk ? "네" : "아니오") . "\n";
} else if ($rain_total >= $threshold_rain || $high_humidity || $high_tilt) {
    $alert_status = 'warning';
    $message = "산사태 주의: 청주 지역에서 잠재적 위험이 감지되었습니다.\n";
    $message .= " - 강수량: $rain_total mm\n";
    $message .= " - 높은 습도: " . ($high_humidity ? "네" : "아니오") . "\n";
    $message .= " - 높은 기울기: " . ($high_tilt ? "네" : "아니오") . "\n";
    $message .= " - 지하수 위험: " . ($groundwater_risk ? "네" : "아니오") . "\n";
} else {
    $message = "청주 지역에서 큰 위험이 감지되지 않았습니다.";
}

echo json_encode([
    'status' => 'success',
    'alert_status' => $alert_status,
    'message' => $message,
    'weather_data' => $weather_data['response']['body']['items']['item'],
    'groundwater_data' => $groundwater_data['response']['result']['featureCollection']['features'],
    'sensor_data' => ['mpu6050' => $sensor_data_mpu6050, 'ppa800' => $sensor_data_ppa800]
]);
?>

