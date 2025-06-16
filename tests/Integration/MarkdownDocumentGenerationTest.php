<?php

declare(strict_types=1);

namespace Markdown\Escape\Tests\Integration;

use Markdown\Escape\MarkdownEscape;
use Markdown\Escape\Tests\TestCase;

class MarkdownDocumentGenerationTest extends TestCase
{
    /**
     * @var MarkdownEscape
     */
    private $escape;

    protected function setUp(): void
    {
        $this->escape = new MarkdownEscape();
    }

    public function testGenerateCompleteMarkdownDocument(): void
    {
        // Simulate user-provided content that might contain special characters
        $title       = 'My Project: *Important* & [Urgent]';
        $description = 'This is a _test_ project with **special** characters like $100, 50% off, and C++ code.';
        $code        = <<<'CODE'
function calculate($price) {
    // Apply 50% discount
    return $price * 0.5;
}
CODE;
        $url            = 'https://example.com/path with spaces/file (1).pdf';
        $commandExample = 'npm install --save-dev @types/node';

        // Build a complete markdown document
        $markdown = sprintf(
            "# %s\n\n%s\n\n## Installation\n\n```bash\n%s\n```\n\n## Code Example\n\n%s\n\n## Download\n\nDownload the file from [here](%s).",
            $this->escape->escapeContent($title),
            $this->escape->escapeContent($description),
            $commandExample, // Commands in code blocks don't need escaping
            $this->escape->escapeCodeBlock($code, ['use_fences' => true, 'language' => 'php']),
            $this->escape->escapeUrl($url)
        );

        // Verify the output
        $this->assertStringContainsString('My Project: \\*Important\\* & \\[Urgent\\]', $markdown);
        $this->assertStringContainsString('This is a \\_test\\_ project with \\*\\*special\\*\\*', $markdown);
        $this->assertStringContainsString('```php', $markdown);
        $this->assertStringContainsString('function calculate($price)', $markdown);
        $this->assertStringContainsString('path%20with%20spaces', $markdown);
        $this->assertStringContainsString('file%20%281%29.pdf', $markdown);
    }

    public function testGenerateTableWithSpecialCharacters(): void
    {
        $headers = ['Feature', 'Status*', 'Price ($)', 'Discount (%)'];
        $rows    = [
            ['Basic|Free', 'Active', '$0', '0%'],
            ['Pro*', 'Beta', '$9.99', '10% off'],
            ['Enterprise [Custom]', 'Coming Soon', '$99+', '20-30%'],
        ];

        $markdown = '| ' . implode(' | ', array_map([$this->escape, 'escapeContent'], $headers)) . " |\n";
        $markdown .= '|' . str_repeat(' --- |', count($headers)) . "\n";

        foreach ($rows as $row) {
            $markdown .= '| ' . implode(' | ', array_map([$this->escape, 'escapeContent'], $row)) . " |\n";
        }

        $this->assertStringContainsString('Status\\*', $markdown);
        $this->assertStringContainsString('Basic\\|Free', $markdown);
        $this->assertStringContainsString('Pro\\*', $markdown);
        $this->assertStringContainsString('Enterprise \\[Custom\\]', $markdown);
        $this->assertStringContainsString('20-30%', $markdown);
    }

    public function testNestedListsWithSpecialCharacters(): void
    {
        $items = [
            '# Not a heading',
            '- Not a list item',
            '1. Not a numbered list',
            '> Not a quote',
            '* Not a bullet point',
        ];

        $markdown = "Here are some items:\n\n";
        foreach ($items as $item) {
            $markdown .= '- ' . $this->escape->escapeContent($item) . "\n";
        }

        $this->assertStringContainsString('- \\# Not a heading', $markdown);
        $this->assertStringContainsString('- \\- Not a list item', $markdown);
        $this->assertStringContainsString('- 1\\. Not a numbered list', $markdown);
        $this->assertStringContainsString('- \\> Not a quote', $markdown);
        $this->assertStringContainsString('- \\* Not a bullet point', $markdown);
    }

