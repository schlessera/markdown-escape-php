<?php

/**
 * Example 03: Advanced Features
 * 
 * This example demonstrates advanced templating features including:
 * - Custom escapers
 * - Template inheritance with chain loader
 * - Dynamic template selection
 * - Error handling
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Markdown\Escape\MarkdownTemplate;
use Markdown\Escape\Template\TemplateRenderer;
use Markdown\Escape\Template\Loader\ArrayTemplateLoader;
use Markdown\Escape\Template\Loader\FileTemplateLoader;
use Markdown\Escape\Template\Loader\ChainTemplateLoader;
use Markdown\Escape\Context\AbstractContext;
use Markdown\Escape\Escaper\AbstractEscaper;
use Markdown\Escape\Contract\DialectInterface;

// Example 1: Custom Escaper for Special Formatting
echo "=== Example 1: Custom Escaper ===\n\n";

// Create a custom escaper that adds emoji indicators
class EmojiEscaper extends AbstractEscaper
{
    public function escape(string $text): string
    {
        // Add emoji based on content
        if (stripos($text, 'error') !== false || stripos($text, 'fail') !== false) {
            return '❌ ' . $this->dialect->escapeSpecialCharacters($text);
        }
        if (stripos($text, 'success') !== false || stripos($text, 'pass') !== false) {
            return '✅ ' . $this->dialect->escapeSpecialCharacters($text);
        }
        if (stripos($text, 'warning') !== false || stripos($text, 'caution') !== false) {
            return '⚠️ ' . $this->dialect->escapeSpecialCharacters($text);
        }
        
        return $this->dialect->escapeSpecialCharacters($text);
    }
    
    public function supportsDialect(DialectInterface $dialect): bool
    {
        return true; // Supports all dialects
    }
}

// Create a custom context for status messages
class StatusContext extends AbstractContext
{
    public function getName(): string
    {
        return 'status';
    }
}

// Set up template with custom escaper
$template = MarkdownTemplate::gfm();
$renderer = $template->getRenderer();

// Create and add custom escaper
$statusContext = new StatusContext();
$emojiEscaper = new EmojiEscaper($statusContext, $template->getMarkdownEscape()->getDialect());
$renderer->addEscaper('status', $emojiEscaper);

// Render template with custom escaper
$result = $renderer->renderString(<<<'PHP'
## Test Results

<?php foreach ($results as $test): ?>
- <?= $escapers->status($test['name'] . ': ' . $test['status']) ?>
  - Time: <?= $test['time'] ?>ms
  - Memory: <?= $test['memory'] ?>MB
<?php endforeach; ?>
PHP
, [
    'results' => [
        ['name' => 'Unit Tests', 'status' => 'All tests passed', 'time' => 234, 'memory' => 12.5],
        ['name' => 'Integration Tests', 'status' => '2 tests failed', 'time' => 1502, 'memory' => 45.2],
        ['name' => 'Performance Tests', 'status' => 'Warning: Slow response', 'time' => 5234, 'memory' => 128.7],
    ],
]);

echo $result . "\n\n";

// Example 2: Template Inheritance with Priority
echo "=== Example 2: Template Inheritance ===\n\n";

// Create base templates (low priority)
$baseTemplates = new ArrayTemplateLoader([
    'alert' => <<<'PHP'
<div class="alert">
⚠️ <?= $md->escapeContent($message) ?>
</div>
PHP,
    'header' => <<<'PHP'
# <?= $md->escapeContent($title) ?>

---
PHP,
]);
$baseTemplates->setPriority(10);

// Create override templates (high priority)
$overrideTemplates = new ArrayTemplateLoader([
    'alert' => <<<'PHP'
> **<?= $md->escapeContent($level ?? 'Notice') ?>**: <?= $md->escapeContent($message) ?>
PHP,
]);
$overrideTemplates->setPriority(20);

// Create chain loader
$chainLoader = new ChainTemplateLoader([$baseTemplates, $overrideTemplates]);
$customRenderer = new TemplateRenderer($chainLoader, null, $template->getMarkdownEscape());

// Render using overridden template
$result = $customRenderer->render('alert', [
    'level' => 'Warning',
    'message' => 'This uses the **overridden** template with higher priority!',
]);

echo "Overridden Alert:\n" . $result . "\n\n";

// Render using base template (not overridden)
$result = $customRenderer->render('header', [
    'title' => 'This uses the **base** template',
]);

echo "Base Header:\n" . $result . "\n\n";

// Example 3: Dynamic Template Selection
echo "=== Example 3: Dynamic Template Selection ===\n\n";

// Add notification templates
$notificationTemplates = new ArrayTemplateLoader([
    'notification.success' => <<<'PHP'
### ✅ Success

<?= $md->escapeContent($message) ?>
PHP,
    'notification.error' => <<<'PHP'
### ❌ Error

<?= $md->escapeContent($message) ?>

<?php if (isset($details)): ?>
**Details**: <?= $md->escapeContent($details) ?>
<?php endif; ?>
PHP,
    'notification.info' => <<<'PHP'
### ℹ️ Information

<?= $md->escapeContent($message) ?>
PHP,
]);

$dynamicRenderer = new TemplateRenderer($notificationTemplates, null, $template->getMarkdownEscape());

// Function to render notification based on type
function renderNotification($renderer, $type, $message, $details = null) {
    $templateName = 'notification.' . $type;
    return $renderer->render($templateName, compact('message', 'details'));
}

// Render different notification types
$notifications = [
    ['type' => 'success', 'message' => 'Operation completed **successfully**!'],
    ['type' => 'error', 'message' => 'Failed to process [request]', 'details' => 'Invalid *parameters* provided'],
    ['type' => 'info', 'message' => 'New version `2.0.0` is available'],
];

foreach ($notifications as $notification) {
    echo renderNotification(
        $dynamicRenderer,
        $notification['type'],
        $notification['message'],
        $notification['details'] ?? null
    ) . "\n";
}

// Example 4: Error Handling
echo "\n=== Example 4: Error Handling ===\n\n";

try {
    // Try to render non-existent template
    $result = $customRenderer->render('non-existent-template', []);
} catch (\Markdown\Escape\Exception\TemplateNotFoundException $e) {
    echo "Error caught: " . $e->getMessage() . "\n";
    echo "Searched paths: " . implode(', ', $e->getSearchPaths()) . "\n\n";
}

// Handle template rendering errors
$errorTemplate = new ArrayTemplateLoader([
    'buggy' => '<?= $undefined_variable ?>',
]);

$errorRenderer = new TemplateRenderer($errorTemplate, null, $template->getMarkdownEscape());

try {
    $result = $errorRenderer->render('buggy', []);
} catch (\Markdown\Escape\Exception\TemplateRenderException $e) {
    echo "Render error caught: " . $e->getMessage() . "\n";
    echo "Template name: " . $e->getTemplateName() . "\n";
    echo "Context: " . json_encode($e->getContext()) . "\n\n";
}

// Example 5: Complex Data Structure Rendering
echo "=== Example 5: Complex Data Structure ===\n\n";

$complexTemplate = <<<'PHP'
# <?= $md->escapeContent($report['title']) ?>

Generated: <?= date('Y-m-d H:i:s') ?>

## Summary Statistics

| Metric | Value | Change |
| --- | --- | --- |
<?php foreach ($report['stats'] as $stat): ?>
| <?= $md->escapeContent($stat['name']) ?> | <?= $md->escapeContent($stat['value']) ?> | <?= $stat['change'] > 0 ? '📈' : '📉' ?> <?= $md->escapeContent(abs($stat['change']) . '%') ?> |
<?php endforeach; ?>

## Detailed Results

<?php foreach ($report['categories'] as $category): ?>
### <?= $md->escapeContent($category['name']) ?>

<?php if (!empty($category['items'])): ?>
<?php foreach ($category['items'] as $item): ?>
- **<?= $md->escapeContent($item['title']) ?>**: <?= $md->escapeContent($item['description']) ?>
  <?php if (!empty($item['tags'])): ?>
  - Tags: <?= implode(', ', array_map(function($tag) use ($md) {
      return '`' . $md->escapeInlineCode($tag) . '`';
  }, $item['tags'])) ?>
  <?php endif; ?>
<?php endforeach; ?>
<?php else: ?>
*No items in this category*
<?php endif; ?>

<?php endforeach; ?>
PHP;

$result = $renderer->renderString($complexTemplate, [
    'report' => [
        'title' => 'Monthly **Performance** Report',
        'stats' => [
            ['name' => 'Total Requests', 'value' => '1.2M', 'change' => 15.3],
            ['name' => 'Error Rate', 'value' => '0.02%', 'change' => -25.0],
            ['name' => 'Avg Response Time', 'value' => '145ms', 'change' => -8.7],
        ],
        'categories' => [
            [
                'name' => 'Optimizations [Completed]',
                'items' => [
                    [
                        'title' => 'Database Query Optimization',
                        'description' => 'Reduced query time by *50%* using indexes',
                        'tags' => ['performance', 'database', 'sql'],
                    ],
                    [
                        'title' => 'Caching Implementation',
                        'description' => 'Added Redis caching for **hot** data paths',
                        'tags' => ['cache', 'redis', 'performance'],
                    ],
                ],
            ],
            [
                'name' => 'Issues & Concerns',
                'items' => [
                    [
                        'title' => 'Memory Usage',
                        'description' => 'Peak memory usage increased during [batch] processing',
                        'tags' => ['memory', 'monitoring'],
                    ],
                ],
            ],
            [
                'name' => 'Planned Improvements',
                'items' => [],
            ],
        ],
    ],
]);

echo $result . "\n";