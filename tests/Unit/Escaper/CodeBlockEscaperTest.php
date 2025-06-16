<?php

declare(strict_types=1);

namespace Markdown\Escape\Tests\Unit\Escaper;

use Markdown\Escape\Context\CodeBlockContext;
use Markdown\Escape\Dialect\CommonMarkDialect;
use Markdown\Escape\Escaper\CodeBlockEscaper;
use Markdown\Escape\Tests\TestCase;

class CodeBlockEscaperTest extends TestCase
{
    /**
     * @var CodeBlockEscaper
     */
    private $escaper;

    protected function setUp(): void
    {
        $context       = new CodeBlockContext();
        $dialect       = new CommonMarkDialect();
        $this->escaper = new CodeBlockEscaper($context, $dialect);
    }

    public function testEscapeIndentedCodeBlock(): void
    {
        $code    = "function test() {\n    return true;\n}";
        $escaped = $this->escaper->escape($code);

        $expected = "    function test() {\n        return true;\n    }";
        $this->assertEquals($expected, $escaped);
    }

    public function testEscapeFencedCodeBlock(): void
    {
        $context = new CodeBlockContext(['use_fences' => true]);
        $dialect = new CommonMarkDialect();
        $escaper = new CodeBlockEscaper($context, $dialect);

        $code    = "function test() {\n    return true;\n}";
        $escaped = $escaper->escape($code);

        $this->assertStringStartsWith('```', $escaped);
        $this->assertStringEndsWith('```', $escaped);
        $this->assertStringContainsString($code, $escaped);
    }

    public function testEscapeFencedCodeBlockWithLanguage(): void
    {
        $context = new CodeBlockContext(['use_fences' => true, 'language' => 'javascript']);
        $dialect = new CommonMarkDialect();
        $escaper = new CodeBlockEscaper($context, $dialect);

        $code    = "function test() {\n    return true;\n}";
        $escaped = $escaper->escape($code);

        $this->assertStringStartsWith('```javascript', $escaped);
    }

    public function testEscapeCodeBlockWithBackticks(): void
    {
        $context = new CodeBlockContext(['use_fences' => true]);
        $dialect = new CommonMarkDialect();
        $escaper = new CodeBlockEscaper($context, $dialect);

        $code    = "const str = ```\nmultiline\nstring\n```";
        $escaped = $escaper->escape($code);

        // Since the content has 3 consecutive backticks and 0 tildes,
        // the escaper will use tildes (because 3 > 0)
        $this->assertStringStartsWith('~~~', $escaped);
        $this->assertStringEndsWith('~~~', $escaped);
    }

    public function testEscapeCodeBlockWithTildes(): void
    {
        $context = new CodeBlockContext(['use_fences' => true]);
        $dialect = new CommonMarkDialect();
        $escaper = new CodeBlockEscaper($context, $dialect);

        $code    = 'code with ``` backticks and ~~~ tildes';
        $escaped = $escaper->escape($code);

        // Since the content has 3 consecutive backticks and 3 consecutive tildes,
        // the escaper will use backticks (because 3 <= 3) and need 4 of them
        $this->assertStringStartsWith('````', $escaped);
        $this->assertStringEndsWith('````', $escaped);
    }

    public function testEscapeEmptyCodeBlock(): void
    {
        $code    = '';
        $escaped = $this->escaper->escape($code);

        $this->assertEquals('', $escaped);
    }

    public function testEscapeCodeBlockWithoutTrailingNewline(): void
    {
        $context = new CodeBlockContext(['use_fences' => true]);
        $dialect = new CommonMarkDialect();
        $escaper = new CodeBlockEscaper($context, $dialect);

        $code    = 'no newline at end';
        $escaped = $escaper->escape($code);

        $this->assertStringContainsString("no newline at end\n```", $escaped);
    }

    public function testEscapeWithRawOption(): void
    {
        $context = new CodeBlockContext(['raw' => true]);
        $dialect = new CommonMarkDialect();
        $escaper = new CodeBlockEscaper($context, $dialect);

        $code    = "function test() {\n    return true;\n}";
        $escaped = $escaper->escape($code);

        // Raw mode returns content as-is
        $this->assertEquals($code, $escaped);
    }

    public function testEscapeWithWithinOption(): void
    {
        $context = new CodeBlockContext(['within' => true]);
        $dialect = new CommonMarkDialect();
        $escaper = new CodeBlockEscaper($context, $dialect);

        $code    = "function test() {\n    return true;\n}";
        $escaped = $escaper->escape($code);

        // Within mode returns content as-is (no escaping needed within code blocks)
        $this->assertEquals($code, $escaped);
    }

    public function testEscapeWithComplexFencePatterns(): void
    {
        $context = new CodeBlockContext(['use_fences' => true]);
        $dialect = new CommonMarkDialect();
        $escaper = new CodeBlockEscaper($context, $dialect);

        // Test with many consecutive backticks
        $code    = 'code with ````` five backticks';
        $escaped = $escaper->escape($code);

        // Should use tildes since there are no tildes in the content
        $this->assertStringStartsWith('~~~', $escaped);
        $this->assertStringEndsWith('~~~', $escaped);
    }

    public function testEscapeWithMixedFenceCharacters(): void
    {
        $context = new CodeBlockContext(['use_fences' => true]);
        $dialect = new CommonMarkDialect();
        $escaper = new CodeBlockEscaper($context, $dialect);

        // Test with more tildes than backticks
        $code    = 'code with ` one backtick and ~~~~ four tildes';
        $escaped = $escaper->escape($code);

        // Should use backticks since there are fewer of them
        $this->assertStringStartsWith('``', $escaped);
        $this->assertStringEndsWith('``', $escaped);
    }

    public function testEscapeIndentedCodeBlockWithEmptyLines(): void
    {
        $code    = "first line\n\nsecond line\n\n";
        $escaped = $this->escaper->escape($code);

        $expected = "    first line\n\n    second line\n\n";
        $this->assertEquals($expected, $escaped);
    }

    public function testEscapeWithRawAndFencesOptions(): void
    {
        // Test that raw option takes precedence over use_fences
        $context = new CodeBlockContext(['raw' => true, 'use_fences' => true]);
        $dialect = new CommonMarkDialect();
        $escaper = new CodeBlockEscaper($context, $dialect);

        $code    = "function test() {\n    return true;\n}";
        $escaped = $escaper->escape($code);

        // Raw mode should take precedence
        $this->assertEquals($code, $escaped);
    }

    public function testEscapeWithWithinAndFencesOptions(): void
    {
        // Test that within option takes precedence over use_fences
        $context = new CodeBlockContext(['within' => true, 'use_fences' => true]);
        $dialect = new CommonMarkDialect();
        $escaper = new CodeBlockEscaper($context, $dialect);

        $code    = "function test() {\n    return true;\n}";
        $escaped = $escaper->escape($code);

        // Within mode should take precedence
        $this->assertEquals($code, $escaped);
    }
}
