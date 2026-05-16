<?php

declare(strict_types=1);

// Work around signature conflicts when ext-psr provides PsrExt\Log\LoggerInterface.
if (!extension_loaded('psr')) {
    fwrite(STDOUT, "ext-psr not loaded; no hotfix needed.\n");
    exit(0);
}

$loggerFile = __DIR__ . '/../vendor/monolog/monolog/src/Monolog/Logger.php';
if (!is_file($loggerFile)) {
    fwrite(STDOUT, "Monolog Logger.php not found; skipping.\n");
    exit(0);
}

$code = file_get_contents($loggerFile);
if ($code === false) {
    fwrite(STDERR, "Failed to read Monolog Logger.php\n");
    exit(1);
}

$replacements = [
    '/public function log\(\$level,\s*string\|Stringable\s+\$message,\s*array\s+\$context\s*=\s*\[\]\)\s*:\s*void/' => 'public function log($level, $message, array $context = [])',
    '/public function debug\(string\|Stringable\s+\$message,\s*array\s+\$context\s*=\s*\[\]\)\s*:\s*void/' => 'public function debug($message, array $context = [])',
    '/public function info\(string\|Stringable\s+\$message,\s*array\s+\$context\s*=\s*\[\]\)\s*:\s*void/' => 'public function info($message, array $context = [])',
    '/public function notice\(string\|Stringable\s+\$message,\s*array\s+\$context\s*=\s*\[\]\)\s*:\s*void/' => 'public function notice($message, array $context = [])',
    '/public function warning\(string\|Stringable\s+\$message,\s*array\s+\$context\s*=\s*\[\]\)\s*:\s*void/' => 'public function warning($message, array $context = [])',
    '/public function error\(string\|Stringable\s+\$message,\s*array\s+\$context\s*=\s*\[\]\)\s*:\s*void/' => 'public function error($message, array $context = [])',
    '/public function critical\(string\|Stringable\s+\$message,\s*array\s+\$context\s*=\s*\[\]\)\s*:\s*void/' => 'public function critical($message, array $context = [])',
    '/public function alert\(string\|Stringable\s+\$message,\s*array\s+\$context\s*=\s*\[\]\)\s*:\s*void/' => 'public function alert($message, array $context = [])',
    '/public function emergency\(string\|Stringable\s+\$message,\s*array\s+\$context\s*=\s*\[\]\)\s*:\s*void/' => 'public function emergency($message, array $context = [])',
];

$updated = preg_replace(array_keys($replacements), array_values($replacements), $code);
if ($updated === null) {
    fwrite(STDERR, "Regex error while patching Monolog Logger.php\n");
    exit(1);
}

if ($updated === $code) {
    fwrite(STDOUT, "Monolog hotfix already applied or not required.\n");
    exit(0);
}

if (file_put_contents($loggerFile, $updated) === false) {
    fwrite(STDERR, "Failed to write patched Monolog Logger.php\n");
    exit(1);
}

fwrite(STDOUT, "Applied Monolog ext-psr compatibility hotfix.\n");
exit(0);
