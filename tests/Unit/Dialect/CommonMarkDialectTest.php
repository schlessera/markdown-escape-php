<?php

declare(strict_types=1);

namespace Markdown\Escape\Tests\Unit\Dialect;

use Markdown\Escape\Context\GeneralContentContext;
use Markdown\Escape\Context\UrlContext;
use Markdown\Escape\Dialect\CommonMarkDialect;
use Markdown\Escape\Tests\TestCase;

class CommonMarkDialectTest extends TestCase
{
    /**
     * @var CommonMarkDialect
     */
    private $dialect;

    protected function setUp(): void
    {
        $this->dialect = new CommonMarkDialect();
    }

    public function testGetName(): void
    {
        $this->assertEquals('commonmark', $this->dialect->getName());
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
    }

    public function testEscapeCharacterForGeneralContent(): void
    {
        $context = new GeneralContentContext();

        $this->assertEquals('\\*', $this->dialect->escapeCharacter('*', $context));
        $this->assertEquals('\\_', $this->dialect->escapeCharacter('_', $context));
        $this->assertEquals('\\[', $this->dialect->escapeCharacter('[', $context));
        $this->assertEquals('\\#', $this->dialect->escapeCharacter('#', $context));
    }

    public function testEscapeCharacterForUrl(): void
    {
        $context = new UrlContext();

        $this->assertEquals('%20', $this->dialect->escapeCharacter(' ', $context));
        $this->assertEquals('%28', $this->dialect->escapeCharacter('(', $context));
        $this->assertEquals('%29', $this->dialect->escapeCharacter(')', $context));
    }

    public function testSupportsFeature(): void
    {
        $this->assertTrue($this->dialect->supportsFeature('emphasis'));
        $this->assertTrue($this->dialect->supportsFeature('strong_emphasis'));
        $this->assertTrue($this->dialect->supportsFeature('links'));
        $this->assertTrue($this->dialect->supportsFeature('code_blocks'));
        $this->assertTrue($this->dialect->supportsFeature('tables'));

        $this->assertFalse($this->dialect->supportsFeature('task_lists'));
        $this->assertFalse($this->dialect->supportsFeature('mentions'));
        $this->assertFalse($this->dialect->supportsFeature('emoji'));
    }
}
