# Markdown Escape PHP [⚠️ WIP ⚠️]

[![Tests](https://github.com/schlessera/markdown-escape-php/workflows/Tests/badge.svg)](https://github.com/schlessera/markdown-escape-php/actions)
[![Code Coverage](https://codecov.io/gh/schlessera/markdown-escape-php/branch/main/graph/badge.svg)](https://codecov.io/gh/schlessera/markdown-escape-php)
[![Latest Stable Version](https://poser.pugx.org/schlessera/markdown-escape/v/stable)](https://packagist.org/packages/schlessera/markdown-escape)
[![License](https://poser.pugx.org/schlessera/markdown-escape/license)](https://packagist.org/packages/schlessera/markdown-escape)

A PHP library for escaping content to be safely embedded in Markdown without breaking rendering. Supports multiple Markdown dialects and provides context-aware escaping for different use cases.

## Features

- **Multiple Dialect Support**: CommonMark and GitHub Flavored Markdown (GFM)
- **Context-Aware Escaping**: Different escaping strategies for URLs, inline code, code blocks, and general content
- **Templating System**: Built-in templating engine with PHP short tags for generating complex Markdown documents
- **SOLID Design**: Built with clean architecture and design patterns for easy extension
- **PHP 7.2+ Compatible**: Works with PHP 7.2 and all newer versions
- **Fully Tested**: Comprehensive unit and feature tests
- **Zero Dependencies**: No runtime dependencies beyond PHP itself
- **High Test Coverage**: 98%+ code coverage with comprehensive unit and integration tests

## Installation

Install via Composer:

```bash
composer require schlessera/markdown-escape
```

## Quick Start

```php
use Markdown\Escape\MarkdownEscape;

// Create an escaper instance (defaults to CommonMark)
$escape = new MarkdownEscape();

// Or use factory methods for specific dialects
$escape = MarkdownEscape::commonMark();
$escape = MarkdownEscape::gfm();

// Escape general content
$escaped = $escape->escapeContent('This is *bold* and _italic_ text');
// Output: This is \*bold\* and \_italic\_ text

// Escape URLs
$escaped = $escape->escapeUrl('https://example.com/path with spaces/(parentheses)');
// Output: https://example.com/path%20with%20spaces/%28parentheses%29

// Escape inline code
$escaped = $escape->escapeInlineCode('Use `backticks` for code');
// Output: ``Use `backticks` for code``

// Escape code blocks
$code = "function test() {\n    return true;\n}";
$escaped = $escape->escapeCodeBlock($code, ['use_fences' => true, 'language' => 'php']);
// Output: ```php
//         function test() {
//             return true;
//         }
//         ```
```

### Templating System

Generate complex Markdown documents using the built-in templating system:

```php
use Markdown\Escape\MarkdownTemplate;

// Create template instance
$template = MarkdownTemplate::gfm();

// Use built-in templates
$result = $template->render('table', [
    'headers' => ['Name', 'Status', 'Progress'],
    'rows' => [
        ['Feature **A**', 'Complete', '100%'],
        ['Feature *B*', 'In Progress', '75%']
    ]
]);

// Custom templates with automatic escaping
$result = $template->renderString('
# <?= $md->escapeContent($title) ?>

Published by <?= $md->escapeContent($author) ?> on <?= date("Y-m-d") ?>

<?php foreach ($sections as $section): ?>
## <?= $md->escapeContent($section["title"]) ?>

<?= $md->escapeContent($section["content"]) ?>
<?php endforeach; ?>
', [
    'title' => 'My **Important** Document',
    'author' => 'John [Admin] Doe',
    'sections' => [
        ['title' => 'Introduction', 'content' => 'Content with *markdown* syntax...']
    ]
]);
```

## Documentation

- [User Guide](docs/user-guide.md) - Comprehensive guide to using the library
- [Templating Guide](docs/templating-guide.md) - Complete guide to the templating system
- [Architecture](docs/architecture.md) - Understanding the library's design
- [Examples](examples/) - Working examples demonstrating various features
- [Code Coverage](docs/code-coverage.md) - Testing and coverage information

## Requirements

- PHP 7.2 or higher
- Composer for dependency management

## Development

### Running Tests

```bash
composer test              # Run all tests
composer test:unit         # Run unit tests only
composer test:integration  # Run integration tests only
composer test:performance  # Run performance tests
```

### Code Coverage

```bash
composer test:coverage     # Generate HTML coverage report and text summary
composer test:coverage:clover  # Generate Clover XML report
composer coverage:badge    # Generate coverage badge

# View coverage report
open coverage/html/index.html  # macOS
xdg-open coverage/html/index.html  # Linux
start coverage/html/index.html  # Windows
```

### Code Style

```bash
composer cs       # Check code style
composer cs-fix   # Fix code style issues
```

### Static Analysis

```bash
composer phpstan
```

### PHP Compatibility Check

```bash
composer phpcompat  # Checks PHP 7.2+ compatibility
```

### Full Check

```bash
composer check    # Runs all checks (CS, PHPStan, PHP compatibility, and tests)
```

## License

This library is released under the MIT License. See [LICENSE](LICENSE) file for details.

## Contributing

Contributions are welcome! Please read our [Contributing Guide](CONTRIBUTING.md) for details on our code of conduct and the process for submitting pull requests.

## Support

If you discover any security related issues, please email alain.schlesser@gmail.com instead of using the issue tracker.
