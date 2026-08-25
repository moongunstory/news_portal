<?php
// CAPTCHA 이미지 생성 스크립트
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 4자리 난수 코드 생성 (알기 쉬운 대문자 및 숫자)
$chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
$code = '';
$len = strlen($chars);
for ($i = 0; $i < 4; $i++) {
    $code .= $chars[random_int(0, $len - 1)];
}

// 세션에 캡차 코드 저장 (대문자로 비교)
$_SESSION['captcha_code'] = $code;

// 이미지 생성 (가로 120, 세로 38)
$width = 120;
$height = 38;
$image = @imagecreatetruecolor($width, $height);

if (!$image) {
    // GD가 없을 경우 대체 텍스트 출력
    header('Content-Type: text/plain');
    echo $code;
    exit;
}

// 색상 지정
$bg_color = imagecolorallocate($image, 245, 247, 250);
$border_color = imagecolorallocate($image, 206, 212, 218);
$text_color = imagecolorallocate($image, 33, 37, 41);
$line_color1 = imagecolorallocate($image, 173, 181, 189);
$line_color2 = imagecolorallocate($image, 3, 199, 90);

// 배경 채우기 및 테두리
imagefilledrectangle($image, 0, 0, $width, $height, $bg_color);
imagerectangle($image, 0, 0, $width - 1, $height - 1, $border_color);

// 노이즈 선 추가
imageline($image, 0, random_int(5, 30), $width, random_int(5, 30), $line_color1);
imageline($image, 0, random_int(5, 30), $width, random_int(5, 30), $line_color2);

// 노이즈 점 추가
for ($i = 0; $i < 40; $i++) {
    $dot_color = imagecolorallocate($image, random_int(150, 220), random_int(150, 220), random_int(150, 220));
    imagesetpixel($image, random_int(0, $width), random_int(0, $height), $dot_color);
}

// 글자 그리기 (내장 폰트 5 사용)
$font = 5;
$char_width = imagefontwidth($font);
$char_height = imagefontheight($font);
$total_width = strlen($code) * ($char_width + 12);
$start_x = ($width - $total_width) / 2;

for ($i = 0; $i < strlen($code); $i++) {
    $char = $code[$i];
    $x = $start_x + ($i * ($char_width + 12)) + random_int(-2, 2);
    $y = ($height - $char_height) / 2 + random_int(-3, 3);
    $c_color = imagecolorallocate($image, random_int(10, 80), random_int(10, 80), random_int(10, 80));
    imagechar($image, $font, (int)$x, (int)$y, $char, $c_color);
}

// 이미지 출력
header('Content-Type: image/png');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

imagepng($image);
imagedestroy($image);
