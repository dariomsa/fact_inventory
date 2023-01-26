<?php

///////////////////////////////
//file and directory operations
///////////////////////////////

function file_read($file_read) {
    $handle = 0;
    $content = "";
    if (filesize($file_read) == 0) {
        echo "The size of " . $file_read . " file is 0! ";
    }
    if (is_readable($file_read)) {
        if (!$handle = fopen($file_read, 'r')) {
            echo "Cannot open file " . $file_read . "! ";
            exit;
        }
        $content = fread($handle, filesize($file_read));
        fclose($handle);
    } else {
        echo "The file " . $file_read . "is not readable! ";
    }
    return $content;
}

function file_write($file_write, $content) {
    $handle = 0;
    if (!$handle = fopen($file_write, 'w+')) {
        echo "Cannot open file ($file_write)!";
        chmod($file_write, 666);
        exit;
    }
    fclose($handle);
    if (is_writable($file_write)) {
        if (!$handle = fopen($file_write, 'w+')) {
            echo "Cannot open file ($file_write)!";
            exit;
        }
        if (fwrite($handle, $content) === FALSE) {
            echo "Cannot write to file ($file_write)!";
            exit;
        }
        fclose($handle);
    } else {
        echo "The file $file_write is not writable!";
    }
    $content = 0;
}

function file_delete($file_delete) {
    $handle = 0;
    chmod($file_delete, 666);
    if (!$handle = unlink($file_delete)) {
        echo "Cannot delete file " . $file_delete . "! ";
        exit;
    }
}

function directory_read($path_directory) {
    $content = array();
    if (is_dir($path_directory)) {
        if ($directory_identifier = opendir($path_directory)) {
            while (false !== ($filename = readdir($directory_identifier))) {
                $content[] = $filename;
            }
            $content[] = sort($content);
            closedir($directory_identifier);
        }
    }
    return $content;
}

function log_write($file_write, $facility, $severity, $version, $timestamp, $appId, $msgId, $msgContent) {
    $handle = 0;
    $content = "\r\n" . "<" . (($facility * 8) + $severity) . ">" . $version . " " . $timestamp . " - " . $appId . " - " . $msgId . " - " . $msgContent;
    if (!$handle = fopen($file_write, 'a+')) {
        echo "Cannot open file ($file_write)!";
        chmod($file_write, 666);
        exit;
    }
    fclose($handle);
    if (is_writable($file_write)) {
        if (!$handle = fopen($file_write, 'a+')) {
            echo "Cannot open file ($file_write)!";
            exit;
        }
        if (fwrite($handle, $content) === FALSE) {
            echo "Cannot write to file ($file_write)!";
            exit;
        }
        fclose($handle);
    } else {
        echo "The file $file_write is not writable!";
    }
    $content = 0;
}

?>
