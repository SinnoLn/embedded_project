<?php
// MySQL 연결 설정
$mysqli = new mysqli("localhost", "root", "12345678", "iot_project_db");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// API 호출 설정
$service_key = "6E3FF3C8-EF42-3E03-982D-555B07AB33F5";
$api_url = "https://api.vworld.kr/req/data";
$query_params = '?' . http_build_query([
    'service' => 'data',
    'request' => 'GetFeature',
    'data' => 'LT_L_GIMSDIREC',
    'key' => $service_key,
    'format' => 'json',
    'geomFilter' => 'BOX(127.433, 36.562, 127.543, 36.722)',  // 청주 좌표 기준 예시 (경도, 위도)
    'domain' => '223.130.162.254'  // 등록된 도메인 추가
]);

// cURL을 사용한 API 호출
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url . $query_params);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_HEADER, FALSE);
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);  // HTTP 응답 코드
curl_close($ch);

if ($response === FALSE) {
    die("Error occurred while fetching groundwater data.");
}

echo "HTTP Code: $httpcode\n";  // HTTP 응답 코드를 출력
echo "Response: $response\n";  // 전체 응답을 출력

$data = json_decode($response, true);
if ($data === NULL) {
    die("Error occurred while parsing groundwater data. Response: " . $response);
}

if ($data['response']['status'] !== "OK") {
    die("API Error: " . $data['response']['error']['text']);
}

?>

