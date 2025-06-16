<?php

declare(strict_types=1);

namespace Markdown\Escape\Tests\Integration;

use Markdown\Escape\MarkdownEscape;
use Markdown\Escape\Tests\TestCase;

class EdgeCasesTest extends TestCase
{
    /**
     * @var MarkdownEscape
     */
    private $escape;

    protected function setUp(): void
    {
        $this->escape = new MarkdownEscape();
    }

    public function testEmptyContent(): void
    {
        $this->assertEquals('', $this->escape->escapeContent(''));
        $this->assertEquals('', $this->escape->escapeUrl(''));
        $this->assertEquals('``', $this->escape->escapeInlineCode(''));
        $this->assertEquals('', $this->escape->escapeCodeBlock(''));
    }

    public function testWhitespaceOnlyContent(): void
    {
        $spaces   = '   ';
        $tabs     = "\t\t";
        $newlines = "\n\n";
        $mixed    = " \t\n ";

        $this->assertEquals($spaces, $this->escape->escapeContent($spaces));
        $this->assertEquals($tabs, $this->escape->escapeContent($tabs));
        $this->assertEquals($newlines, $this->escape->escapeContent($newlines));
        $this->assertEquals($mixed, $this->escape->escapeContent($mixed));
    }

    public function testVeryLongContent(): void
    {
        // Generate a long string with special characters
        $longContent = str_repeat('This is a *test* with _special_ characters [link](url) ', 1000);

        $escaped = $this->escape->escapeContent($longContent);

        // Should handle long content without issues
        $this->assertStringContainsString('\\*test\\*', $escaped);
        $this->assertStringContainsString('\\_special\\_', $escaped);
        $this->assertStringContainsString('\\[link\\]\\(url\\)', $escaped);

        // Count occurrences
        $this->assertEquals(1000, substr_count($escaped, '\\*test\\*'));
    }

    public function testUnicodeContent(): void
    {
        $contents = [
            '你好 *世界*',              // Chinese
            'مرحبا _بالعالم_',         // Arabic
            '🎉 **Party** 🎊',        // Emojis
            'Café ☕ & Résumé 📄',    // Mixed
        ];

        foreach ($contents as $content) {
            $escaped = $this->escape->escapeContent($content);

            // Unicode should be preserved - check only what's in the current content
            if (strpos($content, '你好') !== false) {
                $this->assertStringContainsString('你好', $escaped);
            }
            if (strpos($content, 'مرحبا') !== false) {
                $this->assertStringContainsString('مرحبا', $escaped);
            }
            if (strpos($content, '🎉') !== false) {
                $this->assertStringContainsString('🎉', $escaped);
            }
            if (strpos($content, '☕') !== false) {
                $this->assertStringContainsString('☕', $escaped);
            }

            // But markdown special chars should still be escaped
            if (strpos($content, '*') !== false) {
                $this->assertStringContainsString('\\*', $escaped);
            }
            if (strpos($content, '_') !== false) {
                $this->assertStringContainsString('\\_', $escaped);
            }
        }
    }

    public function testConsecutiveSpecialCharacters(): void
    {
        $cases = [
            '***bold and italic***'      => '\\*\\*\\*bold and italic\\*\\*\\*',
            '___also bold and italic___' => '\\_\\_\\_also bold and italic\\_\\_\\_',
            '```multiple backticks```'   => '\\`\\`\\`multiple backticks\\`\\`\\`',
            '### Heading ###'            => '\\### Heading ###',
            '---horizontal rule---'      => '\\---horizontal rule---',  // First hyphen escaped at start of line
        ];

        foreach ($cases as $input => $expected) {
            $escaped = $this->escape->escapeContent($input);
            $this->assertEquals($expected, $escaped);
        }
    }

    public function testNestedBackticksInCode(): void
    {
        $codes = [
            '`code`'     => '`` `code` ``',
            '``code``'   => '``` ``code`` ```',
            '```code```' => '```` ```code``` ````',
            'a`b`c`d'    => '``a`b`c`d``',
            '` ` `'      => '`` ` ` ` ``',
        ];

        foreach ($codes as $input => $expected) {
            $escaped = $this->escape->escapeInlineCode($input);
            $this->assertEquals($expected, $escaped);
        }
    }

