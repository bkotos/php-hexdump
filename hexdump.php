<?php

define('BINARY_FLAG', '-b');
define('OCTAL_FLAG', '-o');

/**
 * @param string[] $arguments
 */
function main(array &$arguments)
{
    $hasStdIn = ftell(STDIN) !== false;
    $isBin = argumentFlagExists(BINARY_FLAG, $arguments);
    $isOct = argumentFlagExists(OCTAL_FLAG, $arguments);

    if ($isBin) {
        // binary
        $bytesPerLine = 6;
        $colsPerByte = 9;
    } else if ($isOct) {
        // octal
        $bytesPerLine = 12;
        $colsPerByte = 4;
    } else {
        // hexadecimal
        $bytesPerLine = 16;
        $colsPerByte = 3;
    }

    list($command, $filename) = array_pad($arguments, 2, null);
    $file = getFile($hasStdIn, $filename);

    if (!$file) {
        exit(0);
    }

    $bytes = [];
    $chars = '';

    for($i = 0; !feof($file); $i++) {
        $isNewLine = ($i + 1) % $bytesPerLine === 0;
        $isFirstByteOfLine = $i % $bytesPerLine === 0;

        if ($isFirstByteOfLine) {
            $line = str_pad(dechex(ftell($file)), 8, '0', STR_PAD_LEFT);
        }

        $char = fread($file, 1);
        $ascii = ord($char);
        $chars .= getPrintableChar($char);
        if ($isBin) {
            $bytes[] = asciiToBin($ascii);
        } elseif ($isOct) {
            $bytes[] = asciiToOct($ascii);
        } else {
            $bytes[] = asciiToHex($ascii);
        }

        if ($isNewLine) {
            echo renderDump($bytesPerLine, $colsPerByte, $line, $bytes, $chars);
        }
    }

    if (count($bytes) > 0) {
        echo renderDump($bytesPerLine, $colsPerByte, $line, $bytes, $chars);
    }

    fclose($file);
}

/**
 * @param string $argumentFlag
 * @param string[] $arguments
 * @return bool
 */
function argumentFlagExists(string $argumentFlag, array &$arguments): bool
{
    $index = array_search($argumentFlag, $arguments);

    if ($index !== false) {
        array_splice($arguments, $index, 1);

        return true;
    }

    return false;
}

/**
 * @param bool $hasStdIn
 * @param string|null $filename
 * @return resource|bool|null
 */
function getFile(bool $hasStdIn, string $filename = null)
{
    if ($hasStdIn) {
        return STDIN;
    } elseif(file_exists($filename)) {
        return fopen($filename, 'r');
    }

    return null;
}

/**
 * @param int $bytesPerLine
 * @param int $colsPerByte
 * @param string $line
 * @param string[] $bytes
 * @param string $chars
 * @return string
 */
function renderDump(int $bytesPerLine, int $colsPerByte, string $line, array &$bytes, string &$chars): string
{
    $byteDump = str_pad(implode(' ', $bytes), $bytesPerLine * $colsPerByte);

    $dump = "$line: $byteDump $chars" . PHP_EOL;
    $bytes = [];
    $chars = '';

    return $dump;
}

/**
 * @param string $char
 * @return string
 */
function getPrintableChar(string $char): string
{
    $ascii = ord($char);
    $printableAscii = 46;

    if ($ascii >= 32 && $ascii <= 126) {
        $printableAscii = $ascii;
    }

    return chr($printableAscii);
}

/**
 * @param int $ascii
 * @return string
 */
function asciiToBin(int $ascii): string
{
    return str_pad(decbin($ascii), 8, '0', STR_PAD_LEFT);
}

/**
 * @param int $ascii
 * @return string
 */
function asciiToHex(int $ascii): string
{
    return str_pad(dechex($ascii), 2, '0', STR_PAD_LEFT);
}

/**
 * @param int $ascii
 * @return string
 */
function asciiToOct(int $ascii): string
{
    return str_pad(decoct($ascii), 3, '0', STR_PAD_LEFT);
}

main($argv);
