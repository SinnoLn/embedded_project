<?php
// MySQL 연결 설정
$mysqli = new mysqli("localhost", "root", "12345678", "iot_project_db");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// API 호출 설정
$service_key = "5wl5BtMRM8BADvFJqBXLF+R21RhkpL9jIoSQLyOQHc95SQi5Nab5JTQHrbMQCgkRF3wH0iWBeZWNBKwbseyj+A==";  // 디코딩된 서비스 키
$page_no = 1;
$num_of_rows = 1000;
$data_type = "JSON";
$base_date = date("Ymd");  // 현재 날짜
$base_time = date("Hi", strtotime("-1 hour"));  // 1시간 전 시간
$nx = 68;  // X 좌표
$ny = 101;  // Y 좌표

$api_url = "http://apis.data.go.kr/1360000/VilageFcstInfoService_2.0/getUltraSrtNcst";
$query_params = '?' . http_build_query([
    'serviceKey' => $service_key,
    'pageNo' => $page_no,
    'numOfRows' => $num_of_rows,
    'dataType' => $data_type,
    'base_date' => $base_date,
    'base_time' => $base_time,
    'nx' => $nx,
    'ny' => $ny
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
    die("Error occurred while fetching data.");
}

echo "HTTP Code: $httpcode\n";  // HTTP 응답 코드를 출력
echo "Response: $response\n";  // 전체 응답을 출력

$data = json_decode($response, true);
if ($data === NULL) {
    die("Error occurred while parsing data. Response: " . $response);
}

if ($data['response']['header']['resultCode'] !== "00") {
    die("API Error: " . $data['response']['header']['resultMsg']);
}
?>

