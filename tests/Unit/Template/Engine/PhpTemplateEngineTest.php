<?php

declare(strict_types=1);

namespace Markdown\Escape\Tests\Unit\Template\Engine;

use Markdown\Escape\Exception\TemplateRenderException;
use Markdown\Escape\MarkdownEscape;
use Markdown\Escape\Template\Engine\PhpTemplateEngine;
use Markdown\Escape\Template\Template;
use Markdown\Escape\Template\TemplateContext;
use Markdown\Escape\Tests\TestCase;

class PhpTemplateEngineTest extends TestCase
{
    /**
     * @var PhpTemplateEngine
     */
    private $engine;

    protected function setUp(): void
    {
        $this->engine = new PhpTemplateEngine();
    }

    public function testGetName(): void
    {
        $this->assertSame('php', $this->engine->getName());
    }

    public function testRenderSimpleTemplate(): void
    {
        $template = new Template('test', 'Hello, <?= $name ?>!');
        $context  = new TemplateContext(['name' => 'World']);

        $result = $this->engine->render($template, $context);

        $this->assertSame('Hello, World!', $result);
    }

    public function testRenderWithPhpShortTags(): void
    {
        $template = new Template('test', '<?php if ($show): ?>Hello<?php endif; ?>');
        $context  = new TemplateContext(['show' => true]);

        $result = $this->engine->render($template, $context);

        $this->assertSame('Hello', $result);
    }

    public function testRenderWithLoop(): void
    {
        $template = new Template('test', '<?php foreach ($items as $item): ?><?= $item ?>,<?php endforeach; ?>');
        $context  = new TemplateContext(['items' => ['a', 'b', 'c']]);

        $result = $this->engine->render($template, $context);

        $this->assertSame('a,b,c,', $result);
    }

    public function testRenderWithMarkdownEscape(): void
    {
        $template = new Template('test', '<?= $md->escapeContent($text) ?>');
        $context  = new TemplateContext(['text' => '**bold**']);
        $context->setMarkdownEscape(new MarkdownEscape());

        $result = $this->engine->render($template, $context);

        $this->assertSame('\*\*bold\*\*', $result);
    }

    public function testRenderWithMarkdownAlias(): void
    {
        $template = new Template('test', '<?= $markdown->escapeContent($text) ?>');
        $context  = new TemplateContext(['text' => '**bold**']);
        $context->setMarkdownEscape(new MarkdownEscape());

        $result = $this->engine->render($template, $context);

        $this->assertSame('\*\*bold\*\*', $result);
    }

    public function testRenderWithEscapers(): void
    {
        $template = new Template('test', '<?= $escapers->content($text) ?>');
        $context  = new TemplateContext(['text' => '**bold**']);
        $context->setMarkdownEscape(new MarkdownEscape());

        $result = $this->engine->render($template, $context);

        $this->assertSame('\*\*bold\*\*', $result);
    }

    public function testRenderWithEscapersProperty(): void
    {
        $template = new Template('test', '<?= $escapers->url->escape($link) ?>');
        $context  = new TemplateContext(['link' => 'http://example.com/test(1)']);
        $context->setMarkdownEscape(new MarkdownEscape());

        $result = $this->engine->render($template, $context);

        $this->assertSame('http://example.com/test%281%29', $result);
    }

    public function testRenderWithContextVariable(): void
    {
        $template = new Template('test', '<?= $context->get("value", "default") ?>');
        $context  = new TemplateContext(['value' => 'actual']);

        $result = $this->engine->render($template, $context);

        $this->assertSame('actual', $result);
    }

    public function testRenderComplexTemplate(): void
    {
        $templateContent = <<<'TEMPLATE'
<?php if (isset($title)): ?>
# <?= $md->escapeContent($title) . "\n\n" ?>
<?php endif; ?>
<?php foreach ($items as $item): ?>
- <?= $md->escapeContent($item) . "\n" ?>
<?php endforeach; ?>
TEMPLATE;

        $template = new Template('test', trim($templateContent));

        $context = new TemplateContext([
            'title' => 'My List',
            'items' => ['Item *1*', 'Item **2**', 'Item [3]'],
        ]);
        $context->setMarkdownEscape(new MarkdownEscape());

        $result = $this->engine->render($template, $context);

        $expected = "# My List\n\n"
                  . "- Item \\*1\\*\n"
                  . "- Item \\*\\*2\\*\\*\n"
                  . "- Item \\[3\\]\n";

        $this->assertSame($expected, $result);
    }

    public function testRenderWithExtractedVariables(): void
    {
        $template = new Template('test', '<?= $a ?>,<?= $b ?>,<?= $c ?>');
        $context  = new TemplateContext(['a' => 1, 'b' => 2, 'c' => 3]);

        $result = $this->engine->render($template, $context);

        $this->assertSame('1,2,3', $result);
    }

    public function testRenderThrowsExceptionOnError(): void
    {
        $template = new Template('test', '<?= $undefined ?>');
        $context  = new TemplateContext();

        $this->expectException(TemplateRenderException::class);
        $this->expectExceptionMessage('Template rendering failed: Undefined variable');

        $this->engine->render($template, $context);
    }

    public function testRenderThrowsExceptionOnSyntaxError(): void
    {
        $template = new Template('test', '<?php invalid syntax ?>');
        $context  = new TemplateContext();

        $this->expectException(TemplateRenderException::class);

        $this->engine->render($template, $context);
    }

    public function testSupportsPhpTemplates(): void
    {
        $phpTemplate   = new Template('test.php', 'content');
        $phtmlTemplate = new Template('test.phtml', 'content');
        $otherTemplate = new Template('test.twig', 'content');

        $this->assertTrue($this->engine->supports($phpTemplate));
        $this->assertTrue($this->engine->supports($phtmlTemplate));
        $this->assertFalse($this->engine->supports($otherTemplate));
    }

    public function testSupportsTemplateWithEngineMetadata(): void
    {
        $template1 = new Template('test', 'content', ['engine' => 'php']);
        $template2 = new Template('test', 'content', ['engine' => 'twig']);
        $template3 = new Template('test', 'content');

        $this->assertTrue($this->engine->supports($template1));
        $this->assertFalse($this->engine->supports($template2));
        $this->assertFalse($this->engine->supports($template3));
    }

    public function testConfigure(): void
    {
        $options = [
            'strict_variables' => true,
            'auto_escape'      => true,
        ];

        $this->engine->configure($options);

        $expected = [
            'strict_variables' => true,
            'auto_escape'      => true,
            'short_tags'       => true,
        ];
        $this->assertEquals($expected, $this->engine->getOptions());
    }

    public function testEmptyTemplateReturnsEmptyString(): void
    {
        $template = new Template('test', '');
        $context  = new TemplateContext();

        $result = $this->engine->render($template, $context);

        $this->assertSame('', $result);
    }

    public function testOutputBufferingCleanupOnError(): void
    {
        $template = new Template('test', '<?php ob_start(); throw new Exception("test"); ?>');
        $context  = new TemplateContext();

        $initialLevel = ob_get_level();

        try {
            $this->engine->render($template, $context);
        } catch (TemplateRenderException $e) {
            // Expected
        }

        $this->assertSame($initialLevel, ob_get_level());
    }
}
