<?php

declare(strict_types=1);

namespace Markdown\Escape\Tests\Integration;

use Markdown\Escape\Context\AbstractContext;
use Markdown\Escape\Context\GeneralContentContext;
use Markdown\Escape\Dialect\AbstractDialect;
use Markdown\Escape\Dialect\CommonMarkDialect;
use Markdown\Escape\Escaper\AbstractEscaper;
use Markdown\Escape\EscaperFactory;
use Markdown\Escape\MarkdownEscape;
use Markdown\Escape\Tests\TestCase;

class CustomizationTest extends TestCase
{
    public function testCustomContext(): void
    {
        // Create a custom context for table cells
        $tableCellContext = new class (['preserve_alignment' => true]) extends AbstractContext {
            public function __construct(array $options = [])
            {
                parent::__construct('table_cell', $options);
            }

            protected function configure(): void
            {
                $this->escapingTypes = ['pipe', 'newline'];
            }
        };

        $dialect = new CommonMarkDialect();
        $factory = new EscaperFactory();

        // Register a custom escaper for table cells
        $customEscaper = new class ($tableCellContext, $dialect) extends AbstractEscaper {
            protected function postProcess(string $content): string
            {
                // Additional processing for table cells
                $content = str_replace("\n", ' ', $content); // Replace newlines with spaces
                $content = trim($content); // Trim whitespace

                return $content;
            }
        };

        $factory->registerEscaper('table_cell', 'commonmark', $customEscaper);

        $escape = new MarkdownEscape($dialect, $factory);
        $result = $escape->escape("Cell with | pipe and\nnewline", $tableCellContext);

        $this->assertStringContainsString('\\|', $result);
        $this->assertStringNotContainsString("\n", $result);
    }

    public function testCustomDialect(): void
    {
        // Create a custom dialect that treats @ as special (like GitHub)
        $customDialect = new class () extends AbstractDialect {
            public function __construct()
            {
                parent::__construct('custom');
            }

            protected function configure(): void
            {
                $this->features = ['mentions', 'hashtags'];

                $this->characterMappings = [
                    GeneralContentContext::NAME => [
                        '@' => '\\@',
                        '#' => '\\#',
                        '*' => '\\*',
                        '_' => '\\_',
                        '[' => '\\[',
                        ']' => '\\]',
                    ],
                ];
            }

            protected function getDefaultSpecialCharacters(): array
            {
                return ['@', '#', '*', '_', '[', ']'];
            }

            protected function getDefaultCharacterMappings(): array
            {
                return [
                    '@' => '\\@',
                    '#' => '\\#',
                    '*' => '\\*',
                    '_' => '\\_',
                    '[' => '\\[',
                    ']' => '\\]',
                ];
            }
        };

        $escape  = new MarkdownEscape($customDialect);
        $content = 'Hello @user, check out #hashtag and *bold* text!';
        $escaped = $escape->escapeContent($content);

        $this->assertStringContainsString('\\@user', $escaped);
        $this->assertStringContainsString('\\#hashtag', $escaped);
        $this->assertStringContainsString('\\*bold\\*', $escaped);
    }

    public function testFactoryExtension(): void
    {
        $factory = new EscaperFactory();

        // Register a custom escaper class for a new context type
        $factory->registerDefaultEscaperClass('custom_context', TestCustomEscaper::class);

        // Create the context
        $context = new class () extends AbstractContext {
            public function __construct()
            {
                parent::__construct('custom_context');
            }

            protected function configure(): void
            {
                $this->escapingTypes = ['custom'];
            }
        };

        $dialect = new CommonMarkDialect();
        $escaper = $factory->createEscaper($context, $dialect);

        $this->assertInstanceOf(TestCustomEscaper::class, $escaper);
    }

    public function testChainedCustomizations(): void
    {
        // Create a pipeline of customizations
        $factory = new EscaperFactory();

        // Custom dialect for a wiki-like syntax
        $wikiDialect = new class () extends AbstractDialect {
            public function __construct()
            {
                parent::__construct('wiki');
            }

            protected function configure(): void
            {
                $this->features = ['wiki_links', 'templates'];

                $this->characterMappings = [
                    GeneralContentContext::NAME => [
                        '[[' => '\\[\\[',
                        ']]' => '\\]\\]',
                        '{{' => '\\{\\{',
                        '}}' => '\\}\\}',
                        '*'  => '\\*',
                        '_'  => '\\_',
                    ],
                ];
            }

            protected function getDefaultSpecialCharacters(): array
            {
                return ['[', ']', '{', '}', '*', '_'];
            }

            protected function getDefaultCharacterMappings(): array
            {
                return [
                    '[' => '\\[',
                    ']' => '\\]',
                    '{' => '\\{',
                    '}' => '\\}',
                    '*' => '\\*',
                    '_' => '\\_',
                ];
            }
        };

        $escape = new MarkdownEscape($wikiDialect, $factory);

        $content = 'This is a [[wiki link]] with {{template}} and *emphasis*.';
        $escaped = $escape->escapeContent($content);

        // Wiki syntax should be escaped
        $this->assertStringContainsString('\\[\\[wiki link\\]\\]', $escaped);
        $this->assertStringContainsString('\\{\\{template\\}\\}', $escaped);
        $this->assertStringContainsString('\\*emphasis\\*', $escaped);
    }
}

// Test helper class
class TestCustomEscaper extends AbstractEscaper
{
    protected function postProcess(string $content): string
    {
        return '[CUSTOM]' . $content . '[/CUSTOM]';
    }
}
