<?php
// CAPTCHA mejorado con mayor seguridad y mejor diseño
ob_start(); // Iniciar búfer de salida para capturar cualquier salida inesperada

session_start();

// Configuración del CAPTCHA
$width = 160;
$height = 60;
$length = 5;

// Generar código aleatorio (números y letras mayúsculas sin confusión)
$caracteres = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ'; // Sin 0, O, 1, I para evitar confusión
$captcha_text = '';
for ($i = 0; $i < $length; $i++) {
    $captcha_text .= $caracteres[rand(0, strlen($caracteres) - 1)];
}

// Almacenar en sesión
$_SESSION['captcha_answer'] = $captcha_text;

// Crear imagen
$image = imagecreatetruecolor($width, $height);

// Colores
$bg_start = imagecolorallocate($image, 100, 130, 220);
$bg_end = imagecolorallocate($image, 140, 170, 240);
$text_color = imagecolorallocate($image, 255, 255, 255);
$line_color = imagecolorallocate($image, 80, 110, 200);
$dot_color = imagecolorallocate($image, 120, 150, 230);

// Crear gradiente de fondo
for ($y = 0; $y < $height; $y++) {
    $r = 100 + ($y * 40 / $height);
    $g = 130 + ($y * 40 / $height);
    $b = 220 + ($y * 20 / $height);
    $color = imagecolorallocate($image, $r, $g, $b);
    imageline($image, 0, $y, $width, $y, $color);
}

// Añadir líneas de ruido
for ($i = 0; $i < 3; $i++) {
    imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $line_color);
}

// Añadir puntos de ruido
for ($i = 0; $i < 150; $i++) {
    imagesetpixel($image, rand(0, $width), rand(0, $height), $dot_color);
}

// Escribir el texto del CAPTCHA con rotación y desplazamiento
$font_size = 20;
$x_start = 15;
$y_base = 40;

for ($i = 0; $i < $length; $i++) {
    $char = $captcha_text[$i];
    $angle = rand(-15, 15);
    $x = $x_start + ($i * 25);
    $y = $y_base + rand(-5, 5);
    
    // Sombra del texto
    $shadow_color = imagecolorallocate($image, 60, 90, 180);
    imagestring($image, 5, $x + 2, $y + 2, $char, $shadow_color);
    
    // Texto principal
    imagestring($image, 5, $x, $y, $char, $text_color);
}

// Añadir círculos decorativos
for ($i = 0; $i < 3; $i++) {
    $circle_color = imagecolorallocate($image, rand(120, 160), rand(150, 190), rand(230, 250));
    imageellipse($image, rand(0, $width), rand(0, $height), rand(10, 20), rand(10, 20), $circle_color);
}

// Headers para evitar caché
header('Content-Type: image/png');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

ob_end_clean(); // Limpiar el búfer y desactivarlo

// Salida de la imagen
imagepng($image);
imagedestroy($image);
?>
