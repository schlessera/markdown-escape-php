<?php

declare(strict_types=1);

namespace Markdown\Escape\Tests\Integration;

use Markdown\Escape\MarkdownEscape;
use Markdown\Escape\MarkdownTemplate;
use Markdown\Escape\Template\Loader\ArrayTemplateLoader;
use Markdown\Escape\Template\Loader\ChainTemplateLoader;
use Markdown\Escape\Template\Loader\FileTemplateLoader;
use Markdown\Escape\Template\TemplateRenderer;
use Markdown\Escape\Tests\TestCase;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

class TemplateRenderingTest extends TestCase
{
    /**
     * @var vfsStreamDirectory
     */
    private $root;

    /**
     * @var string
     */
    private $rootPath;

    protected function setUp(): void
    {
        $this->root     = vfsStream::setup('templates');
        $this->rootPath = vfsStream::url('templates');
    }

    public function testCompleteMarkdownDocumentGeneration(): void
    {
        $template = MarkdownTemplate::gfm();

        $result = $template->renderString(<<<'PHP'
# <?= $md->escapeContent($project['name']) ?>

[![Build Status](<?= $md->escapeUrl($project['badge_url']) ?>)](<?= $md->escapeUrl($project['build_url']) ?>)

## Description

<?= $md->escapeContent($project['description']) ?>

## Installation

```bash
<?= $md->escapeWithinCodeBlock($project['install_command']) ?>

```

## Usage

<?php foreach ($examples as $example): ?>
### <?= $md->escapeContent($example['title']) ?>

<?= $md->escapeContent($example['description']) ?>

```<?= $example['language'] ?? 'php' ?>
<?= $md->escapeWithinCodeBlock($example['code']) ?>

```

<?php endforeach; ?>

## API Reference

<?php foreach ($methods as $method): ?>
### <?= $md->escapeInlineCode($method['signature']) ?>

<?= $md->escapeContent($method['description']) ?>

<?php if (!empty($method['parameters'])): ?>
**Parameters:**

| Name | Type | Description |
| --- | --- | --- |
<?php foreach ($method['parameters'] as $param): ?>
| <?= $md->escapeInlineCode($param['name']) ?> | <?= $md->escapeContent($param['type']) ?> | <?= $md->escapeContent($param['description']) ?> |
<?php endforeach; ?>
<?php endif; ?>

<?php if (isset($method['return'])): ?>
**Returns:** <?= $md->escapeContent($method['return']) ?>
<?php endif; ?>

<?php endforeach; ?>

## Contributing

Please see [CONTRIBUTING](<?= $md->escapeUrl($links['contributing']) ?>) for details.

## License

The MIT License (MIT). Please see [License File](<?= $md->escapeUrl($links['license']) ?>) for more information.
PHP
            , [
                'project' => [
                    'name'            => 'My **Awesome** Library',
                    'badge_url'       => 'https://img.shields.io/badge/build-passing-green?style=flat&logo=github',
                    'build_url'       => 'https://github.com/user/repo/actions',
                    'description'     => 'A library that does *amazing* things with [special] characters!',
                    'install_command' => 'composer require user/package:^1.0',
                ],
                'examples' => [
                    [
                        'title'       => 'Basic Usage',
                        'description' => 'Here\'s how to use the `escape()` method:',
                        'language'    => 'php',
                        'code'        => '<?php
$escaper = new Escaper();
$result = $escaper->escape("**text**");
echo $result; // Outputs: \*\*text\*\*',
                    ],
                    [
                        'title'       => 'Advanced Features',
                        'description' => 'Using context-aware escaping:',
                        'code'        => '$url = "http://example.com/path(with)parens";
$escaped = $escaper->escapeUrl($url);',
                    ],
                ],
                'methods' => [
                    [
                        'signature'   => 'escape(string $text): string',
                        'description' => 'Escapes markdown special characters in text.',
                        'parameters'  => [
                            ['name' => '$text', 'type' => 'string', 'description' => 'The text to escape'],
                        ],
                        'return' => 'The escaped text',
                    ],
                    [
                        'signature'   => 'escapeUrl(string $url): string',
                        'description' => 'Escapes URLs for use in markdown links.',
                        'parameters'  => [
                            ['name' => '$url', 'type' => 'string', 'description' => 'The URL to escape'],
                        ],
                        'return' => 'The escaped URL',
                    ],
                ],
                'links' => [
                    'contributing' => 'https://github.com/user/repo/blob/main/CONTRIBUTING.md',
                    'license'      => 'https://github.com/user/repo/blob/main/LICENSE.md',
                ],
            ]);

        // Verify the output contains properly escaped content
        $this->assertStringContainsString('# My \\*\\*Awesome\\*\\* Library', $result);
        $this->assertStringContainsString('does \\*amazing\\* things with \\[special\\] characters!', $result);
        $this->assertStringContainsString('```bash', $result);
        $this->assertStringContainsString('composer require user/package:^1.0', $result);
        $this->assertStringContainsString('| `$text` | string | The text to escape |', $result);
        $this->assertStringContainsString('### `escape(string $text): string`', $result);
        $this->assertStringContainsString('echo $result; // Outputs: \\*\\*text\\*\\*', $result);
    }

