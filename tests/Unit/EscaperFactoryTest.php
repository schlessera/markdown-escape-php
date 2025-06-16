<?php

declare(strict_types=1);

namespace Markdown\Escape\Tests\Unit;

use Markdown\Escape\Context\GeneralContentContext;
use Markdown\Escape\Contract\ContextInterface;
use Markdown\Escape\Contract\DialectInterface;
use Markdown\Escape\Contract\EscaperInterface;
use Markdown\Escape\Dialect\CommonMarkDialect;
use Markdown\Escape\Escaper\GeneralContentEscaper;
use Markdown\Escape\EscaperFactory;
use Markdown\Escape\Exception\UnsupportedContextException;
use Markdown\Escape\Exception\UnsupportedDialectException;
use Markdown\Escape\Tests\TestCase;

class TestUnsupportedDialectEscaper extends GeneralContentEscaper
{
    public function supportsDialect(DialectInterface $dialect): bool
    {
        return false;
    }
}

class EscaperFactoryTest extends TestCase
{
    /**
     * @var EscaperFactory
     */
    private $factory;

    protected function setUp(): void
    {
        $this->factory = new EscaperFactory();
    }

    public function testCreateEscaperReturnsNewInstance(): void
    {
        $context = new GeneralContentContext();
        $dialect = new CommonMarkDialect();

        $escaper = $this->factory->createEscaper($context, $dialect);

        $this->assertInstanceOf(GeneralContentEscaper::class, $escaper);
    }

    public function testCreateEscaperReturnsCachedInstance(): void
    {
        $context = new GeneralContentContext();
        $dialect = new CommonMarkDialect();

        $escaper1 = $this->factory->createEscaper($context, $dialect);
        $escaper2 = $this->factory->createEscaper($context, $dialect);

        $this->assertSame($escaper1, $escaper2);
    }

    public function testCreateEscaperThrowsExceptionForUnsupportedContext(): void
    {
        $context = $this->createMock(ContextInterface::class);
        $context->method('getName')->willReturn('unsupported');

        $dialect = new CommonMarkDialect();

        $this->expectException(UnsupportedContextException::class);
        $this->expectExceptionMessage('No escaper available for context "unsupported"');

        $this->factory->createEscaper($context, $dialect);
    }

    public function testRegisterEscaper(): void
    {
        $contextName = 'custom';
        $dialectName = 'custom_dialect';

        $escaper = $this->createMock(EscaperInterface::class);

        $this->factory->registerEscaper($contextName, $dialectName, $escaper);

        // Now create context and dialect mocks that return those names
        $context = $this->createMock(ContextInterface::class);
        $context->method('getName')->willReturn($contextName);

        $dialect = $this->createMock(DialectInterface::class);
        $dialect->method('getName')->willReturn($dialectName);

        $result = $this->factory->createEscaper($context, $dialect);

        $this->assertSame($escaper, $result);
    }

    public function testHasEscaper(): void
    {
        $context = new GeneralContentContext();
        $dialect = new CommonMarkDialect();

        $this->assertTrue($this->factory->hasEscaper($context, $dialect));

        // Test with unsupported context
        $unsupportedContext = $this->createMock(ContextInterface::class);
        $unsupportedContext->method('getName')->willReturn('unsupported');

        $this->assertFalse($this->factory->hasEscaper($unsupportedContext, $dialect));

        // Test with registered escaper
        $customContext = $this->createMock(ContextInterface::class);
        $customContext->method('getName')->willReturn('custom');

        $customDialect = $this->createMock(DialectInterface::class);
        $customDialect->method('getName')->willReturn('custom_dialect');

        $escaper = $this->createMock(EscaperInterface::class);
        $this->factory->registerEscaper('custom', 'custom_dialect', $escaper);

        $this->assertTrue($this->factory->hasEscaper($customContext, $customDialect));
    }

    public function testRegisterDefaultEscaperClass(): void
    {
        $contextName  = 'new_context';
        $escaperClass = GeneralContentEscaper::class;

        $this->factory->registerDefaultEscaperClass($contextName, $escaperClass);

        $context = $this->createMock(ContextInterface::class);
        $context->method('getName')->willReturn($contextName);

        $dialect = new CommonMarkDialect();

        $escaper = $this->factory->createEscaper($context, $dialect);

        $this->assertInstanceOf($escaperClass, $escaper);
    }

    public function testCreateEscaperThrowsExceptionForInvalidEscaperClass(): void
    {
        // This test checks the code path where the created object doesn't implement EscaperInterface
        // We need to use reflection to modify the private property

        $factory    = new EscaperFactory();
        $reflection = new \ReflectionClass($factory);
        $property   = $reflection->getProperty('defaultEscaperClasses');
        $property->setAccessible(true);

        $defaultClasses                              = $property->getValue($factory);
        $defaultClasses[GeneralContentContext::NAME] = \stdClass::class;
        $property->setValue($factory, $defaultClasses);

        $context = new GeneralContentContext();
        $dialect = new CommonMarkDialect();

        $this->expectException(UnsupportedContextException::class);
        $this->expectExceptionMessage('Class "stdClass" must implement EscaperInterface');

        $factory->createEscaper($context, $dialect);
    }

    public function testCreateEscaperThrowsExceptionForUnsupportedDialect(): void
    {
        $factory = new EscaperFactory();
        $factory->registerDefaultEscaperClass('test_context', TestUnsupportedDialectEscaper::class);

        $context = $this->createMock(ContextInterface::class);
        $context->method('getName')->willReturn('test_context');

        $dialect = new CommonMarkDialect();

        $this->expectException(UnsupportedDialectException::class);
        $this->expectExceptionMessage('Dialect "commonmark" is not supported by escaper for context "test_context"');

        $factory->createEscaper($context, $dialect);
    }
}
