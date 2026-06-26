<?php

require '../vendor/autoload.php';

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Interfaces\ImageInterface;

const START_MESSAGE_MARKER = '<t>';
const END_MESSAGE_MARKER = '</t>';
const CHARS_LOWER_BOUND = 97;
const CHARS_UPPER_BOUND = 122;
const ALPHABET_LENGHT = 26;
const DEFAULT_SHIFT = 3;

function encryptText(string $text, int $shift = DEFAULT_SHIFT): string
{
    $encryptedChars = [];
    for ($i = 0; $i < strlen($text); $i++) {
        if (!ctype_alpha($text[$i])) {
            array_push($encryptedChars, $text[$i]);
            continue;
        }
        $isUpperCase = ctype_upper($text[$i]);
        $numChar = ord(strtolower($text[$i]));
        $newNumChar = $numChar + ($shift % ALPHABET_LENGHT);
        $newNumChar = $newNumChar > CHARS_UPPER_BOUND ?
            $newNumChar - ALPHABET_LENGHT : $newNumChar;
        array_push($encryptedChars, $isUpperCase ? strtoupper(chr($newNumChar)) : chr($newNumChar));
    }
    return implode("", $encryptedChars);
}

function decryptText(string $text, int $shift = DEFAULT_SHIFT): string
{
    $decryptedChars = [];
    for ($i = 0; $i < strlen($text); $i++) {
        if (!ctype_alpha($text[$i])) {
            array_push($decryptedChars, $text[$i]);
            continue;
        }
        $isUpperCase = ctype_upper($text[$i]);
        $numChar = ord(strtolower($text[$i]));
        $newNumChar = $numChar - ($shift % ALPHABET_LENGHT);
        $newNumChar = $newNumChar < CHARS_LOWER_BOUND ?
            $newNumChar + ALPHABET_LENGHT : $newNumChar;
        array_push($decryptedChars, $isUpperCase ? strtoupper(chr($newNumChar)) : chr($newNumChar));
    }
    return implode("", $decryptedChars);
}


function encodeMessage(string $message): string
{
    return START_MESSAGE_MARKER . encryptText($message) . END_MESSAGE_MARKER;
}

function decodeMessage(string $message): string
{
    return decryptText(
        substr(
            $message,
            strlen(START_MESSAGE_MARKER),
            -strlen(END_MESSAGE_MARKER)
        )
    );
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

function writeToImage(ImageInterface &$img, string $text): void
{
    $message = encodeMessage($text);
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
        $channels = $rgb->channels();
        $orgRedBinVal = $channels[0]->value();
        $newRedVal = bindec(substr($orgRedBinVal, 0, -1) . $binText[$bitsEncoded]);
        $newRgb = "rgb($newRedVal, {$channels[1]}, {$channels[2]})";
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
        $channels = $rgb->channels();
        $redBinVal = decbin(intval($channels[0]->value()));
        array_push($tempBits, $redBinVal[strlen($redBinVal) - 1]);
    }
    $message = $success ? implode("", $messageChars) : null;
    return decodeMessage($message);
}


function main(): void
{
    $imgPath = 'cola.png';
    $outPath = 'cola2.png';
    $text = 'i like watches';
    $image = loadImage($imgPath);
    writeToImage($image, $text);
    $image->save($outPath);
    $image2 = loadImage($outPath);
    echo readFromImage($image2) . "\r\n";
}


main();
