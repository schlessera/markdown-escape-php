<?php

declare(strict_types=1);

namespace Markdown\Escape\Tests\Unit;

use Markdown\Escape\Context\GeneralContentContext;
use Markdown\Escape\Dialect\CommonMarkDialect;
use Markdown\Escape\Dialect\GitHubFlavoredMarkdownDialect;
use Markdown\Escape\MarkdownEscape;
use Markdown\Escape\Tests\TestCase;

class MarkdownEscapeTest extends TestCase
{
    public function testCommonMarkFactoryMethod(): void
    {
        $escape = MarkdownEscape::commonMark();

        $this->assertInstanceOf(CommonMarkDialect::class, $escape->getDialect());
    }

    public function testGfmFactoryMethod(): void
    {
        $escape = MarkdownEscape::gfm();

        $this->assertInstanceOf(GitHubFlavoredMarkdownDialect::class, $escape->getDialect());
    }

    public function testEscapeContent(): void
    {
        $escape  = new MarkdownEscape();
        $content = 'This is *bold* and _italic_ text with [link](url)';

        $escaped = $escape->escapeContent($content);

        $this->assertStringContainsString('\\*bold\\*', $escaped);
        $this->assertStringContainsString('\\_italic\\_', $escaped);
        $this->assertStringContainsString('\\[link\\]', $escaped);
        $this->assertStringContainsString('\\(url\\)', $escaped);
    }

    public function testEscapeUrl(): void
    {
        $escape = new MarkdownEscape();
        $url    = 'https://example.com/path with spaces/(parentheses)';

        $escaped = $escape->escapeUrl($url);

        $this->assertStringContainsString('path%20with%20spaces', $escaped);
        $this->assertStringContainsString('%28parentheses%29', $escaped);
    }

    public function testEscapeInlineCode(): void
    {
        $escape = new MarkdownEscape();
        $code   = 'const code = `template literal`';

        $escaped = $escape->escapeInlineCode($code);

        $this->assertStringStartsWith('``', $escaped);
        $this->assertStringEndsWith('``', $escaped);
        $this->assertStringContainsString($code, $escaped);
    }

    public function testEscapeCodeBlock(): void
    {
        $escape = new MarkdownEscape();
        $code   = "function test() {\n    return true;\n}";

        $escaped = $escape->escapeCodeBlock($code, ['use_fences' => true, 'language' => 'javascript']);

        $this->assertStringStartsWith('```javascript', $escaped);
        $this->assertStringEndsWith('```', $escaped);
        $this->assertStringContainsString($code, $escaped);
    }

    public function testWithDialect(): void
    {
        $escape = new MarkdownEscape();
        $gfm    = new GitHubFlavoredMarkdownDialect();

        $newEscape = $escape->withDialect($gfm);

        $this->assertNotSame($escape, $newEscape);
        $this->assertSame($gfm, $newEscape->getDialect());
    }

    public function testEscapeWithCustomContext(): void
    {
        $escape  = new MarkdownEscape();
        $context = new GeneralContentContext();
        $content = '# Heading';

        $escaped = $escape->escape($content, $context);

        $this->assertStringContainsString('\\#', $escaped);
    }

    public function testGetFactory(): void
    {
        $escape   = new MarkdownEscape();
        $factory1 = $escape->getFactory();
        $factory2 = $escape->getFactory();

        $this->assertInstanceOf(\Markdown\Escape\EscaperFactory::class, $factory1);
        $this->assertSame($factory1, $factory2);
    }
}