    public function testTemplateInheritanceWithChainLoader(): void
    {
        // Create base templates
        $baseTemplates = new ArrayTemplateLoader([
            'layout' => <<<'PHP'
# <?= $md->escapeContent($title) ?>

<?= $content ?>

---
*Generated by <?= $md->escapeContent($generator ?? 'System') ?>*
PHP
            ,
            'section' => <<<'PHP'
## <?= $md->escapeContent($section_title) ?>

<?= $md->escapeContent($section_content) ?>
PHP
            ,
        ]);

        // Create custom templates directory
        vfsStream::newFile('custom-section.php')
            ->at($this->root)
            ->setContent(
                <<<'PHP'
### <?= $md->escapeContent($section_title) ?> (Custom)

> <?= $md->escapeContent($section_content) ?>

*Modified: <?= date('Y-m-d') ?>*
PHP
            );

        $fileLoader = new FileTemplateLoader($this->rootPath);
        $fileLoader->setPriority(20); // Higher priority

        $chainLoader = new ChainTemplateLoader([$baseTemplates, $fileLoader]);

        $renderer = new TemplateRenderer($chainLoader, null, MarkdownEscape::gfm());

        // Render using base template
        $result1 = $renderer->render('section', [
            'section_title'   => 'Base Section',
            'section_content' => 'This uses the **base** template.',
        ]);

        $this->assertStringContainsString('## Base Section', $result1);
        $this->assertStringContainsString('This uses the \\*\\*base\\*\\* template.', $result1);

        // Render using custom template (overrides base)
        $result2 = $renderer->render('custom-section', [
            'section_title'   => 'Custom Section',
            'section_content' => 'This uses the *custom* template.',
        ]);

        $this->assertStringContainsString('### Custom Section (Custom)', $result2);
        $this->assertStringContainsString('> This uses the \\*custom\\* template.', $result2);
        $this->assertStringContainsString('*Modified: ' . date('Y-m-d') . '*', $result2);
    }

    public function testNestedTemplateIncludes(): void
    {
        // Create templates that include each other
        vfsStream::newFile('main.php')
            ->at($this->root)
            ->setContent(
                <<<'PHP'
# <?= $md->escapeContent($title) ?>

<?php foreach ($sections as $section): ?>
<?php 
    $context->set('current_section', $section);
    echo $context->getRenderer()->render('section', $section + $context->all());
?>

<?php endforeach; ?>

<?php echo $context->getRenderer()->render('footer', $context->all()); ?>
PHP
            );

        vfsStream::newFile('section.php')
            ->at($this->root)
            ->setContent(
                <<<'PHP'
## <?= $md->escapeContent($heading) ?>

<?= $md->escapeContent($content) ?>

<?php if (isset($items) && count($items) > 0): ?>
<?php echo $context->getRenderer()->render('list', ['items' => $items] + $context->all()); ?>
<?php endif; ?>
PHP
            );

        vfsStream::newFile('list.php')
            ->at($this->root)
            ->setContent(
                <<<'PHP'
<?php foreach ($items as $item): ?>
- <?= $md->escapeContent($item) ?>

<?php endforeach; ?>
PHP
            );

        vfsStream::newFile('footer.php')
            ->at($this->root)
            ->setContent(
                <<<'PHP'
---
*Document generated on <?= date('Y-m-d H:i:s') ?>*
PHP
            );

        $template = new MarkdownTemplate();
        $template->addPath($this->rootPath);

        $result = $template->render('main', [
            'title'    => 'Nested **Templates** Demo',
            'sections' => [
                [
                    'heading' => 'First Section',
                    'content' => 'This section has *no* items.',
                ],
                [
                    'heading' => 'Second Section',
                    'content' => 'This section has [some] items:',
                    'items'   => ['Item **1**', 'Item *2*', 'Item `3`'],
                ],
            ],
        ]);

        $this->assertStringContainsString('# Nested \\*\\*Templates\\*\\* Demo', $result);
        $this->assertStringContainsString('## First Section', $result);
        $this->assertStringContainsString('This section has \\*no\\* items.', $result);
        $this->assertStringContainsString('## Second Section', $result);
        $this->assertStringContainsString('This section has \\[some\\] items:', $result);
        $this->assertStringContainsString('- Item \\*\\*1\\*\\*', $result);
        $this->assertStringContainsString('- Item \\*2\\*', $result);
        $this->assertStringContainsString('- Item \\`3\\`', $result);
        $this->assertStringContainsString('*Document generated on', $result);
    }

