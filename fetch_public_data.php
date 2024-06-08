<?php
header('Content-Type: application/json; charset=UTF-8');

$service_key = "%2F4WDEt5xuBAPWVxNjw0DRCHhV%2FRYIpMSI8iqayDK1gj4xEyhV3bd7fLirKTdCDPg8cWYph74ye%2B13YGZWgKMkQ%3D%3D";
$page_no = 1;
$num_of_rows = 10;
$data_type = "JSON";
$base_date = "20240528";  // 요청 날짜를 설정합니다.
$base_time = "0600";      // 요청 시간을 설정합니다.
$nx = 55;                 // X 좌표를 설정합니다.
$ny = 127;                // Y 좌표를 설정합니다.

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

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url . $query_params);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_HEADER, FALSE);
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);  // HTTP 응답 코드를 가져옵니다.
curl_close($ch);

if ($response === FALSE) {
    die("Error occurred while fetching data.");
}

$data = json_decode($response, true);
if ($data === NULL) {
    die("Error occurred while parsing data. Response: " . $response);
}

if ($data['response']['header']['resultCode'] !== "00") {
    die("API Error: " . $data['response']['header']['resultMsg']);
}

echo json_encode($data, JSON_PRETTY_PRINT);
?>

