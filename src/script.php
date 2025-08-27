<?php

require '../vendor/autoload.php';

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Interfaces\ImageInterface;

function encodeText(string $text): string // add caesar encoding
{
    return "<t>$text<t>";
}


function textToBinary(string $text): string
{
    $binTextArr = [];
    for ($i = 0; $i < strlen($text); $i++) {
        $bin = decbin(ord($text[$i]));
        array_push($binTextArr, str_pad($bin, 8, 0, STR_PAD_LEFT));
    }
    return implode($binTextArr);
}

function loadImage(string $imgPath): ImageInterface
{
    $manager = new ImageManager(new Driver());
    return $manager->read($imgPath);
}


function writeToImage(ImageInterface &$img, string $message): void
{
    $binText = textToBinary($message);
    $bitsCount = strlen($binText);
    $width = $img->width();
    $height = $img->height();
    $bitsEncoded = 0;
    $row = 0;
    $col = 0;

    while ($bitsEncoded < $bitsCount && $row + 1 < $height) {
        if ($col > 0 && $col < $width) {
            $col += 1;
        } else {
            $col = 0;
            $row += 1;
        }
        $rgb = $img->pickColor($col, $row);
        // phpcs:ignore
        /** @disregard P1013 */
        $orgRedBinVal = decbin($rgb->red()->toInt());
        $newRedVal = bindec(substr($orgRedBinVal, 0, -1) . $binText[$bitsEncoded]);
        // phpcs:ignore
        /** @disregard P1013 */
        $newRgb = "rgb($newRedVal, {$rgb->green()->toInt()}, {$rgb->blue()->toInt()})";
        $img->drawPixel($col, $row, $newRgb);
        $bitsEncoded += 1;
    }
}


// function readFromImage(ImageInterface &$img): string
// {
//     $width = $img->width();
//     $height = $img->height();
//     $bitsEncoded = 0;
//     $row = 0;
//     $col = 0;
//     $messageBits = [];
//     while (true) {
//         if ($col > 0 && $col < $width) {
//             $col += 1;
//         } else {
//             $col = 0;
//             $row += 1;
//         }
//         $rgb = $img->pickColor($col, $row);
//         // phpcs:ignore
//         /** @disregard P1013 */
//         $orgRedBinVal = decbin($rgb->red()->toInt());
//         $newRedVal = bindec(substr($orgRedBinVal, 0, -1) . $binText[$bitsEncoded]);
//         // phpcs:ignore
//         /** @disregard P1013 */
//         $newRgb = "rgb($newRedVal, {$rgb->green()->toInt()}, {$rgb->blue()->toInt()})";
//         $img->drawPixel($col, $row, $newRgb);
//         $bitsEncoded += 1;
//     }

//     return "";
// }


function main(): void
{
    $imgPath = 'cola.png';
    $outPath = 'cola2.png';
    $text = 'i like watches';
    $encodedText = encodeText($text);
    $image = loadImage($imgPath);
    writeToImage($image, $encodedText);
    $image->save($outPath);
}


main();