    public function testCustomEscaperIntegration(): void
    {
        // Create a custom escaper for special formatting
        $customEscaper = new class (new \Markdown\Escape\Context\GeneralContentContext(), new \Markdown\Escape\Dialect\CommonMarkDialect()) extends \Markdown\Escape\Escaper\AbstractEscaper {
            public function escape(string $text): string
            {
                // Custom logic: wrap in brackets
                return '[' . str_replace(['[', ']'], ['\\[', '\\]'], $text) . ']';
            }

            public function supportsDialect(\Markdown\Escape\Contract\DialectInterface $dialect): bool
            {
                return true;
            }
        };

        $template = <<<'PHP'
Normal: <?= $md->escapeContent($normal) . "\n" ?>
Custom: <?= $escapers->special($special) . "\n" ?>
Combined: <?= $md->escapeContent($prefix) ?> <?= $escapers->special($special) ?>
PHP;

        $renderer = new TemplateRenderer();
        $renderer->setMarkdownEscape(new MarkdownEscape());
        $renderer->addEscaper('special', $customEscaper);

        $result = $renderer->renderString($template, [
            'normal'  => 'Normal **text**',
            'special' => 'Special [text]',
            'prefix'  => 'Prefix:',
        ]);

        $this->assertSame(<<<'MD'
Normal: Normal \*\*text\*\*
Custom: [Special \[text\]]
Combined: Prefix: [Special \[text\]]
MD
            , $result);
    }

    public function testErrorHandlingInTemplates(): void
    {
        $template = <<<'PHP'
<?php if ($should_fail): ?>
<?php throw new \RuntimeException('Template error'); ?>
<?php endif; ?>
Content: <?= $md->escapeContent($content) ?>
PHP;

        $renderer = new TemplateRenderer();
        $renderer->setMarkdownEscape(new MarkdownEscape());

        // Should work when not failing
        $result = $renderer->renderString($template, [
            'should_fail' => false,
            'content'     => 'Success',
        ]);

        $this->assertSame('Content: Success', trim($result));

        // Should throw wrapped exception when failing
        $this->expectException(\Markdown\Escape\Exception\TemplateRenderException::class);
        $this->expectExceptionMessage('Template error');

        $renderer->renderString($template, [
            'should_fail' => true,
            'content'     => 'Failed',
        ]);
    }

    public function testPerformanceWithLargeDataSets(): void
    {
        $template = new MarkdownTemplate();

        // Generate large dataset
        $rows = [];
        for ($i = 1; $i <= 100; $i++) {
            $rows[] = [
                "Item **$i**",
                'Description with *special* characters [test]',
                'Status: `active`',
                "URL: http://example.com/item($i)",
            ];
        }

        $start = microtime(true);

        $result = $template->render('table', [
            'headers' => ['Name', 'Description', 'Status', 'URL'],
            'rows'    => $rows,
        ]);

        $duration = microtime(true) - $start;

        // Should complete in reasonable time
        $this->assertLessThan(1.0, $duration, 'Template rendering took too long');

        // Verify output
        $this->assertStringContainsString('| Name | Description | Status | URL |', $result);
        $this->assertStringContainsString('| Item \\*\\*1\\*\\* |', $result);
        $this->assertStringContainsString('| Item \\*\\*100\\*\\* |', $result);
        $this->assertStringContainsString('Description with \\*special\\* characters \\[test\\]', $result);
        $this->assertStringContainsString('URL: http://example.com/item\\(50\\)', $result);
    }
}
