<?php

$command = null;

if (extension_loaded('xdebug') || extension_loaded('pcov')) {
    $command = 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'pest --coverage --min=80';
} elseif (defined('PHP_BINARY') && preg_match('/phpdbg/i', PHP_BINARY)) {
    $command = escapeshellcmd(PHP_BINARY) . ' -qrr vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'pest --coverage --min=80';
} elseif (stripos(PHP_OS_FAMILY, 'Windows') !== false && shell_exec('where phpdbg')) {
    $command = 'phpdbg -qrr vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'pest --coverage --min=80';
}

if ($command !== null) {
    passthru($command, $status);
    exit($status);
}

echo 'No coverage driver available. Running tests without coverage.' . PHP_EOL;
passthru('vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'pest', $status);
exit($status);
