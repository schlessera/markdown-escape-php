<?php

declare(strict_types=1);

namespace Markdown\Escape\Tests\Unit\Template;

use Markdown\Escape\Contract\EscaperInterface;
use Markdown\Escape\Contract\TemplateEngineInterface;
use Markdown\Escape\Contract\TemplateLoaderInterface;
use Markdown\Escape\Exception\TemplateNotFoundException;
use Markdown\Escape\Exception\TemplateRenderException;
use Markdown\Escape\MarkdownEscape;
use Markdown\Escape\Template\Engine\PhpTemplateEngine;
use Markdown\Escape\Template\Loader\ArrayTemplateLoader;
use Markdown\Escape\Template\TemplateRenderer;
use Markdown\Escape\Tests\TestCase;

class TemplateRendererTest extends TestCase
{
    public function testConstructorDefaults(): void
    {
        $renderer = new TemplateRenderer();

        $this->assertInstanceOf(TemplateLoaderInterface::class, $renderer->getLoader());
        $this->assertInstanceOf(TemplateEngineInterface::class, $renderer->getEngine());
    }

    public function testConstructorWithDependencies(): void
    {
        $loader   = new ArrayTemplateLoader();
        $engine   = new PhpTemplateEngine();
        $markdown = new MarkdownEscape();

        $renderer = new TemplateRenderer($loader, $engine, $markdown);

        $this->assertSame($loader, $renderer->getLoader());
        $this->assertSame($engine, $renderer->getEngine());
    }

    public function testRender(): void
    {
        $loader   = new ArrayTemplateLoader(['greeting' => 'Hello, <?= $name ?>!']);
        $renderer = new TemplateRenderer($loader);

        $result = $renderer->render('greeting', ['name' => 'World']);

        $this->assertSame('Hello, World!', $result);
    }

    public function testRenderWithMarkdownEscape(): void
    {
        $loader   = new ArrayTemplateLoader(['test' => '<?= $md->escapeContent($text) ?>']);
        $markdown = new MarkdownEscape();

        $renderer = new TemplateRenderer($loader, null, $markdown);

        $result = $renderer->render('test', ['text' => '**bold**']);

        $this->assertSame('\*\*bold\*\*', $result);
    }

    public function testRenderString(): void
    {
        $renderer = new TemplateRenderer();

        $result = $renderer->renderString('Hello, <?= $name ?>!', ['name' => 'World']);

        $this->assertSame('Hello, World!', $result);
    }

    public function testRenderStringWithMarkdown(): void
    {
        $renderer = new TemplateRenderer(null, null, new MarkdownEscape());

        $result = $renderer->renderString(
            '<?= $md->escapeContent($text) ?>',
            ['text' => '**bold**']
        );

        $this->assertSame('\*\*bold\*\*', $result);
    }

    public function testRenderThrowsTemplateNotFoundException(): void
    {
        $loader   = new ArrayTemplateLoader();
        $renderer = new TemplateRenderer($loader);

        $this->expectException(TemplateNotFoundException::class);

        $renderer->render('nonexistent');
    }

    public function testRenderThrowsTemplateRenderException(): void
    {
        $loader   = new ArrayTemplateLoader(['error' => '<?= $undefined ?>']);
        $renderer = new TemplateRenderer($loader);

        $this->expectException(TemplateRenderException::class);
        $this->expectExceptionMessage('Failed to render template "error"');

        $renderer->render('error');
    }

    public function testRenderStringThrowsException(): void
    {
        $renderer = new TemplateRenderer();

        $this->expectException(TemplateRenderException::class);
        $this->expectExceptionMessage('Failed to render template "string"');

        $renderer->renderString('<?= $undefined ?>');
    }

    public function testSetLoader(): void
    {
        $renderer = new TemplateRenderer();
        $loader   = new ArrayTemplateLoader();

        $result = $renderer->setLoader($loader);

        $this->assertSame($renderer, $result); // Fluent interface
        $this->assertSame($loader, $renderer->getLoader());
    }

    public function testSetEngine(): void
    {
        $renderer = new TemplateRenderer();
        $engine   = new PhpTemplateEngine();

        $result = $renderer->setEngine($engine);

        $this->assertSame($renderer, $result); // Fluent interface
        $this->assertSame($engine, $renderer->getEngine());
    }

    public function testSetMarkdownEscape(): void
    {
        $renderer = new TemplateRenderer();
        $markdown = new MarkdownEscape();

        $result = $renderer->setMarkdownEscape($markdown);

        $this->assertSame($renderer, $result); // Fluent interface
    }

    public function testAddEscaper(): void
    {
        $loader   = new ArrayTemplateLoader(['test' => '<?= $escapers->custom($text) ?>']);
        $renderer = new TemplateRenderer($loader);

        $escaper = $this->createMock(EscaperInterface::class);
        $escaper->expects($this->once())
            ->method('escape')
            ->with('input')
            ->willReturn('escaped');

        $renderer->addEscaper('custom', $escaper);

        $result = $renderer->render('test', ['text' => 'input']);

        $this->assertSame('escaped', $result);
    }

    public function testConfigure(): void
    {
        $renderer = new TemplateRenderer();

        $options = [
            'auto_reload'      => false,
            'strict_variables' => true,
        ];

        $result = $renderer->configure($options);

        $this->assertSame($renderer, $result); // Fluent interface
        $this->assertSame(
            array_merge(['auto_reload' => false, 'strict_variables' => true], $options),
            $renderer->getOptions()
        );
    }

    public function testConfigureEngine(): void
    {
        $engine = $this->createMock(TemplateEngineInterface::class);
        $engine->expects($this->once())
            ->method('configure')
            ->with(['strict' => true]);

        $renderer = new TemplateRenderer(null, $engine);

        $renderer->configure([
            'engine' => ['strict' => true],
        ]);
    }

    public function testComplexTemplate(): void
    {
        $template = <<<'PHP'
# <?= $md->escapeContent($title) . "\n\n" ?>
<?php foreach ($sections as $section): ?>
## <?= $md->escapeContent($section['title']) . "\n\n" ?>
<?= $md->escapeContent($section['content']) . "\n\n" ?>
<?php endforeach; ?>
PHP;

        $loader   = new ArrayTemplateLoader(['document' => $template]);
        $renderer = new TemplateRenderer($loader, null, new MarkdownEscape());

        $result = $renderer->render('document', [
            'title'    => 'My *Document*',
            'sections' => [
                ['title' => 'Section **1**', 'content' => 'Content with [links]'],
                ['title' => 'Section 2', 'content' => 'More content'],
            ],
        ]);

        $expected = <<<'MD'
# My \*Document\*

## Section \*\*1\*\*

Content with \[links\]

## Section 2

More content


MD;

        $this->assertSame($expected, $result);
    }

    public function testMultipleEscapers(): void
    {
        $template = <<<'PHP'
Content: <?= $escapers->content($text) . "\n" ?>
URL: <?= $escapers->url($link) . "\n" ?>
Code: <?= $escapers->inlineCode($code) ?>
PHP;

        $loader   = new ArrayTemplateLoader(['test' => $template]);
        $renderer = new TemplateRenderer($loader, null, new MarkdownEscape());

        $result = $renderer->render('test', [
            'text' => '**bold**',
            'link' => 'http://example.com/test(1)',
            'code' => 'code with `backticks`',
        ]);

        $expected = <<<'MD'
Content: \*\*bold\*\*
URL: http://example.com/test%281%29
Code: `` code with `backticks` ``
MD;

        $this->assertSame($expected, $result);
    }
}
