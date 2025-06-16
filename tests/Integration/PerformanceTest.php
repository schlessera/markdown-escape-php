<?php

declare(strict_types=1);

namespace Markdown\Escape\Tests\Integration;

use Markdown\Escape\MarkdownEscape;
use Markdown\Escape\Tests\TestCase;

class PerformanceTest extends TestCase
{
    /**
     * @var MarkdownEscape
     */
    private $escape;

    protected function setUp(): void
    {
        $this->escape = new MarkdownEscape();
    }

    /**
     * @group performance
     */
    public function testLargeDocumentProcessing(): void
    {
        // Generate a large document
        $sections = 100;
        $content  = '';

        for ($i = 1; $i <= $sections; $i++) {
            $content .= "# Section $i: *Important* Information\n\n";
            $content .= "This is paragraph _number_ $i with **special** characters.\n";
            $content .= "- List item with [link](https://example.com/page-$i)\n";
            $content .= "- Another item with `code` and more text\n\n";
            $content .= "```php\n";
            $content .= "function process$i(\$data) {\n";
            $content .= "    return \$data * $i;\n";
            $content .= "}\n";
            $content .= "```\n\n";
        }

        $startTime = microtime(true);
        $escaped   = $this->escape->escapeContent($content);
        $duration  = microtime(true) - $startTime;

        // Should process large documents quickly (under 100ms for 100 sections)
        $this->assertLessThan(0.1, $duration, 'Large document processing took too long');

        // Verify escaping worked
        $this->assertStringContainsString('\\*Important\\*', $escaped);
        $this->assertStringContainsString('\\_number\\_', $escaped);
        $this->assertStringContainsString('\\[link\\]', $escaped);
    }

    /**
     * @group performance
     */
    public function testManySmallOperations(): void
    {
        $iterations  = 1000;
        $testStrings = [
            'Simple text without special chars',
            'Text with *asterisks* and _underscores_',
            'Links [like this](url) and ![images](img.png)',
            'Code with `backticks` and ```blocks```',
            '# Headings and > quotes and - lists',
        ];

        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            foreach ($testStrings as $string) {
                $this->escape->escapeContent($string);
            }
        }

        $duration = microtime(true) - $startTime;
        $avgTime  = $duration / ($iterations * count($testStrings));

        // Average time per operation should be very small
        $this->assertLessThan(0.0001, $avgTime, 'Average operation time is too high');
    }

    /**
     * @group performance
     */
    public function testCachingBehavior(): void
    {
        // Test that repeated operations with the same factory are efficient
        $factory = $this->escape->getFactory();

        $context = new \Markdown\Escape\Context\GeneralContentContext();
        $dialect = new \Markdown\Escape\Dialect\CommonMarkDialect();

        // First call - creates escaper
        $startTime     = microtime(true);
        $escaper1      = $factory->createEscaper($context, $dialect);
        $firstCallTime = microtime(true) - $startTime;

        // Second call - should return cached escaper
        $startTime      = microtime(true);
        $escaper2       = $factory->createEscaper($context, $dialect);
        $secondCallTime = microtime(true) - $startTime;

        // Cached call should be significantly faster
        $this->assertSame($escaper1, $escaper2, 'Factory should return cached escaper');
        $this->assertLessThan($firstCallTime / 2, $secondCallTime, 'Cached call should be faster');
    }

    /**
     * @group performance
     */
    public function testMemoryUsage(): void
    {
        $initialMemory = memory_get_usage();

        // Process a large amount of data
        $largeText = str_repeat('This is a test with *special* characters. ', 10000);
        $escaped   = $this->escape->escapeContent($largeText);

        $memoryUsed = memory_get_usage() - $initialMemory;

        // Memory usage should be reasonable (less than 10MB for this test)
        $this->assertLessThan(10 * 1024 * 1024, $memoryUsed, 'Memory usage is too high');

        // Result should be properly escaped
        $this->assertStringContainsString('\\*special\\*', $escaped);
    }

    /**
     * @group performance
     */
    public function testComplexCodeBlockPerformance(): void
    {
        // Generate a complex code block with many special characters
        $code = <<<'CODE'
<?php
// Complex code with many special characters
$patterns = ['*', '_', '[', ']', '(', ')', '#', '+', '-', '.', '!', '|', '{', '}', '>', '`'];
$data = array_map(function($p) { return "Pattern: $p"; }, $patterns);
$result = implode(' | ', $data);
echo "Result: $result";

// More complex patterns
$regex = '/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/';
$markdown = '# Title with *emphasis* and [links](url)';
$escaped = str_replace(['*', '_', '[', ']'], ['\*', '\_', '\[', '\]'], $markdown);
CODE;

        $iterations = 100;
        $startTime  = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $this->escape->escapeCodeBlock($code, ['use_fences' => true, 'language' => 'php']);
        }

        $duration = microtime(true) - $startTime;
        $avgTime  = $duration / $iterations;

        // Should handle complex code blocks efficiently
        $this->assertLessThan(0.001, $avgTime, 'Code block escaping is too slow');
    }

    /**
     * @group performance
     */
    public function testUrlProcessingPerformance(): void
    {
        $urls = [
            'https://example.com/simple',
            'https://example.com/path with spaces/file (1).pdf',
            'https://user:pass@example.com:8080/path?query=value&foo[]=bar#section',
            'https://example.com/very/long/path/with/many/segments/and/special-chars_here.html?param1=value1&param2=value2',
            'https://международный.домен.рф/путь/к/файлу.html',
        ];

        $iterations = 500;
        $startTime  = microtime(true);

        foreach ($urls as $url) {
            for ($i = 0; $i < $iterations; $i++) {
                $this->escape->escapeUrl($url);
            }
        }

        $duration        = microtime(true) - $startTime;
        $totalOperations = count($urls) * $iterations;
        $avgTime         = $duration / $totalOperations;

        // URL escaping should be fast
        $this->assertLessThan(0.0001, $avgTime, 'URL escaping is too slow');
    }
}
