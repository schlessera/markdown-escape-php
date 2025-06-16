# User Guide

This guide covers all aspects of using the Markdown Escape PHP library.

## Table of Contents

1. [Introduction](#introduction)
2. [Installation](#installation)
3. [Basic Usage](#basic-usage)
4. [Contexts](#contexts)
5. [Dialects](#dialects)
6. [Templating](#templating)
7. [Advanced Usage](#advanced-usage)
8. [Best Practices](#best-practices)

## Introduction

Markdown Escape PHP is a library designed to safely escape content that will be embedded in Markdown documents. It prevents your content from being interpreted as Markdown formatting, ensuring that special characters are displayed literally.

### Why Use This Library?

When programmatically generating Markdown content, you often need to include user-provided text or data that might contain Markdown special characters. Without proper escaping, these characters could break your document's formatting or create unintended structures.

## Installation

Install the library using Composer:

```bash
composer require schlessera/markdown-escape
```

## Basic Usage

### Creating an Escaper Instance

```php
use Markdown\Escape\MarkdownEscape;

// Default instance (uses CommonMark dialect)
$escape = new MarkdownEscape();

// Specific dialect instances
$commonMark = MarkdownEscape::commonMark();
$gfm = MarkdownEscape::gfm();
```

### Escaping Content

The library provides convenient methods for common escaping scenarios:

```php
// Escape general content
$text = 'This has *asterisks* and _underscores_';
$escaped = $escape->escapeContent($text);
// Result: This has \*asterisks\* and \_underscores\_

// Escape URLs
$url = 'https://example.com/path with spaces';
$escaped = $escape->escapeUrl($url);
// Result: https://example.com/path%20with%20spaces

// Escape inline code
$code = 'Use `backticks` for inline code';
$escaped = $escape->escapeInlineCode($code);
// Result: ``Use `backticks` for inline code``

// Escape code blocks
$code = "function test() {\n    return true;\n}";
$escaped = $escape->escapeCodeBlock($code, [
    'use_fences' => true,
    'language' => 'php'
]);
```

## Contexts

Contexts define what type of content you're escaping and how it should be handled.

### General Content Context

Used for regular text that might appear anywhere in a Markdown document:

```php
use Markdown\Escape\Context\GeneralContentContext;

$context = new GeneralContentContext();
$escaped = $escape->escape('# Not a heading', $context);
```

### URL Context

Specifically for URLs that will be used in links:

```php
use Markdown\Escape\Context\UrlContext;

$context = new UrlContext(['encode_unicode' => true]);
$escaped = $escape->escape('https://example.com/página', $context);
```

### Inline Code Context

For content that will be wrapped in backticks:

```php
use Markdown\Escape\Context\InlineCodeContext;

$context = new InlineCodeContext();
$escaped = $escape->escape('const a = `template`', $context);
```

### Code Block Context

For multi-line code that will be in code blocks:

```php
use Markdown\Escape\Context\CodeBlockContext;

$context = new CodeBlockContext([
    'use_fences' => true,
    'language' => 'javascript'
]);
$escaped = $escape->escape($code, $context);
```

## Dialects

### CommonMark

The default dialect, supporting standard CommonMark features:

```php
$escape = MarkdownEscape::commonMark();
```

Features:

- Basic emphasis (*italic* and **bold**)
- Links and images
- Code blocks and inline code
- Lists (ordered and unordered)
- Blockquotes
- Headings
- Tables

### GitHub Flavored Markdown (GFM)

Extended dialect with GitHub-specific features:

```php
$escape = MarkdownEscape::gfm();
```

Additional features:

- Task lists
- Strikethrough (~~text~~)
- Autolinks
- Mentions (@username)
- Emoji (:emoji:)
- Footnotes

### Switching Dialects

You can switch dialects on an existing instance:

```php
$escape = new MarkdownEscape();
$gfmDialect = new GitHubFlavoredMarkdownDialect();
$newEscape = $escape->withDialect($gfmDialect);
```

## Templating

The library includes a powerful templating system for generating complex Markdown documents. Templates use PHP short tags and automatically handle escaping.

### Quick Start

```php
use Markdown\Escape\MarkdownTemplate;

// Create a template instance
$template = MarkdownTemplate::gfm();

// Render a string template
$result = $template->renderString(
    'Hello, <?= $md->escapeContent($name) ?>!',
    ['name' => '**World**']
);
// Output: Hello, \*\*World\*\*!

// Use built-in templates
$result = $template->render('table', [
    'headers' => ['Name', 'Value'],
    'rows' => [['Item *1*', '100%']]
]);
```

### Built-in Templates

The library includes several ready-to-use templates:

- `document` - Structured documents with sections
- `table` - Markdown tables
- `list` - Bulleted lists with sub-items
- `code-example` - Code examples with output
- `link-list` - Lists of links
- `api-method` - API endpoint documentation

### Custom Templates

Create your own templates:

```php
// Add a directory containing templates
$template->addPath('/path/to/templates');

// Add default templates programmatically
$template->addDefaultTemplates([
    'greeting' => 'Hello, <?= $md->escapeContent($name) ?>!',
]);
```

### Template Variables

Templates have access to:

- `$md` or `$markdown` - The MarkdownEscape instance
- `$escapers` - Object providing access to all escapers
- All variables passed to render()

For comprehensive templating documentation, see the [Templating Guide](templating-guide.md).

## Advanced Usage

### Custom Contexts

Create your own context by extending `AbstractContext`:

```php
use Markdown\Escape\Context\AbstractContext;

class CustomContext extends AbstractContext
{
    public const NAME = 'custom';
    
    protected function configure(): void
    {
        $this->escapingTypes = ['custom_type'];
    }
}
```

### Working with the Factory

For more control, work directly with the factory:

```php
use Markdown\Escape\EscaperFactory;
use Markdown\Escape\Context\GeneralContentContext;
use Markdown\Escape\Dialect\CommonMarkDialect;

$factory = new EscaperFactory();
$context = new GeneralContentContext();
$dialect = new CommonMarkDialect();

$escaper = $factory->createEscaper($context, $dialect);
$escaped = $escaper->escape('Content to escape');
```

### Registering Custom Escapers

```php
$factory = new EscaperFactory();
$factory->registerEscaper('custom', 'commonmark', $customEscaper);
```

## Best Practices

### 1. Choose the Right Context

Always use the most specific context for your use case:

```php
// Good: Using URL context for URLs
$escaped = $escape->escapeUrl($userProvidedUrl);

// Bad: Using general content context for URLs
$escaped = $escape->escapeContent($userProvidedUrl);
```

### 2. Consider Your Target Platform

Use the appropriate dialect for where your Markdown will be rendered:

```php
// For GitHub
$escape = MarkdownEscape::gfm();

// For standard Markdown parsers
$escape = MarkdownEscape::commonMark();
```

### 3. Handle User Input Safely

Always escape user-provided content before including it in Markdown:

```php
$comment = $_POST['comment'];
$safeComment = $escape->escapeContent($comment);
$markdown = "## User Comment\n\n" . $safeComment;
```

### 4. Code Block Language Specification

When escaping code blocks, specify the language for syntax highlighting:

```php
$code = file_get_contents('example.php');
$escaped = $escape->escapeCodeBlock($code, [
    'use_fences' => true,
    'language' => 'php'
]);
```

### 5. URL Encoding Options

For international URLs, consider Unicode encoding:

```php
$url = 'https://example.com/文档';
$escaped = $escape->escapeUrl($url, ['encode_unicode' => true]);
```

## Examples

### Building a Markdown Document

```php
$escape = MarkdownEscape::gfm();

$title = $escape->escapeContent($userTitle);
$description = $escape->escapeContent($userDescription);
$codeSnippet = $escape->escapeCodeBlock($userCode, [
    'use_fences' => true,
    'language' => 'php'
]);

$markdown = <<<MD
# {$title}

{$description}

## Example Code

{$codeSnippet}

For more information, visit [{$escape->escapeContent($linkText)}]({$escape->escapeUrl($linkUrl)})
MD;
```

### Escaping a Complex Table

```php
$headers = ['Name', 'Value*', 'Description'];
$rows = [
    ['config_file', '/etc/app.conf', 'Main [configuration] file'],
    ['log|level', 'debug', 'Logging level (debug|info|warn|error)']
];

$markdown = "| " . implode(" | ", array_map([$escape, 'escapeContent'], $headers)) . " |\n";
$markdown .= "|" . str_repeat(" --- |", count($headers)) . "\n";

foreach ($rows as $row) {
    $markdown .= "| " . implode(" | ", array_map([$escape, 'escapeContent'], $row)) . " |\n";
}
```
