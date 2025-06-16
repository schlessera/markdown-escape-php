<?php

/**
 * Example 01: Basic Template Usage
 * 
 * This example demonstrates basic template rendering with the MarkdownTemplate facade.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Markdown\Escape\MarkdownTemplate;

// Create a template instance (defaults to CommonMark)
$template = new MarkdownTemplate();

// Example 1: Render a simple string template
echo "=== Example 1: Simple String Template ===\n\n";

$result = $template->renderString(
    'Hello, <?= $name ?>! Welcome to **<?= $project ?>**.',
    [
        'name' => 'Developer',
        'project' => 'Markdown Templates',
    ]
);

echo $result . "\n\n";

// Example 2: Using built-in document template
echo "=== Example 2: Built-in Document Template ===\n\n";

$result = $template->render('document', [
    'title' => 'Getting Started with **Templates**',
    'description' => 'Learn how to use the *powerful* templating system.',
    'sections' => [
        [
            'title' => 'Installation',
            'content' => 'Run `composer require schlessera/markdown-escape` to install.',
        ],
        [
            'title' => 'Basic Usage',
            'content' => 'Create templates with PHP short tags and [markdown] content.',
        ],
    ],
]);

echo $result . "\n\n";

// Example 3: Using built-in table template
echo "=== Example 3: Table Template ===\n\n";

$result = $template->render('table', [
    'headers' => ['Feature', 'Description', 'Status'],
    'rows' => [
        ['Escaping', 'Automatically escapes **special** characters', '✓ Complete'],
        ['Templates', 'PHP short tags with <?= $variable ?> support', '✓ Complete'],
        ['Dialects', 'Supports CommonMark & GFM', '✓ Complete'],
    ],
]);

echo $result . "\n\n";

// Example 4: Using built-in list template
echo "=== Example 4: List Template ===\n\n";

$result = $template->render('list', [
    'items' => [
        'Simple item with **bold** text',
        [
            'text' => 'Item with sub-items',
            'subItems' => [
                'First *sub-item*',
                'Second [sub-item]',
                'Third `sub-item`',
            ],
        ],
        'Another simple item',
    ],
]);

echo $result . "\n\n";

// Example 5: Code example template
echo "=== Example 5: Code Example Template ===\n\n";

$result = $template->render('code-example', [
    'title' => 'Escaping Example',
    'description' => 'How to escape markdown content:',
    'language' => 'php',
    'code' => '<?php
use Markdown\Escape\MarkdownEscape;

$markdown = MarkdownEscape::commonMark();
$escaped = $markdown->escapeContent("This has **bold** and *italic* text!");
echo $escaped; // Output: This has \*\*bold\*\* and \*italic\* text!',
    'output' => 'This has \*\*bold\*\* and \*italic\* text!',
]);

echo $result . "\n";