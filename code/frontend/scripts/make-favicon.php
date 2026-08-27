<?php

$logo = $argv[1] ?? null;
$outDir = $argv[2] ?? null;
if (! $logo || ! $outDir || ! is_file($logo)) {
    fwrite(STDERR, "Uso: php make-favicon.php <logo.jpg> <outDir>\n");
    exit(1);
}

$src = imagecreatefromjpeg($logo);
if (! $src) {
    fwrite(STDERR, "Falha ao ler JPEG\n");
    exit(1);
}

$w = imagesx($src);
$h = imagesy($src);
$size = 32;
$dst = imagecreatetruecolor($size, $size);
$white = imagecolorallocate($dst, 255, 255, 255);
imagefilledrectangle($dst, 0, 0, $size, $size, $white);

$scale = min($size / $w, $size / $h) * 0.92;
$nw = (int) ($w * $scale);
$nh = (int) ($h * $scale);
$x = (int) (($size - $nw) / 2);
$y = (int) (($size - $nh) / 2);
imagecopyresampled($dst, $src, $x, $y, 0, 0, $nw, $nh, $w, $h);

$pngPath = rtrim($outDir, '/\\').DIRECTORY_SEPARATOR.'favicon-32.png';
$icoPath = rtrim($outDir, '/\\').DIRECTORY_SEPARATOR.'favicon.ico';
$applePath = rtrim($outDir, '/\\').DIRECTORY_SEPARATOR.'apple-touch-icon.png';

imagepng($dst, $pngPath);

// PNG-in-ICO (Windows Vista+)
$png = file_get_contents($pngPath);
$ico = pack('vvv', 0, 1, 1);
$ico .= pack('CCCCvvVV', $size, $size, 0, 0, 1, 32, strlen($png), 22);
$ico .= $png;
file_put_contents($icoPath, $ico);
copy($pngPath, $applePath);

echo "favicon gerado: {$icoPath} (".filesize($icoPath)." bytes)\n";
