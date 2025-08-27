<?php

require '../vendor/autoload.php';

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Interfaces\ImageInterface;

const START_MESSAGE_MARKER = '<t>';
const END_MESSAGE_MARKER = '</t>';


function encodeText(string $text): string // add caesar encoding
{
    return START_MESSAGE_MARKER . $text . END_MESSAGE_MARKER;
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


function readFromImage(ImageInterface &$img): string | null
{
    $success = false;
    $width = $img->width();
    $height = $img->height();
    $row = 0;
    $col = 0;
    $messageChars = [];
    $tempBits = [];
    while (true) {
        if (count($tempBits) == 8) {
            array_push($messageChars, chr(bindec(implode("", $tempBits))));
            $tempBits = [];
        }
        $messageLength = count($messageChars);
        if ($messageLength > 4) {
            $lastChars = implode(
                "",
                [
                    $messageChars[$messageLength - 4],
                    $messageChars[$messageLength - 3],
                    $messageChars[$messageLength - 2],
                    $messageChars[$messageLength - 1]
                ]
            );
            if ($lastChars === END_MESSAGE_MARKER) {
                $success = true;
                break;
            }
        }

        if ($col > 0 && $col < $width) {
            $col++;
        } else {
            $col = 0;
            $row++;
        }
        if ($row > $height) {
            break;
        }
        $rgb = $img->pickColor($col, $row);
        // phpcs:ignore
        /** @disregard P1013 */
        $redBinVal = decbin($rgb->red()->toInt());
        array_push($tempBits, $redBinVal[strlen($redBinVal) - 1]);
    }
    $message = $success ? implode("", $messageChars) : null;
    return $message;
}


function main(): void
{
    $imgPath = 'cola.png';
    $outPath = 'cola2.png';
    $text = 'i like watches';
    $encodedText = encodeText($text);
    $image = loadImage($imgPath);
    writeToImage($image, $encodedText);
    $image->save($outPath);
    $image2 = loadImage($outPath);
    echo readFromImage($image2) . "\r\n";
}


main();
