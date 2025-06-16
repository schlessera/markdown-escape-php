<?php

declare(strict_types=1);

namespace Markdown\Escape\Tests\Unit;

use Markdown\Escape\Contract\DialectInterface;
use Markdown\Escape\Contract\TemplateRendererInterface;
use Markdown\Escape\Dialect\CommonMarkDialect;
use Markdown\Escape\Dialect\GitHubFlavoredMarkdownDialect;
use Markdown\Escape\MarkdownEscape;
use Markdown\Escape\MarkdownTemplate;
use Markdown\Escape\Tests\TestCase;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

class MarkdownTemplateTest extends TestCase
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

    public function testConstructor(): void
    {
        $template = new MarkdownTemplate();

        $this->assertInstanceOf(MarkdownEscape::class, $template->getMarkdownEscape());
        $this->assertInstanceOf(TemplateRendererInterface::class, $template->getRenderer());
    }

    public function testConstructorWithDialect(): void
    {
        $dialect  = new CommonMarkDialect();
        $template = new MarkdownTemplate($dialect);

        $this->assertSame($dialect, $template->getMarkdownEscape()->getDialect());
    }

    public function testCommonMarkFactory(): void
    {
        $template = MarkdownTemplate::commonMark();

        $this->assertInstanceOf(MarkdownTemplate::class, $template);
        $this->assertInstanceOf(CommonMarkDialect::class, $template->getMarkdownEscape()->getDialect());
    }

    public function testGfmFactory(): void
    {
        $template = MarkdownTemplate::gfm();

        $this->assertInstanceOf(MarkdownTemplate::class, $template);
        $this->assertInstanceOf(GitHubFlavoredMarkdownDialect::class, $template->getMarkdownEscape()->getDialect());
    }

    public function testWithDialectFactory(): void
    {
        $dialect  = $this->createMock(DialectInterface::class);
        $template = MarkdownTemplate::withDialect($dialect);

        $this->assertInstanceOf(MarkdownTemplate::class, $template);
        $this->assertSame($dialect, $template->getMarkdownEscape()->getDialect());
    }

    public function testRenderString(): void
    {
        $template = new MarkdownTemplate();

        $result = $template->renderString('Hello, <?= $name ?>!', ['name' => 'World']);

        $this->assertSame('Hello, World!', $result);
    }

    public function testRenderDefaultTemplate(): void
    {
        $template = new MarkdownTemplate();

        $result = $template->render('document', [
            'title'       => 'Test Document',
            'description' => 'A test document',
            'sections'    => [
                ['title' => 'Section 1', 'content' => 'Content 1'],
                ['title' => 'Section 2', 'content' => 'Content 2'],
            ],
        ]);

        $expected = <<<'MD'
# Test Document

A test document

## Section 1

Content 1

## Section 2

Content 2


MD;

        $this->assertSame($expected, $result);
    }

    public function testRenderTableTemplate(): void
    {
        $template = new MarkdownTemplate();

        $result = $template->render('table', [
            'headers' => ['Name', 'Age', 'City'],
            'rows'    => [
                ['John', '25', 'New York'],
                ['Jane', '30', 'London'],
            ],
        ]);

        $expected = <<<'MD'
| Name | Age | City |
| --- | --- | --- |
| John | 25 | New York |
| Jane | 30 | London |

MD;

        $this->assertSame($expected, $result);
    }

    public function testRenderListTemplate(): void
    {
        $template = new MarkdownTemplate();

        $result = $template->render('list', [
            'items' => [
                'Simple item',
                ['text' => 'Item with sub-items', 'subItems' => ['Sub 1', 'Sub 2']],
                'Another simple item',
            ],
        ]);

        $expected = <<<'MD'
- Simple item
- Item with sub-items
  - Sub 1
  - Sub 2
- Another simple item

MD;

        $this->assertSame($expected, $result);
    }

    public function testRenderCodeExampleTemplate(): void
    {
        $template = new MarkdownTemplate();

        $result = $template->render('code-example', [
            'title'       => 'Example Code',
            'description' => 'This is an example',
            'language'    => 'php',
            'code'        => '<?php echo "Hello"; ?>',
            'output'      => 'Hello',
        ]);

        $expected = <<<'MD'
### Example Code

This is an example

```php
<?php echo "Hello"; ?>
```

Output:
```
Hello
```

MD;

        $this->assertSame($expected, $result);
    }

    public function testRenderLinkListTemplate(): void
    {
        $template = new MarkdownTemplate();

        $result = $template->render('link-list', [
            'links' => [
                ['text' => 'Google', 'url' => 'https://google.com'],
                ['text' => 'GitHub', 'url' => 'https://github.com', 'description' => 'Code hosting'],
            ],
        ]);

        $expected = <<<'MD'
- [Google](https://google.com)
- [GitHub](https://github.com) - Code hosting

MD;

        $this->assertSame($expected, $result);
    }

    public function testRenderApiMethodTemplate(): void
    {
        $template = new MarkdownTemplate();

        $result = $template->render('api-method', [
            'method'      => 'POST',
            'endpoint'    => '/api/users',
            'description' => 'Create a new user',
            'parameters'  => [
                ['name' => 'name', 'type' => 'string', 'required' => true, 'description' => 'User name'],
                ['name' => 'email', 'type' => 'string', 'required' => true, 'description' => 'User email'],
            ],
            'example' => [
                'language' => 'json',
                'code'     => '{"name": "John", "email": "john@example.com"}',
            ],
        ]);

        $this->assertStringContainsString('### POST `/api/users`', $result);
        $this->assertStringContainsString('Create a new user', $result);
        $this->assertStringContainsString('| `name` | string | Yes | User name |', $result);
        $this->assertStringContainsString('```json', $result);
    }

    public function testAddPath(): void
    {
        vfsStream::newFile('custom.php')
            ->at($this->root)
            ->setContent('Custom: <?= $text ?>');

        $template = new MarkdownTemplate();
        $template->addPath($this->rootPath);

        $result = $template->render('custom', ['text' => 'Hello']);

        $this->assertSame('Custom: Hello', $result);
    }

    public function testAddPathWithNamespace(): void
    {
        $namespaceDir = vfsStream::newDirectory('namespace');
        $this->root->addChild($namespaceDir);
        vfsStream::newFile('template.php')
            ->at($namespaceDir)
            ->setContent('Namespaced: <?= $text ?>');

        $template = new MarkdownTemplate();
        $template->addPath($this->rootPath . '/namespace', 'custom');

        $result = $template->render('custom/template', ['text' => 'Content']);

        $this->assertSame('Namespaced: Content', $result);
    }

    public function testAddDefaultTemplates(): void
    {
        $template = new MarkdownTemplate();

        $template->addDefaultTemplates([
            'greeting' => 'Hello, <?= $name ?>!',
        ]);

        $result = $template->render('greeting', ['name' => 'World']);

        $this->assertSame('Hello, World!', $result);
    }

    public function testConfigure(): void
    {
        $template = new MarkdownTemplate();

        $result = $template->configure(['auto_reload' => false]);

        $this->assertSame($template, $result); // Fluent interface
    }

    public function testEscapingInDefaultTemplates(): void
    {
        $template = new MarkdownTemplate();

        $result = $template->render('document', [
            'title'    => 'Title with **markdown**',
            'sections' => [
                ['title' => 'Section *italic*', 'content' => 'Content with [brackets]'],
            ],
        ]);

        $this->assertStringContainsString('Title with \\*\\*markdown\\*\\*', $result);
        $this->assertStringContainsString('Section \\*italic\\*', $result);
        $this->assertStringContainsString('Content with \\[brackets\\]', $result);
    }

    public function testComplexScenario(): void
    {
        // Create custom template file
        vfsStream::newFile('report.php')
            ->at($this->root)
            ->setContent(
                <<<'PHP'
# <?= $md->escapeContent($title) ?>

Generated on: <?= date('Y-m-d') ?>

## Summary

<?= $md->escapeContent($summary) ?>

## Data

<?php include 'table.php'; ?>

## Links

<?php include 'link-list.php'; ?>
PHP
            );

        // Create table template
        vfsStream::newFile('table.php')
            ->at($this->root)
            ->setContent(
                <<<'PHP'
| <?= implode(' | ', array_map([$md, 'escapeContent'], $headers)) ?> |
| <?= str_repeat('--- | ', count($headers)) ?>
<?php foreach ($rows as $row): ?>
| <?= implode(' | ', array_map([$md, 'escapeContent'], $row)) ?> |
<?php endforeach; ?>
PHP
            );

        // Create link-list template
        vfsStream::newFile('link-list.php')
            ->at($this->root)
            ->setContent(
                <<<'PHP'
<?php foreach ($links as $link): ?>
- [<?= $md->escapeContent($link['text']) ?>](<?= $md->escapeUrl($link['url']) ?>)
<?php endforeach; ?>
PHP
            );

        $template = new MarkdownTemplate();
        $template->addPath($this->rootPath);

        $result = $template->render('report', [
            'title'   => 'Report **2024**',
            'summary' => 'This report contains *important* data',
            'headers' => ['Metric', 'Value'],
            'rows'    => [['Users', '1000'], ['Revenue', '$50,000']],
            'links'   => [
                ['text' => 'Full Report', 'url' => 'https://example.com/report'],
            ],
        ]);

        $this->assertStringContainsString('# Report \\*\\*2024\\*\\*', $result);
        $this->assertStringContainsString('Generated on: ' . date('Y-m-d'), $result);
        $this->assertStringContainsString('This report contains \\*important\\* data', $result);
        $this->assertStringContainsString('| Metric | Value |', $result);
        $this->assertStringContainsString('[Full Report](https://example.com/report)', $result);
    }
}
