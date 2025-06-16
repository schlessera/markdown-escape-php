<?php

declare(strict_types=1);

namespace Markdown\Escape\Tests\Unit\Escaper;

use Markdown\Escape\Context\InlineCodeContext;
use Markdown\Escape\Dialect\CommonMarkDialect;
use Markdown\Escape\Escaper\InlineCodeEscaper;
use Markdown\Escape\Tests\TestCase;

class InlineCodeEscaperTest extends TestCase
{
    /**
     * @var InlineCodeEscaper
     */
    private $escaper;

    protected function setUp(): void
    {
        $context       = new InlineCodeContext();
        $dialect       = new CommonMarkDialect();
        $this->escaper = new InlineCodeEscaper($context, $dialect);
    }

    public function testEscapeSimpleCode(): void
    {
        $code    = 'const x = 42;';
        $escaped = $this->escaper->escape($code);

        $this->assertEquals('`const x = 42;`', $escaped);
    }

    public function testEscapeCodeWithSingleBacktick(): void
    {
        $code    = 'const str = `template`;';
        $escaped = $this->escaper->escape($code);

        $this->assertEquals('``const str = `template`;``', $escaped);
    }

    public function testEscapeCodeWithDoubleBackticks(): void
    {
        $code    = 'const str = ``double backticks``;';
        $escaped = $this->escaper->escape($code);

        $this->assertEquals('```const str = ``double backticks``;```', $escaped);
    }

    public function testEscapeCodeStartingWithBacktick(): void
    {
        $code    = '`starts with backtick';
        $escaped = $this->escaper->escape($code);

        $this->assertEquals('`` `starts with backtick ``', $escaped);
    }

    public function testEscapeCodeEndingWithBacktick(): void
    {
        $code    = 'ends with backtick`';
        $escaped = $this->escaper->escape($code);

        $this->assertEquals('`` ends with backtick` ``', $escaped);
    }

    public function testEscapeCodeWithBackticksBothEnds(): void
    {
        $code    = '`surrounded by backticks`';
        $escaped = $this->escaper->escape($code);

        $this->assertEquals('`` `surrounded by backticks` ``', $escaped);
    }

    public function testEscapeEmptyCode(): void
    {
        $code    = '';
        $escaped = $this->escaper->escape($code);

        $this->assertEquals('``', $escaped);
    }

    public function testEscapeCodeWithMultipleConsecutiveBackticks(): void
    {
        $code    = 'code with ``` three backticks';
        $escaped = $this->escaper->escape($code);

        $this->assertEquals('````code with ``` three backticks````', $escaped);
    }
}
