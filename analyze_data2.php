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
// nx, ny를 위도와 경도로 변환하는 함수
function convert_xy_to_latlon($x, $y) {
    $RE = 6371.00877;  // 지구 반경(km)
    $GRID = 5.0;  // 격자 간격(km)
    $SLAT1 = 30.0;  // 투영 기준 위도1(degree)
    $SLAT2 = 60.0;  // 투영 기준 위도2(degree)
    $OLON = 126.0;  // 기준점 경도(degree)
    $OLAT = 38.0;  // 기준점 위도(degree)
    $XO = 43;  // 기준점 X좌표(GRID)
    $YO = 136;  // 기준점 Y좌표(GRID)

    $DEGRAD = pi() / 180.0;
    $RADDEG = 180.0 / pi();

    $re = $RE / $GRID;
    $slat1 = $SLAT1 * $DEGRAD;
    $slat2 = $SLAT2 * $DEGRAD;
    $olon = $OLON * $DEGRAD;
    $olat = $OLAT * $DEGRAD;

    $sn = tan(pi() * 0.25 + $slat2 * 0.5) / tan(pi() * 0.25 + $slat1 * 0.5);
    $sn = log(cos($slat1) / cos($slat2)) / log($sn);
    $sf = tan(pi() * 0.25 + $slat1 * 0.5);
    $sf = pow($sf, $sn) * cos($slat1) / $sn;
    $ro = tan(pi() * 0.25 + $olat * 0.5);
    $ro = $re * $sf / pow($ro, $sn);

    $ra = sqrt(($x - $XO) * ($x - $XO) + ($ro - $y + $YO) * ($ro - $y + $YO));
    if ($sn < 0.0) {
        $ra = -$ra;
    }
    $alat = 2.0 * atan(pow($re * $sf / $ra, 1.0 / $sn)) - pi() * 0.5;

    if (abs($x - $XO) <= 0.0) {
        $theta = 0.0;
    } else {
        if (abs($y - $YO + $ro) <= 0.0) {
            $theta = pi() * 0.5;
            if ($x - $XO < 0.0) {
                $theta = -$theta;
            }
        } else {
            $theta = atan2(($x - $XO), ($ro - $y + $YO));
        }
    }
    $alon = $theta / $sn + $olon;

    return array('lat' => $alat * $RADDEG, 'lon' => $alon * $RADDEG);
}

// 일정 범위 내에 있는지 확인하는 함수
function is_within_range($point, $bbox, $range = 0.01) {
    return ($point[1] >= ($bbox[0] - $range) && $point[1] <= ($bbox[2] + $range)) &&
        ($point[0] >= ($bbox[1] - $range) && $point[0] <= ($bbox[3] + $range));
}

// 임계값 설정
$threshold_rain = 50;  // 강수량 임계값 
$threshold_humidity = 80;  // 습도 임계값 
$threshold_tilt = 20;  // 기울기 임계값

// 강수량 데이터 가져오기
$rain_total = 0;
$high_humidity = false;

foreach ($weather_data['response']['body']['items']['item'] as $item) {
    if ($item['category'] == 'RN1') {
        $rain_total += $item['obsrValue'];
    }
    if ($item['category'] == 'REH' && $item['obsrValue'] >= $threshold_humidity) {
        $high_humidity = true;
    }
}

// 데이터 분석 및 좌표 조정
$weather_nx = 60;  // 예시로 설정한 nx 값
$weather_ny = 127;  // 예시로 설정한 ny 값
$weather_latlon = convert_xy_to_latlon($weather_nx, $weather_ny);
$within_range = false;
$groundwater_risk = false;
$groundwater_risk_details = [];

if (isset($groundwater_data['response']['result']['featureCollection']['features'])) {
    foreach ($groundwater_data['response']['result']['featureCollection']['features'] as $feature) {
        $coordinates = $feature['geometry']['coordinates'];
        foreach ($coordinates as $line) {
            foreach ($line as $coord) {
                if (is_within_range([$weather_latlon['lat'], $weather_latlon['lon']], [$coord[1], $coord[0]])) {
                    $within_range = true;
                    $groundwater_risk = true;
                    $groundwater_risk_details[] = [$coord[1], $coord[0]];  // 위험 좌표를 배열에 추가
                    break 3;  // 위험 좌표를 찾으면 더 이상 반복하지 않음
                }
            }
        }
    }
}

if (!$within_range) {
    $safe_coordinates[] = [$weather_latlon['lat'], $weather_latlon['lon']];  // 안전 좌표를 배열에 추가
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

// 분석 결과 생성
$message = '';
$alert_status = 'safe';

if ($rain_total >= $threshold_rain && $high_humidity && $high_tilt) {
    $alert_status = 'danger';
    $message = "산사태 경고: 청주 지역에서 높은 위험이 감지되었습니다!\n";
    $message .= " - 강수량: $rain_total mm\n";
    $message .= " - 높은 습도: 네\n";
    $message .= " - 높은 기울기: 네\n";
    $message .= " - 지하수 위험: 네\n";
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
    'groundwater_risk_details' => $groundwater_risk_details,  // 위험 좌표 추가
    'safe_coordinates' => $safe_coordinates,  // 안전 좌표 추가
    'sensor_data' => ['mpu6050' => $sensor_data_mpu6050, 'ppa800' => $sensor_data_ppa800]
]);
?>

