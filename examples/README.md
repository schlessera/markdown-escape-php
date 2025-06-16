# Markdown Escape PHP - Examples

This directory contains examples demonstrating how to use the Markdown Escape PHP library's templating system.

## Examples Overview

### 01-basic-usage.php
Demonstrates basic template usage including:
- Simple string templates with PHP short tags
- Built-in templates (document, table, list, code-example)
- Basic variable escaping

### 02-custom-templates.php
Shows how to create and use custom templates:
- File-based templates
- User profile template example
- Release notes template example
- Template organization

### 03-advanced-features.php
Covers advanced templating features:
- Custom escapers for special formatting
- Template inheritance with chain loaders
- Dynamic template selection
- Error handling
- Complex data structure rendering

### 04-api-documentation.php
Demonstrates generating API documentation:
- Class documentation templates
- Full API reference generation
- Quick reference cards
- Nested template rendering

## Running the Examples

Each example is self-contained and can be run from the command line:

```bash
# Make sure dependencies are installed
composer install

# Run an example
php examples/01-basic-usage.php
```

## Template Directory

Some examples create template files in the `examples/templates/` directory. These are created automatically when you run the examples.

## Key Concepts

### 1. Template Variables
Templates have access to:
- `$md` or `$markdown` - The MarkdownEscape instance for escaping
- `$escapers` - Object providing access to all registered escapers
- `$context` - The template context with all variables
- Any variables passed to the `render()` method

### 2. Escaping Methods
Always escape dynamic content:
- `$md->escapeContent()` - For general text content
- `$md->escapeUrl()` - For URLs in links
- `$md->escapeInlineCode()` - For inline code
- `$md->escapeCodeBlock()` - For code blocks

### 3. Template Loading
Templates can be loaded from:
- Strings (using `renderString()`)
- Files (using file loader)
- Arrays (using array loader)
- Multiple sources (using chain loader)

### 4. Built-in Templates
The library includes templates for:
- `document` - Structured documents with sections
- `table` - Markdown tables
- `list` - Bulleted lists with sub-items
- `code-example` - Code examples with output
- `link-list` - Lists of links
- `api-method` - API endpoint documentation

## Creating Your Own Templates

1. Create a `.php` file with PHP short tags
2. Use `<?= $md->escapeContent($variable) ?>` to output escaped content
3. Use standard PHP control structures for logic
4. Save the file in a directory and add it to the template loader

Example template:

```php
# <?= $md->escapeContent($title) ?>

Published: <?= date('Y-m-d') ?>

<?php if (!empty($tags)): ?>
Tags: <?= implode(', ', array_map(function($tag) use ($md) {
    return '`' . $md->escapeInlineCode($tag) . '`';
}, $tags)) ?>
<?php endif; ?>

<?= $md->escapeContent($content) ?>
```

## Best Practices

1. **Always escape user content** - Use the appropriate escape method for the context
2. **Use built-in templates** - They handle escaping correctly
3. **Organize templates** - Use namespaces for different template categories
4. **Handle errors** - Catch `TemplateNotFoundException` and `TemplateRenderException`
5. **Cache templates** - The file loader caches templates automatically
6. **Test templates** - Ensure they handle edge cases and special characters

## Need Help?

Check the main documentation for more details on:
- [Architecture](../docs/architecture.md)
- [User Guide](../docs/user-guide.md)
- [API Reference](04-api-documentation.php)