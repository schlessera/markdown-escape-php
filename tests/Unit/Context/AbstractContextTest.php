<?php

declare(strict_types=1);

namespace Markdown\Escape\Tests\Unit\Context;

use Markdown\Escape\Context\AbstractContext;
use Markdown\Escape\Tests\TestCase;

class AbstractContextTest extends TestCase
{
    public function testRequiresEscaping(): void
    {
        $context = new class ('test', ['option1' => 'value1']) extends AbstractContext {
            protected function configure(): void
            {
                $this->escapingTypes = ['type1', 'type2', 'type3'];
            }
        };

        $this->assertTrue($context->requiresEscaping('type1'));
        $this->assertTrue($context->requiresEscaping('type2'));
        $this->assertTrue($context->requiresEscaping('type3'));
        $this->assertFalse($context->requiresEscaping('type4'));
        $this->assertFalse($context->requiresEscaping('unknown'));
    }

    public function testGetOptions(): void
    {
        $options = ['option1' => 'value1', 'option2' => 'value2'];
        $context = new class ('test', $options) extends AbstractContext {
            protected function configure(): void
            {
                $this->escapingTypes = [];
            }
        };

        $this->assertEquals($options, $context->getOptions());
    }

    public function testEmptyOptions(): void
    {
        $context = new class ('test') extends AbstractContext {
            protected function configure(): void
            {
                $this->escapingTypes = ['test'];
            }
        };

        $this->assertEquals([], $context->getOptions());
    }
}
