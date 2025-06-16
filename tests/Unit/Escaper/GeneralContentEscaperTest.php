<?php

declare(strict_types=1);

namespace Markdown\Escape\Tests\Unit\Escaper;

use Markdown\Escape\Context\GeneralContentContext;
use Markdown\Escape\Dialect\CommonMarkDialect;
use Markdown\Escape\Escaper\GeneralContentEscaper;
use Markdown\Escape\Tests\TestCase;

class GeneralContentEscaperTest extends TestCase
{
    /**
     * @var GeneralContentEscaper
     */
    private $escaper;

    protected function setUp(): void
    {
        $context       = new GeneralContentContext();
        $dialect       = new CommonMarkDialect();
        $this->escaper = new GeneralContentEscaper($context, $dialect);
    }

    public function testEscapeSpecialCharacters(): void
    {
        $content = 'This has *asterisks* and _underscores_ and [brackets]';
        $escaped = $this->escaper->escape($content);

        $this->assertEquals('This has \\*asterisks\\* and \\_underscores\\_ and \\[brackets\\]', $escaped);
    }

    public function testEscapeStartOfLineCharacters(): void
    {
        $content = "# Heading\n> Quote\n- List item\n+ Another item\n1. Numbered";
        $escaped = $this->escaper->escape($content);

        $this->assertStringContainsString('\\# Heading', $escaped);
        $this->assertStringContainsString('\\> Quote', $escaped);
        $this->assertStringContainsString('\\- List item', $escaped);
        $this->assertStringContainsString('\\+ Another item', $escaped);
        $this->assertStringContainsString('1\\. Numbered', $escaped);
    }

    public function testEscapeEmphasisAtWordBoundaries(): void
    {
        $content = 'snake_case_variable and another_example';
        $escaped = $this->escaper->escape($content);

        $this->assertStringContainsString('snake\\_case\\_variable', $escaped);
        $this->assertStringContainsString('another\\_example', $escaped);
    }