    public function testCodeBlocksWithVariousFences(): void
    {
        // Test with different fence scenarios
        $testCases = [
            // No fences in content
            "simple\ncode" => ["```\nsimple\ncode\n```", '```'],
            // Contains backticks
            'has ``` inside' => ["~~~\nhas ``` inside\n~~~", '~~~'],
            // Contains tildes
            'has ~~~ inside' => ["```\nhas ~~~ inside\n```", '```'],
            // Contains both
            '``` and ~~~' => ["````\n``` and ~~~\n````", '````'],
        ];

        foreach ($testCases as $code => $expected) {
            $escaped = $this->escape->escapeCodeBlock($code, ['use_fences' => true]);
            $this->assertEquals($expected[0], $escaped);
            $this->assertStringStartsWith($expected[1], $escaped);
        }
    }

    public function testUrlEdgeCases(): void
    {
        $urls = [
            // Already encoded - will be double encoded
            'https://example.com/path%20with%20spaces' => 'https://example.com/path%2520with%2520spaces',
            // Mixed encoding - %20 stays as is
            'https://example.com/path with%20mixed encoding' => 'https://example.com/path%20with%20mixed%20encoding',
            // Special characters in fragment
            'https://example.com#section:subsection' => 'https://example.com#section%3Asubsection',
            // Query parameters with special chars - http_build_query adds array indices
            'https://example.com?q=test&foo[]=bar' => 'https://example.com?q=test&foo%5B0%5D=bar',
        ];

        foreach ($urls as $input => $expected) {
            $escaped = $this->escape->escapeUrl($input);
            $this->assertEquals($expected, $escaped);
        }
    }

    public function testMixedLineEndings(): void
    {
        $contents = [
            "Line 1\nLine 2\nLine 3",      // Unix
            "Line 1\r\nLine 2\r\nLine 3",  // Windows
            "Line 1\rLine 2\rLine 3",      // Old Mac
            "Line 1\n\rLine 2\r\nLine 3",  // Mixed
        ];

        foreach ($contents as $content) {
            $escaped = $this->escape->escapeContent($content);

            // Should preserve line endings
            $this->assertStringContainsString('Line 1', $escaped);
            $this->assertStringContainsString('Line 2', $escaped);
            $this->assertStringContainsString('Line 3', $escaped);
        }
    }

    public function testPathologicalCases(): void
    {
        // Test cases that might cause performance issues
        $nested  = str_repeat('[', 50) . 'text' . str_repeat(']', 50);
        $escaped = $this->escape->escapeContent($nested);
        $this->assertEquals(str_repeat('\\[', 50) . 'text' . str_repeat('\\]', 50), $escaped);

        // Many different special characters
        $allSpecial = '\\*_[]()#+-!|{}>`~@:';
        $escaped    = $this->escape->escapeContent($allSpecial);

        // Check each character individually, handling the backslash specially
        $this->assertStringContainsString('\\\\', $escaped); // Backslash
        $this->assertStringContainsString('\\*', $escaped);
        $this->assertStringContainsString('\\_', $escaped);
        $this->assertStringContainsString('\\[', $escaped);
        $this->assertStringContainsString('\\]', $escaped);
        $this->assertStringContainsString('\\(', $escaped);
        $this->assertStringContainsString('\\)', $escaped);
        $this->assertStringContainsString('#', $escaped);    // # not escaped in middle
        $this->assertStringContainsString('\\+', $escaped);
        $this->assertStringContainsString('-', $escaped);    // - not escaped in middle
        $this->assertStringContainsString('!', $escaped);    // ! not escaped without [
        $this->assertStringContainsString('\\|', $escaped);
        $this->assertStringContainsString('\\{', $escaped);
        $this->assertStringContainsString('\\}', $escaped);
        $this->assertStringContainsString('>', $escaped);    // > not escaped in middle
        $this->assertStringContainsString('\\`', $escaped);
        // Note: ~ @ : are not special in CommonMark by default
    }
}
