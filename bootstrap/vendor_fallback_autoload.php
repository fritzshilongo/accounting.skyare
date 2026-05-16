<?php

$vendorRoot = dirname(__DIR__) . '/vendor';

if (!is_dir($vendorRoot)) {
    return;
}

$fallbackAutoloaders = [
    'Safe\\Exceptions\\' => [$vendorRoot . '/thecodingmachine/safe/generated/Exceptions'],
    'Safe\\' => [$vendorRoot . '/thecodingmachine/safe/lib'],
];
$autoloadFiles = [];

$autoloadFilePriority = [
    'thecodingmachine/safe' => -200,
    'laravel/framework' => -100,
];

$packageComposerFiles = glob($vendorRoot . '/*/*/composer.json') ?: [];

foreach ($packageComposerFiles as $composerFile) {
    $packageDir = dirname($composerFile);
    $composerConfig = json_decode(file_get_contents($composerFile), true);

    if (!is_array($composerConfig) || !isset($composerConfig['autoload'])) {
        continue;
    }

    $autoload = $composerConfig['autoload'];
    $packageName = $composerConfig['name'] ?? '';
    $filePriority = $autoloadFilePriority[$packageName] ?? 0;

    foreach (($autoload['files'] ?? []) as $relativePath) {
        $autoloadFiles[] = [
            'path' => $packageDir . '/' . ltrim(str_replace('\\', '/', $relativePath), '/'),
            'priority' => $filePriority,
        ];
    }

    foreach (($autoload['psr-4'] ?? []) as $prefix => $paths) {
        foreach ((array) $paths as $relativePath) {
            $resolvedPath = $packageDir . '/' . ltrim(str_replace('\\', '/', $relativePath), '/');
            $fallbackAutoloaders[$prefix][] = rtrim($resolvedPath, '/');
        }
    }
}

spl_autoload_register(static function (string $class) use ($fallbackAutoloaders): void {
    foreach ($fallbackAutoloaders as $prefix => $basePaths) {
        if (!str_starts_with($class, $prefix)) {
            continue;
        }

        $relativeClass = substr($class, strlen($prefix));
        $relativePath = str_replace('\\', '/', $relativeClass) . '.php';

        foreach ($basePaths as $basePath) {
            $candidate = $basePath . '/' . $relativePath;

            if (is_file($candidate)) {
                require_once $candidate;
                return;
            }
        }
    }
});

usort($autoloadFiles, static function (array $left, array $right): int {
    return $left['priority'] <=> $right['priority'];
});

foreach ($autoloadFiles as $autoloadFile) {
    if (is_file($autoloadFile['path'])) {
        require_once $autoloadFile['path'];
    }
}