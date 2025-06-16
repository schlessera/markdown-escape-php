<?php

declare(strict_types=1);

namespace Markdown\Escape\Tests\Unit\Dialect;

use Markdown\Escape\Context\CodeBlockContext;
use Markdown\Escape\Context\GeneralContentContext;
use Markdown\Escape\Context\InlineCodeContext;
use Markdown\Escape\Context\UrlContext;
use Markdown\Escape\Dialect\GitHubFlavoredMarkdownDialect;
use Markdown\Escape\Tests\TestCase;

class GitHubFlavoredMarkdownDialectTest extends TestCase
{
    /**
     * @var GitHubFlavoredMarkdownDialect
     */
    private $dialect;

    protected function setUp(): void
    {
        $this->dialect = new GitHubFlavoredMarkdownDialect();
    }

    public function testGetName(): void
    {
        $this->assertEquals('gfm', $this->dialect->getName());
    }

    public function testGetSpecialCharactersForGeneralContent(): void
    {
        $context    = new GeneralContentContext();
        $characters = $this->dialect->getSpecialCharacters($context);

        $this->assertContains('*', $characters);
        $this->assertContains('_', $characters);
        $this->assertContains('[', $characters);
        $this->assertContains(']', $characters);
        $this->assertContains('#', $characters);
        $this->assertContains('`', $characters);
        $this->assertContains('~', $characters);
        $this->assertContains('@', $characters);
        $this->assertContains(':', $characters);
    }

    public function testGetSpecialCharactersForUrl(): void
    {
        $context    = new UrlContext();
        $characters = $this->dialect->getSpecialCharacters($context);

        $this->assertContains(' ', $characters);
        $this->assertContains('(', $characters);
        $this->assertContains(')', $characters);
        $this->assertContains('<', $characters);
        $this->assertContains('>', $characters);
        $this->assertContains('[', $characters);
        $this->assertContains(']', $characters);
    }

    public function testGetSpecialCharactersForInlineCode(): void
    {
        $context    = new InlineCodeContext();
        $characters = $this->dialect->getSpecialCharacters($context);

        $this->assertContains('`', $characters);
    }

    public function testGetSpecialCharactersForCodeBlock(): void
    {
        $context    = new CodeBlockContext();
        $characters = $this->dialect->getSpecialCharacters($context);

        $this->assertEmpty($characters);
    }

    public function testEscapeCharacterForGeneralContent(): void
    {
        $context = new GeneralContentContext();

        $this->assertEquals('\\*', $this->dialect->escapeCharacter('*', $context));
        $this->assertEquals('\\_', $this->dialect->escapeCharacter('_', $context));
        $this->assertEquals('\\[', $this->dialect->escapeCharacter('[', $context));
        $this->assertEquals('\\#', $this->dialect->escapeCharacter('#', $context));
        $this->assertEquals('\\~', $this->dialect->escapeCharacter('~', $context));
        $this->assertEquals('\\@', $this->dialect->escapeCharacter('@', $context));
        $this->assertEquals('\\:', $this->dialect->escapeCharacter(':', $context));
    }

    public function testEscapeCharacterForUrl(): void
    {
        $context = new UrlContext();

        $this->assertEquals('%20', $this->dialect->escapeCharacter(' ', $context));
        $this->assertEquals('%28', $this->dialect->escapeCharacter('(', $context));
        $this->assertEquals('%29', $this->dialect->escapeCharacter(')', $context));
        $this->assertEquals('%5B', $this->dialect->escapeCharacter('[', $context));
        $this->assertEquals('%5D', $this->dialect->escapeCharacter(']', $context));
    }

    public function testSupportsFeature(): void
    {
        // Standard features
        $this->assertTrue($this->dialect->supportsFeature('emphasis'));
        $this->assertTrue($this->dialect->supportsFeature('strong_emphasis'));
        $this->assertTrue($this->dialect->supportsFeature('links'));
        $this->assertTrue($this->dialect->supportsFeature('code_blocks'));
        $this->assertTrue($this->dialect->supportsFeature('tables'));

        // GFM-specific features
        $this->assertTrue($this->dialect->supportsFeature('task_lists'));
        $this->assertTrue($this->dialect->supportsFeature('mentions'));
        $this->assertTrue($this->dialect->supportsFeature('emoji'));
        $this->assertTrue($this->dialect->supportsFeature('autolinks'));
        $this->assertTrue($this->dialect->supportsFeature('strikethrough'));
        $this->assertTrue($this->dialect->supportsFeature('footnotes'));

        // Non-existent feature
        $this->assertFalse($this->dialect->supportsFeature('non_existent_feature'));
    }

    public function testGetDefaultSpecialCharacters(): void
    {
        // Test the fallback to default special characters
        $mockContext = $this->createMock(\Markdown\Escape\Contract\ContextInterface::class);
        $mockContext->method('getName')->willReturn('unknown_context');

        $characters = $this->dialect->getSpecialCharacters($mockContext);

        // Should return default special characters
        $this->assertContains('*', $characters);
        $this->assertContains('_', $characters);
        $this->assertContains('@', $characters);
        $this->assertContains(':', $characters);
        $this->assertContains('~', $characters);
    }

    public function testGetDefaultCharacterMappings(): void
    {
        // Test the fallback to default character mappings
        $mockContext = $this->createMock(\Markdown\Escape\Contract\ContextInterface::class);
        $mockContext->method('getName')->willReturn('unknown_context');

        // Test escaping with unknown context (uses default mappings)
        $this->assertEquals('\\@', $this->dialect->escapeCharacter('@', $mockContext));
        $this->assertEquals('\\:', $this->dialect->escapeCharacter(':', $mockContext));
        $this->assertEquals('\\~', $this->dialect->escapeCharacter('~', $mockContext));
    }
}
