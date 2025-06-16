<?php

declare(strict_types=1);

namespace Markdown\Escape\Tests\Unit\Escaper;

use Markdown\Escape\Context\GeneralContentContext;
use Markdown\Escape\Contract\ContextInterface;
use Markdown\Escape\Contract\DialectInterface;
use Markdown\Escape\Dialect\CommonMarkDialect;
use Markdown\Escape\Dialect\GitHubFlavoredMarkdownDialect;
use Markdown\Escape\Escaper\AbstractEscaper;
use Markdown\Escape\Tests\TestCase;

class AbstractEscaperTest extends TestCase
{
    public function testGetContext(): void
    {
        $context = new GeneralContentContext();
        $dialect = new CommonMarkDialect();

        $escaper = new class ($context, $dialect) extends AbstractEscaper {};

        $this->assertSame($context, $escaper->getContext());
    }

    public function testSupportsDialect(): void
    {
        $context = new GeneralContentContext();
        $dialect = new CommonMarkDialect();

        $escaper = new class ($context, $dialect) extends AbstractEscaper {};

        $this->assertTrue($escaper->supportsDialect($dialect));
        $this->assertTrue($escaper->supportsDialect(new CommonMarkDialect()));
        $this->assertFalse($escaper->supportsDialect(new GitHubFlavoredMarkdownDialect()));
    }

    public function testEscapeWithNoSpecialCharacters(): void
    {
        $context = $this->createMock(ContextInterface::class);
        $dialect = $this->createMock(DialectInterface::class);

        // Make dialect return empty special characters
        $dialect->method('getSpecialCharacters')->willReturn([]);

        $escaper = new class ($context, $dialect) extends AbstractEscaper {};

        $content = 'This has no special characters to escape';
        $this->assertEquals($content, $escaper->escape($content));
    }

    public function testPostProcessIsCalledDuringEscape(): void
    {
        $context = new GeneralContentContext();
        $dialect = new CommonMarkDialect();

        $escaper = new class ($context, $dialect) extends AbstractEscaper {
            /**
             * @var bool
             */
            public $postProcessCalled = false;

            protected function postProcess(string $content): string
            {
                $this->postProcessCalled = true;

                return parent::postProcess($content);
            }
        };

        $escaper->escape('test content');

        $this->assertTrue($escaper->postProcessCalled);
    }
}
