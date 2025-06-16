#!/usr/bin/env php
<?php

/**
 * Generate coverage badge from clover.xml
 */

if (!file_exists(__DIR__ . '/../coverage/clover.xml')) {
    echo "Coverage file not found. Run 'composer test:coverage:clover' first.\n";
    exit(1);
}

$xml = simplexml_load_file(__DIR__ . '/../coverage/clover.xml');
$metrics = $xml->xpath('//metrics');

$totalElements = 0;
$checkedElements = 0;

foreach ($metrics as $metric) {
    $totalElements += (int) $metric['elements'];
    $checkedElements += (int) $metric['coveredelements'];
}

$coverage = ($totalElements > 0) ? ($checkedElements / $totalElements) * 100 : 0;
$coverage = round($coverage, 2);

// Determine color based on coverage
if ($coverage >= 90) {
    $color = 'brightgreen';
} elseif ($coverage >= 80) {
    $color = 'green';
} elseif ($coverage >= 70) {
    $color = 'yellowgreen';
} elseif ($coverage >= 60) {
    $color = 'yellow';
} elseif ($coverage >= 50) {
    $color = 'orange';
} else {
    $color = 'red';
}

// Generate badge URL
$badgeUrl = sprintf(
    'https://img.shields.io/badge/coverage-%s%%25-%s',
    $coverage,
    $color
);

echo "Coverage: {$coverage}%\n";
echo "Badge URL: {$badgeUrl}\n";
echo "\nMarkdown:\n";
echo "![Coverage]({$badgeUrl})\n";

// Save coverage percentage to file
file_put_contents(__DIR__ . '/../coverage/coverage-percentage.txt', $coverage);