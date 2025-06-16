# Templating Guide

The Markdown Escape PHP library includes a powerful templating system that allows you to generate complex Markdown documents while ensuring all dynamic content is properly escaped.

## Table of Contents

- [Introduction](#introduction)
- [Getting Started](#getting-started)
- [Template Syntax](#template-syntax)
- [Built-in Templates](#built-in-templates)
- [Custom Templates](#custom-templates)
- [Template Loading](#template-loading)
- [Advanced Features](#advanced-features)
- [Best Practices](#best-practices)
- [API Reference](#api-reference)

## Introduction

The templating system combines PHP's native templating capabilities with the library's context-aware escaping functionality. This ensures that:

- Dynamic content is always properly escaped for Markdown
- Templates are easy to write and understand
- The system is extensible and customizable
- Performance is optimized through caching

### Key Features

- **PHP Short Tags**: Use familiar PHP syntax with `<?= ?>` for output
- **Automatic Escaping**: Access to all escaping methods within templates
- **Template Inheritance**: Override and extend templates using loaders
- **Multiple Sources**: Load templates from files, arrays, or custom sources
- **Built-in Templates**: Ready-to-use templates for common use cases

## Getting Started

### Basic Usage

```php
use Markdown\Escape\MarkdownTemplate;

// Create a template instance
$template = MarkdownTemplate::commonMark(); // or ::gfm() for GitHub Flavored

// Render a string template
$result = $template->renderString(
    'Hello, <?= $md->escapeContent($name) ?>!',
    ['name' => '**World**']
);
// Output: Hello, \*\*World\*\*!
```

### Using Built-in Templates

```php
// Render a table
$result = $template->render('table', [
    'headers' => ['Name', 'Status', 'Progress'],
    'rows' => [
        ['Feature **A**', 'Complete', '100%'],
        ['Feature *B*', 'In Progress', '75%'],
    ]
]);
```

## Template Syntax

Templates use PHP short tags for dynamic content. The following variables are always available:

### Available Variables

- `$md` or `$markdown` - The MarkdownEscape instance
- `$escapers` - Object providing access to all escapers
- `$context` - The template context with all variables
- All variables passed to `render()` or `renderString()`

### Escaping Methods

```php
// General content (paragraphs, list items, etc)
<?= $md->escapeContent($text) ?>

// URLs for links and images
<?= $md->escapeUrl($url) ?>

// Inline code
<?= $md->escapeInlineCode($code) ?>

// Code blocks
<?= $md->escapeCodeBlock($code) ?>
```

### Using Escapers Object

```php
// Access escapers through the $escapers object
<?= $escapers->content($text) ?>
<?= $escapers->url($link) ?>
<?= $escapers->inlineCode($code) ?>

// Custom escapers (if added)
<?= $escapers->custom($text) ?>
```

### Control Structures

```php
<?php if ($showSection): ?>
## <?= $md->escapeContent($sectionTitle) ?>
<?php endif; ?>

<?php foreach ($items as $item): ?>
- <?= $md->escapeContent($item) ?>
<?php endforeach; ?>
```

## Built-in Templates

The library includes several pre-built templates:

### document

Creates a structured document with title, description, and sections.

```php
$template->render('document', [
    'title' => 'My Document',
    'description' => 'Document description',
    'sections' => [
        ['title' => 'Section 1', 'content' => 'Content...'],
        ['title' => 'Section 2', 'content' => 'More content...'],
    ]
]);
```

### table

Renders a Markdown table with proper escaping.

```php
$template->render('table', [
    'headers' => ['Column 1', 'Column 2'],
    'rows' => [
        ['Row 1 Col 1', 'Row 1 Col 2'],
        ['Row 2 Col 1', 'Row 2 Col 2'],
    ]
]);
```

### list

Creates bulleted lists with optional sub-items.

```php
$template->render('list', [
    'items' => [
        'Simple item',
        [
            'text' => 'Item with sub-items',
            'subItems' => ['Sub 1', 'Sub 2']
        ],
    ]
]);
```

### code-example

Displays code with syntax highlighting and optional output.

```php
$template->render('code-example', [
    'title' => 'Example',
    'description' => 'Shows how to use the feature',
    'language' => 'php',
    'code' => '<?php echo "Hello"; ?>',
    'output' => 'Hello'
]);
```

### link-list

Creates a list of links with optional descriptions.

```php
$template->render('link-list', [
    'links' => [
        ['text' => 'GitHub', 'url' => 'https://github.com'],
        ['text' => 'Docs', 'url' => './docs', 'description' => 'Documentation'],
    ]
]);
```

### api-method

Documents API endpoints.

```php
$template->render('api-method', [
    'method' => 'GET',
    'endpoint' => '/api/users/{id}',
    'description' => 'Get user by ID',
    'parameters' => [
        ['name' => 'id', 'type' => 'integer', 'required' => true, 'description' => 'User ID'],
    ],
    'example' => [
        'language' => 'json',
        'code' => '{"id": 1, "name": "John"}'
    ]
]);
```

## Custom Templates

### Creating Custom Templates

Create a `.php` file with your template:

```php
// templates/article.php
# <?= $md->escapeContent($title) ?>

*By <?= $md->escapeContent($author) ?> on <?= date('F j, Y', strtotime($date)) ?>*

<?php if (!empty($tags)): ?>
**Tags**: <?= implode(', ', array_map(function($tag) use ($md) {
    return '`' . $md->escapeInlineCode($tag) . '`';
}, $tags)) ?>

<?php endif; ?>
<?= $md->escapeContent($content) ?>

<?php if (!empty($related)): ?>
## Related Articles

<?php foreach ($related as $article): ?>
- [<?= $md->escapeContent($article['title']) ?>](<?= $md->escapeUrl($article['url']) ?>)
<?php endforeach; ?>
<?php endif; ?>
```

### Adding Custom Templates

```php
// Add a directory containing templates
$template->addPath('/path/to/templates');

// Add templates with namespace
$template->addPath('/path/to/admin/templates', 'admin');

// Add default templates programmatically
$template->addDefaultTemplates([
    'greeting' => 'Hello, <?= $md->escapeContent($name) ?>!',
]);

// Render custom template
$result = $template->render('article', [
    'title' => 'My Article',
    'author' => 'John Doe',
    'date' => '2024-01-15',
    'content' => 'Article content...',
    'tags' => ['php', 'markdown'],
]);
```

## Template Loading

The library supports multiple template loading strategies:

### File Template Loader

Loads templates from the filesystem:

```php
use Markdown\Escape\Template\Loader\FileTemplateLoader;

$loader = new FileTemplateLoader('/path/to/templates');
$loader->addPath('admin', '/path/to/admin/templates');
```

### Array Template Loader

Stores templates in memory:

```php
use Markdown\Escape\Template\Loader\ArrayTemplateLoader;

$loader = new ArrayTemplateLoader([
    'welcome' => 'Welcome, <?= $name ?>!',
    'goodbye' => 'Goodbye, <?= $name ?>!',
]);
```

### Chain Template Loader

Combines multiple loaders with priority:

```php
use Markdown\Escape\Template\Loader\ChainTemplateLoader;

$defaultLoader = new ArrayTemplateLoader($defaultTemplates);
$defaultLoader->setPriority(10);

$customLoader = new FileTemplateLoader('/custom/templates');
$customLoader->setPriority(20); // Higher priority

$chainLoader = new ChainTemplateLoader([$defaultLoader, $customLoader]);
```

## Advanced Features

### Custom Escapers

Create custom escapers for special formatting:

```php
use Markdown\Escape\Escaper\AbstractEscaper;

class HighlightEscaper extends AbstractEscaper {
    public function escape(string $text): string {
        // Escape markdown and wrap in highlight syntax
        $escaped = $this->dialect->escapeSpecialCharacters($text);
        return "=={$escaped}==";
    }
}

// Add to renderer
$renderer = $template->getRenderer();
$renderer->addEscaper('highlight', new HighlightEscaper($context, $dialect));

// Use in templates
// <?= $escapers->highlight($importantText) ?>
```

### Template Context

Access and manipulate the template context:

```php
// In template:
<?php $context->set('computed', $value * 2); ?>
<?= $context->get('computed') ?>

// Check if variable exists
<?php if ($context->has('optional')): ?>
    <?= $md->escapeContent($context->get('optional')) ?>
<?php endif; ?>
```

### Nested Template Rendering

Render templates within templates:

```php
// In a template:
<?php 
$renderer = $context->getMarkdownEscape()->getFactory()->createRenderer();
echo $renderer->render('partial', ['data' => $data] + $context->all());
?>
```

### Error Handling

Handle template errors gracefully:

```php
use Markdown\Escape\Exception\TemplateNotFoundException;
use Markdown\Escape\Exception\TemplateRenderException;

try {
    $result = $template->render('my-template', $data);
} catch (TemplateNotFoundException $e) {
    echo "Template not found: " . $e->getTemplateName();
    echo "Searched in: " . implode(', ', $e->getSearchPaths());
} catch (TemplateRenderException $e) {
    echo "Render error in template: " . $e->getTemplateName();
    echo "Error: " . $e->getMessage();
}
```

## Best Practices

### 1. Always Escape Dynamic Content

Never output user content without escaping:

```php
// ❌ Bad
<?= $userInput ?>

// ✅ Good
<?= $md->escapeContent($userInput) ?>
```

### 2. Use the Right Escaper

Choose the appropriate escaper for the context:

```php
// For general content
<?= $md->escapeContent($text) ?>

// For URLs in links
[Link](<?= $md->escapeUrl($url) ?>)

// For inline code
Use `<?= $md->escapeInlineCode($code) ?>` for this

// For code blocks
```
<?= $md->escapeCodeBlock($code) ?>
``
```

### 3. Organize Templates

Use namespaces to organize templates:

```php
$template->addPath('/templates/emails', 'email');
$template->addPath('/templates/docs', 'docs');

// Use namespaced templates
$template->render('email/welcome', $data);
$template->render('docs/api', $data);
```

### 4. Cache Compiled Templates

The file loader automatically caches loaded templates. For production, ensure the template cache directory is writable.

### 5. Validate Template Data

Validate data before passing to templates:

```php
// Ensure required fields exist
$data = array_merge([
    'title' => 'Untitled',
    'content' => '',
    'author' => 'Anonymous',
], $userData);

$result = $template->render('article', $data);
```

### 6. Use Type Hints in Complex Templates

For complex templates, document expected variables:

```php
<?php
/**
 * Article Template
 * 
 * @var string $title Article title
 * @var string $author Author name
 * @var string $date Publication date (Y-m-d)
 * @var string $content Article content
 * @var array $tags Array of tag strings
 * @var array $related Array of related articles
 */
?>
# <?= $md->escapeContent($title) ?>
```

## API Reference

### MarkdownTemplate

Main facade for template rendering.

#### Methods

- `__construct(?DialectInterface $dialect = null)` - Create instance
- `static commonMark()` - Create CommonMark instance
- `static gfm()` - Create GitHub Flavored instance
- `render(string $template, array $vars = [])` - Render template
- `renderString(string $content, array $vars = [])` - Render string
- `addPath(string $path, string $namespace = '')` - Add template directory
- `addDefaultTemplates(array $templates)` - Add default templates
- `getRenderer()` - Get the renderer instance
- `getMarkdownEscape()` - Get the escape instance

### TemplateRenderer

Core rendering engine.

#### Methods

- `render(string $name, array $vars = [])` - Render template by name
- `renderString(string $content, array $vars = [])` - Render string template
- `setLoader(TemplateLoaderInterface $loader)` - Set template loader
- `setEngine(TemplateEngineInterface $engine)` - Set template engine
- `setMarkdownEscape(MarkdownEscape $escape)` - Set escape instance
- `addEscaper(string $name, EscaperInterface $escaper)` - Add custom escaper

### Template Loaders

#### FileTemplateLoader

- `__construct($paths = [], string $extension = '.php')` - Create file loader
- `addPath(string $namespace, $path)` - Add search path

#### ArrayTemplateLoader

- `__construct(array $templates = [])` - Create array loader
- `addTemplate(string $name, $template)` - Add template

#### ChainTemplateLoader

- `__construct(array $loaders = [])` - Create chain loader
- `addLoader(TemplateLoaderInterface $loader)` - Add loader to chain

## See Also

- [Examples](../examples/) - Working examples of template usage
- [Architecture](architecture.md) - Understanding the system design
- [User Guide](user-guide.md) - General usage documentation
