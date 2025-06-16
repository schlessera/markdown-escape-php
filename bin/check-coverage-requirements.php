#!/usr/bin/env php
<?php

echo "Checking code coverage requirements...\n\n";

// Check PHP version
echo "PHP Version: " . PHP_VERSION . "\n";

// Check for coverage extensions
$extensions = [
    'xdebug' => 'Xdebug',
    'pcov' => 'PCOV',
];

$available = [];
foreach ($extensions as $ext => $name) {
    if (extension_loaded($ext)) {
        $available[] = $name;
        echo "✓ $name extension is installed\n";
    } else {
        echo "✗ $name extension is NOT installed\n";
    }
}

echo "\n";

if (empty($available)) {
    echo "⚠️  No code coverage driver available!\n\n";
    echo "To enable code coverage, install one of the following:\n";
    echo "- Xdebug: pecl install xdebug\n";
    echo "- PCOV: pecl install pcov\n";
    echo "\nFor development, PCOV is recommended as it's faster.\n";
    echo "For CI/CD, PCOV is usually pre-installed.\n";
} else {
    echo "✅ Code coverage is available using: " . implode(', ', $available) . "\n";
}

// Check if running in CI
if (getenv('CI')) {
    echo "\n📦 Running in CI environment\n";
}