    public function testMixedContentWithInlineCode(): void
    {
        $text    = 'Use the `*` operator for multiplication and `_` for underscores in Python.';
        $escaped = $this->escape->escapeContent($text);

        // Backticks should be escaped in general content
        $this->assertStringContainsString('Use the \\`\\*\\` operator', $escaped);
        $this->assertStringContainsString('and \\`\\_\\` for underscores', $escaped);

        // But when we want actual inline code
        $code1    = '*';
        $code2    = '_';
        $markdown = sprintf(
            'Use the %s operator for multiplication and %s for underscores in Python.',
            $this->escape->escapeInlineCode($code1),
            $this->escape->escapeInlineCode($code2)
        );

        $this->assertEquals('Use the `*` operator for multiplication and `_` for underscores in Python.', $markdown);
    }

    public function testCodeBlockWithBackticksAndTildes(): void
    {
        $code = <<<'CODE'
// This code contains both ``` and ~~~
const markdown = ```
# Title
~~~
Code block
~~~
```;
CODE;

        $escaped = $this->escape->escapeCodeBlock($code, ['use_fences' => true]);

        // Should use 4 backticks since the content has 3
        $this->assertStringStartsWith('````', $escaped);
        $this->assertStringEndsWith('````', $escaped);
        $this->assertStringContainsString($code, $escaped);
    }

    public function testUrlsInDifferentContexts(): void
    {
        $baseUrl = 'https://example.com/docs/';
        $page    = 'Getting Started (Part 1)';
        $anchor  = '#step-2:-installation';

        // URL in link
        $url        = $baseUrl . $page . $anchor;
        $escapedUrl = $this->escape->escapeUrl($url);
        $linkText   = $this->escape->escapeContent($page);
        $markdown   = sprintf('[%s](%s)', $linkText, $escapedUrl);

        $this->assertStringContainsString('Getting Started \\(Part 1\\)', $markdown);
        $this->assertStringContainsString('Getting%20Started%20%28Part%201%29', $markdown);
        // Note: rawurlencode in PHP doesn't encode colons for RFC3986 compliance
        $this->assertStringContainsString('#step-2:-installation', $escapedUrl);

        // Reference-style link
        $refName   = $this->escape->escapeContent('guide-1');
        $reference = sprintf('[%s]: %s', $refName, $escapedUrl);

        $this->assertStringContainsString('[guide-1]:', $reference);
    }

    public function testRealWorldREADMEGeneration(): void
    {
        // Simulate generating a README with user-provided data
        $projectName = 'Super*Star* Framework [Beta]';
        $version     = '2.0-beta.1';
        $installCmd  = 'composer require superstar/framework:^2.0';
        $exampleCode = <<<'PHP'
<?php
use SuperStar\Framework;

$app = new Framework(['debug' => true]);
$app->route('GET', '/api/*', function($path) {
    return ['path' => $path];
});
PHP;

        $features = [
            '**Fast** & _Lightweight_',
            'Support for `PHP 7.2+`',
            '100% Test Coverage',
            'MIT License',
        ];

        $markdown = sprintf("# %s\n\n", $this->escape->escapeContent($projectName));
        $markdown .= sprintf("Version: %s\n\n", $this->escape->escapeContent($version));
        $markdown .= "## Installation\n\n```bash\n" . $installCmd . "\n```\n\n";
        $markdown .= "## Quick Example\n\n";
        $markdown .= $this->escape->escapeCodeBlock($exampleCode, ['use_fences' => true, 'language' => 'php']);
        $markdown .= "\n\n## Features\n\n";

        foreach ($features as $feature) {
            $markdown .= sprintf("- %s\n", $this->escape->escapeContent($feature));
        }

        // Verify escaping
        $this->assertStringContainsString('Super\\*Star\\* Framework \\[Beta\\]', $markdown);
        $this->assertStringContainsString('2.0-beta.1', $markdown);
        $this->assertStringContainsString('\\*\\*Fast\\*\\* & \\_Lightweight\\_', $markdown);
        $this->assertStringContainsString('Support for \\`PHP 7.2\\+\\`', $markdown);
        $this->assertStringContainsString("```php\n<?php", $markdown);
    }
}
