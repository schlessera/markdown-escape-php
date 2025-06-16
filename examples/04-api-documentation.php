<?php

/**
 * Example 04: API Documentation Generation
 * 
 * This example shows how to generate complete API documentation
 * using the templating system.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Markdown\Escape\MarkdownTemplate;

// Create templates directory
$templatesDir = __DIR__ . '/templates';
if (!is_dir($templatesDir)) {
    mkdir($templatesDir, 0755, true);
}

// Create API class documentation template
file_put_contents($templatesDir . '/api-class.php', <<<'PHP'
## Class: `<?= $md->escapeInlineCode($class['name']) ?>`

<?php if (isset($class['namespace'])): ?>
**Namespace**: `<?= $md->escapeInlineCode($class['namespace']) ?>`  
<?php endif; ?>
<?php if (!empty($class['implements'])): ?>
**Implements**: <?= implode(', ', array_map(function($i) use ($md) {
    return '`' . $md->escapeInlineCode($i) . '`';
}, $class['implements'])) ?>  
<?php endif; ?>
<?php if (isset($class['extends'])): ?>
**Extends**: `<?= $md->escapeInlineCode($class['extends']) ?>`  
<?php endif; ?>

<?= $md->escapeContent($class['description']) ?>

<?php if (!empty($class['properties'])): ?>
### Properties

| Property | Type | Visibility | Description |
| --- | --- | --- | --- |
<?php foreach ($class['properties'] as $prop): ?>
| `<?= $md->escapeInlineCode($prop['name']) ?>` | `<?= $md->escapeInlineCode($prop['type']) ?>` | <?= $prop['visibility'] ?> | <?= $md->escapeContent($prop['description']) ?> |
<?php endforeach; ?>

<?php endif; ?>
<?php if (!empty($class['methods'])): ?>
### Methods

<?php foreach ($class['methods'] as $method): ?>
#### `<?= $md->escapeInlineCode($method['signature']) ?>`

<?= $md->escapeContent($method['description']) ?>

<?php if (!empty($method['parameters'])): ?>
**Parameters:**

| Name | Type | Description |
| --- | --- | --- |
<?php foreach ($method['parameters'] as $param): ?>
| `<?= $md->escapeInlineCode($param['name']) ?>` | `<?= $md->escapeInlineCode($param['type']) ?>` | <?= $md->escapeContent($param['description']) ?> |
<?php endforeach; ?>

<?php endif; ?>
<?php if (isset($method['return'])): ?>
**Returns:** `<?= $md->escapeInlineCode($method['return']['type']) ?>` - <?= $md->escapeContent($method['return']['description']) ?>

<?php endif; ?>
<?php if (!empty($method['throws'])): ?>
**Throws:**
<?php foreach ($method['throws'] as $exception): ?>
- `<?= $md->escapeInlineCode($exception['type']) ?>` - <?= $md->escapeContent($exception['description']) ?>
<?php endforeach; ?>

<?php endif; ?>
<?php if (isset($method['example'])): ?>
**Example:**

```php
<?= $md->escapeCodeBlock($method['example']) ?>
```

<?php endif; ?>
<?php endforeach; ?>
<?php endif; ?>
PHP
);

// Create full API documentation template
file_put_contents($templatesDir . '/api-full.php', <<<'PHP'
# <?= $md->escapeContent($title) ?>

<?= $md->escapeContent($description) ?>

## Table of Contents

<?php foreach ($sections as $section): ?>
- [<?= $md->escapeContent($section['title']) ?>](#<?= strtolower(str_replace(' ', '-', $section['title'])) ?>)
<?php endforeach; ?>

<?php foreach ($sections as $section): ?>
## <?= $md->escapeContent($section['title']) ?>

<?php if (isset($section['description'])): ?>
<?= $md->escapeContent($section['description']) ?>

<?php endif; ?>
<?php if ($section['type'] === 'classes' && !empty($section['items'])): ?>
<?php foreach ($section['items'] as $class): ?>
<?php 
// Render class documentation
echo $context->getMarkdownEscape()->getFactory()->createRenderer()->render('api-class', ['class' => $class] + $context->all());
?>

---

<?php endforeach; ?>
<?php elseif ($section['type'] === 'content'): ?>
<?= $section['content'] ?>
<?php endif; ?>

<?php endforeach; ?>
PHP
);

// Initialize template system
$template = MarkdownTemplate::gfm();
$template->addPath($templatesDir);

// Generate API documentation
$apiDocs = [
    'title' => 'Markdown Escape PHP - API Reference',
    'description' => 'Complete API documentation for the Markdown Escape PHP library.',
    'sections' => [
        [
            'title' => 'Overview',
            'type' => 'content',
            'content' => <<<'MD'
The Markdown Escape PHP library provides a robust system for escaping content to be safely embedded in Markdown documents. It supports multiple Markdown dialects and provides context-aware escaping.

### Key Features

- **Context-aware escaping** - Different escaping rules for different contexts
- **Multiple dialect support** - CommonMark and GitHub Flavored Markdown
- **Extensible architecture** - Add custom contexts, dialects, and escapers
- **Template system** - Generate complex Markdown documents with templates
MD
        ],
        [
            'title' => 'Core Classes',
            'type' => 'classes',
            'description' => 'The main classes you\'ll interact with when using the library.',
            'items' => [
                [
                    'name' => 'MarkdownEscape',
                    'namespace' => 'Markdown\\Escape',
                    'description' => 'Main facade class for escaping content. Provides convenient static factory methods and instance methods for different escaping contexts.',
                    'properties' => [
                        [
                            'name' => '$dialect',
                            'type' => 'DialectInterface',
                            'visibility' => 'private',
                            'description' => 'The Markdown dialect to use for escaping',
                        ],
                        [
                            'name' => '$factory',
                            'type' => 'EscaperFactoryInterface',
                            'visibility' => 'private',
                            'description' => 'Factory for creating context-specific escapers',
                        ],
                    ],
                    'methods' => [
                        [
                            'signature' => '__construct(?DialectInterface $dialect = null, ?EscaperFactoryInterface $factory = null)',
                            'description' => 'Creates a new MarkdownEscape instance with the specified dialect and factory.',
                            'parameters' => [
                                [
                                    'name' => '$dialect',
                                    'type' => '?DialectInterface',
                                    'description' => 'The dialect to use (defaults to CommonMark)',
                                ],
                                [
                                    'name' => '$factory',
                                    'type' => '?EscaperFactoryInterface',
                                    'description' => 'Custom escaper factory (optional)',
                                ],
                            ],
                        ],
                        [
                            'signature' => 'static commonMark(): self',
                            'description' => 'Creates an instance configured for CommonMark.',
                            'return' => [
                                'type' => 'self',
                                'description' => 'A new MarkdownEscape instance',
                            ],
                            'example' => '$escape = MarkdownEscape::commonMark();
$escaped = $escape->escapeContent("**bold** text");
// Result: \*\*bold\*\* text',
                        ],
                        [
                            'signature' => 'escapeContent(string $content): string',
                            'description' => 'Escapes general content (paragraphs, list items, etc).',
                            'parameters' => [
                                [
                                    'name' => '$content',
                                    'type' => 'string',
                                    'description' => 'The content to escape',
                                ],
                            ],
                            'return' => [
                                'type' => 'string',
                                'description' => 'The escaped content',
                            ],
                        ],
                        [
                            'signature' => 'escapeUrl(string $url): string',
                            'description' => 'Escapes URLs for use in links and images. Handles special characters like parentheses.',
                            'parameters' => [
                                [
                                    'name' => '$url',
                                    'type' => 'string',
                                    'description' => 'The URL to escape',
                                ],
                            ],
                            'return' => [
                                'type' => 'string',
                                'description' => 'The escaped URL',
                            ],
                            'example' => '$url = "https://en.wikipedia.org/wiki/PHP_(programming_language)";
$escaped = $escape->escapeUrl($url);
// Result: https://en.wikipedia.org/wiki/PHP_\\(programming_language\\)',
                        ],
                    ],
                ],
                [
                    'name' => 'MarkdownTemplate',
                    'namespace' => 'Markdown\\Escape',
                    'description' => 'Facade for template-based Markdown generation. Combines the escaping functionality with a powerful template engine.',
                    'methods' => [
                        [
                            'signature' => 'render(string $templateName, array $variables = []): string',
                            'description' => 'Renders a template with the given variables.',
                            'parameters' => [
                                [
                                    'name' => '$templateName',
                                    'type' => 'string',
                                    'description' => 'Name of the template to render',
                                ],
                                [
                                    'name' => '$variables',
                                    'type' => 'array',
                                    'description' => 'Variables to pass to the template',
                                ],
                            ],
                            'return' => [
                                'type' => 'string',
                                'description' => 'The rendered Markdown content',
                            ],
                            'throws' => [
                                [
                                    'type' => 'TemplateNotFoundException',
                                    'description' => 'If the template cannot be found',
                                ],
                                [
                                    'type' => 'TemplateRenderException',
                                    'description' => 'If rendering fails',
                                ],
                            ],
                        ],
                        [
                            'signature' => 'addPath(string $path, string $namespace = \'\'): self',
                            'description' => 'Adds a directory to search for templates.',
                            'parameters' => [
                                [
                                    'name' => '$path',
                                    'type' => 'string',
                                    'description' => 'Directory path containing templates',
                                ],
                                [
                                    'name' => '$namespace',
                                    'type' => 'string',
                                    'description' => 'Optional namespace for the templates',
                                ],
                            ],
                            'return' => [
                                'type' => 'self',
                                'description' => 'Fluent interface',
                            ],
                        ],
                    ],
                ],
            ],
        ],
        [
            'title' => 'Interfaces',
            'type' => 'classes',
            'description' => 'Key interfaces that define the contract for extending the library.',
            'items' => [
                [
                    'name' => 'EscaperInterface',
                    'namespace' => 'Markdown\\Escape\\Contract',
                    'description' => 'Interface for all escaper implementations. Implement this to create custom escapers.',
                    'methods' => [
                        [
                            'signature' => 'escape(string $text): string',
                            'description' => 'Escapes the given text according to the escaper\'s rules.',
                            'parameters' => [
                                [
                                    'name' => '$text',
                                    'type' => 'string',
                                    'description' => 'Text to escape',
                                ],
                            ],
                            'return' => [
                                'type' => 'string',
                                'description' => 'Escaped text',
                            ],
                        ],
                        [
                            'signature' => 'getContext(): ContextInterface',
                            'description' => 'Returns the context this escaper is designed for.',
                            'return' => [
                                'type' => 'ContextInterface',
                                'description' => 'The escaper\'s context',
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'DialectInterface',
                    'namespace' => 'Markdown\\Escape\\Contract',
                    'description' => 'Interface for Markdown dialect implementations. Defines the special characters and escaping rules for a specific Markdown flavor.',
                    'methods' => [
                        [
                            'signature' => 'getSpecialCharacters(): array',
                            'description' => 'Returns an array of special characters that need escaping in this dialect.',
                            'return' => [
                                'type' => 'array',
                                'description' => 'Array of special characters',
                            ],
                        ],
                        [
                            'signature' => 'escapeCharacter(string $char): string',
                            'description' => 'Escapes a single character according to the dialect\'s rules.',
                            'parameters' => [
                                [
                                    'name' => '$char',
                                    'type' => 'string',
                                    'description' => 'Character to escape',
                                ],
                            ],
                            'return' => [
                                'type' => 'string',
                                'description' => 'Escaped character',
                            ],
                        ],
                    ],
                ],
            ],
        ],
        [
            'title' => 'Built-in Templates',
            'type' => 'content',
            'content' => <<<'MD'
The library includes several built-in templates for common use cases:

### document
A basic document template with title, description, and sections.

**Variables:**
- `title` (string) - Document title
- `description` (string, optional) - Document description
- `sections` (array) - Array of sections with `title` and `content`

### table
Renders a Markdown table with headers and rows.

**Variables:**
- `headers` (array) - Column headers
- `rows` (array) - Array of row data

### list
Creates bulleted lists with optional sub-items.

**Variables:**
- `items` (array) - Array of items (strings or arrays with `text` and `subItems`)

### code-example
Displays code with syntax highlighting and optional output.

**Variables:**
- `title` (string, optional) - Example title
- `description` (string, optional) - Example description
- `language` (string) - Programming language for syntax highlighting
- `code` (string) - The code to display
- `output` (string, optional) - Expected output

### api-method
Documents an API endpoint with parameters and examples.

**Variables:**
- `method` (string) - HTTP method
- `endpoint` (string) - API endpoint path
- `description` (string) - Endpoint description
- `parameters` (array) - Array of parameter definitions
- `example` (array, optional) - Example request/response
MD
        ],
    ],
];

// Render the documentation
$result = $template->render('api-full', $apiDocs);

echo $result . "\n\n";

// Also generate a quick reference card
echo "=== Quick Reference Card ===\n\n";

$quickRef = $template->renderString(<<<'PHP'
## Markdown Escape - Quick Reference

### Basic Usage

```php
use Markdown\Escape\MarkdownEscape;

// Create escaper
$md = MarkdownEscape::commonMark(); // or ::gfm()

// Escape content
$escaped = $md->escapeContent("**bold** and *italic*");
// Result: \*\*bold\*\* and \*italic\*

// Escape URLs
$url = $md->escapeUrl("http://site.com/page(1)");
// Result: http://site.com/page\(1\)

// Escape inline code
$code = $md->escapeInlineCode("use `backticks`");
// Result: use \`backticks\`

// Escape code blocks
$block = $md->escapeCodeBlock("```\ncode\n```");
// Result: \`\`\`\ncode\n\`\`\`
```

### Template Usage

```php
use Markdown\Escape\MarkdownTemplate;

// Create template instance
$tpl = MarkdownTemplate::gfm();

// Add custom template directory
$tpl->addPath('/path/to/templates');

// Render built-in template
$result = $tpl->render('table', [
    'headers' => ['Col1', 'Col2'],
    'rows' => [['A', 'B'], ['C', 'D']]
]);

// Render string template
$result = $tpl->renderString(
    'Hello <?= $md->escapeContent($name) ?>!',
    ['name' => '**World**']
);
```

### Custom Templates

Create `.php` files with PHP short tags:

```php
# <?= $md->escapeContent($title) ?>

<?php foreach ($items as $item): ?>
- <?= $md->escapeContent($item) ?>
<?php endforeach; ?>
```

### Available Escapers in Templates

- `$md` or `$markdown` - Main MarkdownEscape instance
- `$escapers->content()` - General content escaping
- `$escapers->url()` - URL escaping
- `$escapers->inlineCode()` - Inline code escaping
- `$escapers->codeBlock()` - Code block escaping
- Custom escapers via `addEscaper()`
PHP
, []);

echo $quickRef . "\n";