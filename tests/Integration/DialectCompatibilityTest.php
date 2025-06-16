<?php

declare(strict_types=1);

namespace Markdown\Escape\Tests\Integration;

use Markdown\Escape\MarkdownEscape;
use Markdown\Escape\Tests\TestCase;

class DialectCompatibilityTest extends TestCase
{
    public function testCommonMarkVsGitHubFlavoredMarkdown(): void
    {
        $content = 'This has @mentions, :emoji:, and ~~strikethrough~~ text.';

        // Test with CommonMark
        $commonMark = MarkdownEscape::commonMark();
        $cmEscaped  = $commonMark->escapeContent($content);

        // Test with GFM
        $gfm        = MarkdownEscape::gfm();
        $gfmEscaped = $gfm->escapeContent($content);

        // GFM should escape additional characters
        $this->assertStringContainsString('\\@mentions', $gfmEscaped);
        $this->assertStringContainsString('\\:emoji\\:', $gfmEscaped);
        $this->assertStringContainsString('\\~\\~strikethrough\\~\\~', $gfmEscaped);

        // CommonMark doesn't escape these by default
        $this->assertStringContainsString('@mentions', $cmEscaped);
        $this->assertStringContainsString(':emoji:', $cmEscaped);
        $this->assertStringContainsString('~~strikethrough~~', $cmEscaped);
    }

    public function testTaskListEscaping(): void
    {
        $tasks = [
            '- [ ] Pending task',
            '- [x] Completed task',
            '- [X] Also completed',
            '- [] Not a task',
        ];

        $gfm = MarkdownEscape::gfm();

        foreach ($tasks as $task) {
            $escaped = $gfm->escapeContent($task);
            // The dash at the beginning should be escaped
            $this->assertStringStartsWith('\\-', $escaped);
            // Brackets should be escaped
            $this->assertStringContainsString('\\[', $escaped);
            $this->assertStringContainsString('\\]', $escaped);
        }
    }

    public function testAutolinksInGFM(): void
    {
        $gfm = MarkdownEscape::gfm();

        // URLs that might be auto-linked
        $urls = [
            'Visit https://example.com for more info',
            'Email me at user@example.com',
            'Check out www.example.com',
        ];

        foreach ($urls as $text) {
            $escaped = $gfm->escapeContent($text);

            // Check for proper escaping based on content
            if (strpos($text, 'https://') !== false) {
                // Colon is escaped to prevent auto-linking
                $this->assertStringContainsString('\\:', $escaped);
            }

            // @ should be escaped in GFM
            if (strpos($text, '@') !== false) {
                $this->assertStringContainsString('\\@', $escaped);
            }
        }
    }

    public function testFootnoteEscaping(): void
    {
        $gfm = MarkdownEscape::gfm();

        $textWithFootnote = 'This is some text[^1] with a footnote reference.';
        $escaped          = $gfm->escapeContent($textWithFootnote);

        $this->assertStringContainsString('text\\[^1\\]', $escaped);
    }

    public function testTableSpecificEscaping(): void
    {
        $commonMark = MarkdownEscape::commonMark();
        $gfm        = MarkdownEscape::gfm();

        $cellContent = 'Value | with pipe';

        $cmEscaped  = $commonMark->escapeContent($cellContent);
        $gfmEscaped = $gfm->escapeContent($cellContent);

        // Both should escape the pipe character
        $this->assertStringContainsString('Value \\| with pipe', $cmEscaped);
        $this->assertStringContainsString('Value \\| with pipe', $gfmEscaped);
    }

    public function testMathBlockCompatibility(): void
    {
        // Some Markdown processors support math blocks with $$
        $mathContent = 'This costs $$50 per unit';

        $commonMark = MarkdownEscape::commonMark();
        $escaped    = $commonMark->escapeContent($mathContent);

        // Dollar signs should not interfere
        $this->assertStringContainsString('$$50', $escaped);
    }

    public function testHTMLEntityHandling(): void
    {
        $content = 'Use &lt; and &gt; for less than and greater than';

        $escape  = new MarkdownEscape();
        $escaped = $escape->escapeContent($content);

        // HTML entities should be preserved
        $this->assertStringContainsString('&lt;', $escaped);
        $this->assertStringContainsString('&gt;', $escaped);
    }

    public function testDialectSwitching(): void
    {
        $content = 'Text with @mention and :emoji:';

        // Start with CommonMark
        $escape   = MarkdownEscape::commonMark();
        $cmResult = $escape->escapeContent($content);

        // Switch to GFM
        $escape    = $escape->withDialect(new \Markdown\Escape\Dialect\GitHubFlavoredMarkdownDialect());
        $gfmResult = $escape->escapeContent($content);

        // Results should be different
        $this->assertNotEquals($cmResult, $gfmResult);
        $this->assertStringContainsString('\\@mention', $gfmResult);
        $this->assertStringContainsString('\\:emoji\\:', $gfmResult);
    }
}