    public function testComplexContent(): void
    {
        $content = <<<'MD'
# This is a heading
With some **bold** text and _italic_ text.
> A blockquote with [a link](https://example.com)
- List item with `code`
1. Numbered list with * and _
MD;

        $escaped = $this->escaper->escape($content);

        $this->assertStringContainsString('\\# This is a heading', $escaped);
        $this->assertStringContainsString('\\*\\*bold\\*\\*', $escaped);
        $this->assertStringContainsString('\\_italic\\_', $escaped);
        $this->assertStringContainsString('\\> A blockquote', $escaped);
        $this->assertStringContainsString('\\[a link\\]', $escaped);
        $this->assertStringContainsString('\\- List item', $escaped);
        $this->assertStringContainsString('\\`code\\`', $escaped);
        $this->assertStringContainsString('1\\. Numbered list', $escaped);
    }

    public function testEscapeStartOfLineWithIndentation(): void
    {
        $content = "  # Indented heading\n    > Indented quote\n  - Indented list\n    + Another indented\n  1. Indented number";
        $escaped = $this->escaper->escape($content);

        $this->assertStringContainsString('  \\# Indented heading', $escaped);
        $this->assertStringContainsString('    \\> Indented quote', $escaped);
        $this->assertStringContainsString('  \\- Indented list', $escaped);
        $this->assertStringContainsString('    \\+ Another indented', $escaped);
        $this->assertStringContainsString('  1\\. Indented number', $escaped);
    }

    public function testEscapeAsteriskAtStartOfLine(): void
    {
        $content = "* List item with asterisk\n  * Indented asterisk";
        $escaped = $this->escaper->escape($content);

        $this->assertStringContainsString('\\* List item with asterisk', $escaped);
        $this->assertStringContainsString('  \\* Indented asterisk', $escaped);
    }

    public function testSpecialCharactersWithoutSpaceAfterAreNotEscaped(): void
    {
        // These should NOT be escaped because they don't have a space after them
        $content = "#NoSpace\n>NoSpace\n-NoSpace\n+NoSpace\n*NoSpace\n1.NoSpace";
        $escaped = $this->escaper->escape($content);

        // The special characters themselves will be escaped by the base escaper,
        // but not by the start-of-line logic
        $this->assertStringContainsString('\\#NoSpace', $escaped);
        $this->assertStringContainsString('\\>NoSpace', $escaped);
        $this->assertStringContainsString('\\-NoSpace', $escaped);
        $this->assertStringContainsString('\\+NoSpace', $escaped);
        $this->assertStringContainsString('\\*NoSpace', $escaped);
        $this->assertStringContainsString('1.NoSpace', $escaped); // Dot is not escaped (no space after)
    }

    public function testEscapeExclamationBeforeBracket(): void
    {
        $content = 'This is an image ![alt text](image.jpg) and not !this';
        $escaped = $this->escaper->escape($content);

        // ! should be escaped when followed by [
        $this->assertStringContainsString('\\!\\[alt text\\]', $escaped);
        // ! should NOT be escaped when not followed by [
        $this->assertStringContainsString('and not !this', $escaped);
    }

    public function testEscapeBackslashes(): void
    {
        $content = 'Path\\to\\file and escape \\ character';
        $escaped = $this->escaper->escape($content);

        // Backslashes should be doubled
        $this->assertStringContainsString('Path\\\\to\\\\file', $escaped);
        $this->assertStringContainsString('escape \\\\ character', $escaped);
    }

    public function testEscapeBackslashAtEndOfLine(): void
    {
        $content = 'Line ends with backslash\\';
        $escaped = $this->escaper->escape($content);

        // Backslash at end should be doubled
        $this->assertEquals('Line ends with backslash\\\\', $escaped);
    }

    public function testEscapeGfmSpecialCharacters(): void
    {
        // Use GFM dialect which has additional special characters
        $context = new GeneralContentContext();
        $dialect = new \Markdown\Escape\Dialect\GitHubFlavoredMarkdownDialect();
        $escaper = new GeneralContentEscaper($context, $dialect);

        $content = 'Mention @user, emoji :smile:, strikethrough ~text~';
        $escaped = $escaper->escape($content);

        // In GFM, these should be escaped
        $this->assertStringContainsString('\\@user', $escaped);
        $this->assertStringContainsString('\\:smile\\:', $escaped);
        $this->assertStringContainsString('\\~text\\~', $escaped);
    }

    public function testEscapeHashInCustomDialect(): void
    {
        // Create a mock custom dialect
        $customDialect = new class ('custom') extends \Markdown\Escape\Dialect\AbstractDialect {
            protected function configure(): void
            {
                $this->features          = [];
                $this->characterMappings = [];
            }

            protected function getDefaultSpecialCharacters(): array
            {
                return ['#', '*', '_'];
            }

            protected function getDefaultCharacterMappings(): array
            {
                return [
                    '#' => '\\#',
                    '*' => '\\*',
                    '_' => '\\_',
                ];
            }

            public function getEscapeRules(\Markdown\Escape\Contract\ContextInterface $context): array
            {
                return [];
            }
        };

        $context = new GeneralContentContext();
        $escaper = new GeneralContentEscaper($context, $customDialect);

        $content = 'This # should be escaped in custom dialect';
        $escaped = $escaper->escape($content);

        // # should be escaped in custom dialects (not just at line start)
        $this->assertStringContainsString('This \\# should be escaped', $escaped);
    }

    public function testEscapeOtherSpecialCharacters(): void
    {
        $content = 'Parentheses (text), brackets {text}, pipes |text|, plus +text';
        $escaped = $this->escaper->escape($content);

        $this->assertStringContainsString('\\(text\\)', $escaped);
        $this->assertStringContainsString('\\{text\\}', $escaped);
        $this->assertStringContainsString('\\|text\\|', $escaped);
        $this->assertStringContainsString('\\+text', $escaped);
    }
}
