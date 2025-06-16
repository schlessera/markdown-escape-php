<?php

declare(strict_types=1);

namespace Markdown\Escape\Tests\Unit\Template;

use Markdown\Escape\Contract\EscaperInterface;
use Markdown\Escape\MarkdownEscape;
use Markdown\Escape\Template\TemplateContext;
use Markdown\Escape\Tests\TestCase;

class TemplateContextTest extends TestCase
{
    public function testConstructor(): void
    {
        $variables = ['foo' => 'bar', 'num' => 42];
        $context   = new TemplateContext($variables);

        $this->assertSame($variables, $context->all());
    }

    public function testSetAndGet(): void
    {
        $context = new TemplateContext();

        $context->set('key', 'value');
        $this->assertSame('value', $context->get('key'));

        $context->set('number', 123);
        $this->assertSame(123, $context->get('number'));

        $context->set('array', ['a', 'b', 'c']);
        $this->assertSame(['a', 'b', 'c'], $context->get('array'));
    }

    public function testGetWithDefault(): void
    {
        $context = new TemplateContext(['existing' => 'value']);

        $this->assertSame('value', $context->get('existing'));
        $this->assertNull($context->get('nonexistent'));
        $this->assertSame('default', $context->get('nonexistent', 'default'));
    }

    public function testHas(): void
    {
        $context = new TemplateContext(['key' => 'value', 'null' => null]);

        $this->assertTrue($context->has('key'));
        $this->assertTrue($context->has('null')); // null value still exists
        $this->assertFalse($context->has('nonexistent'));
    }

    public function testRemove(): void
    {
        $context = new TemplateContext(['a' => 1, 'b' => 2, 'c' => 3]);

        $context->remove('b');

        $this->assertSame(['a' => 1, 'c' => 3], $context->all());
        $this->assertFalse($context->has('b'));

        // Removing non-existent key should not throw
        $context->remove('nonexistent');
    }

    public function testAll(): void
    {
        $variables = ['foo' => 'bar', 'num' => 42, 'array' => [1, 2, 3]];
        $context   = new TemplateContext($variables);

        $this->assertSame($variables, $context->all());
    }

    public function testMergeWithArray(): void
    {
        $context = new TemplateContext(['a' => 1, 'b' => 2]);

        $context->merge(['b' => 20, 'c' => 30]);

        $this->assertSame(['a' => 1, 'b' => 20, 'c' => 30], $context->all());
    }

    public function testMergeWithContext(): void
    {
        $context1 = new TemplateContext(['a' => 1, 'b' => 2]);
        $context2 = new TemplateContext(['b' => 20, 'c' => 30]);

        $context1->merge($context2);

        $this->assertSame(['a' => 1, 'b' => 20, 'c' => 30], $context1->all());
    }

    public function testFluentInterface(): void
    {
        $context = new TemplateContext();

        $result = $context
            ->set('a', 1)
            ->set('b', 2)
            ->remove('a')
            ->merge(['c' => 3]);

        $this->assertSame($context, $result);
        $this->assertSame(['b' => 2, 'c' => 3], $context->all());
    }

    public function testAddAndGetEscaper(): void
    {
        $context = new TemplateContext();
        $escaper = $this->createMock(EscaperInterface::class);

        $context->addEscaper('custom', $escaper);

        $this->assertSame($escaper, $context->getEscaper('custom'));
        $this->assertNull($context->getEscaper('nonexistent'));
    }

    public function testMarkdownEscape(): void
    {
        $context  = new TemplateContext();
        $markdown = new MarkdownEscape();

        $this->assertNull($context->getMarkdownEscape());

        $context->setMarkdownEscape($markdown);

        $this->assertSame($markdown, $context->getMarkdownEscape());
    }

    public function testSetMarkdownEscapeAddsCommonEscapers(): void
    {
        $context  = new TemplateContext();
        $markdown = new MarkdownEscape();

        $context->setMarkdownEscape($markdown);

        // Check that common escapers were added
        $this->assertInstanceOf(EscaperInterface::class, $context->getEscaper('content'));
        $this->assertInstanceOf(EscaperInterface::class, $context->getEscaper('general'));
        $this->assertInstanceOf(EscaperInterface::class, $context->getEscaper('url'));
        $this->assertInstanceOf(EscaperInterface::class, $context->getEscaper('inlineCode'));
        $this->assertInstanceOf(EscaperInterface::class, $context->getEscaper('inline'));
        $this->assertInstanceOf(EscaperInterface::class, $context->getEscaper('codeBlock'));
        $this->assertInstanceOf(EscaperInterface::class, $context->getEscaper('code'));
    }

    public function testStaticCreate(): void
    {
        $variables = ['foo' => 'bar'];
        $context   = TemplateContext::create($variables);

        $this->assertInstanceOf(TemplateContext::class, $context);
        $this->assertSame($variables, $context->all());
    }

    public function testMergeWithContextIncludesEscapers(): void
    {
        $context1 = new TemplateContext();
        $context2 = new TemplateContext();

        $escaper = $this->createMock(EscaperInterface::class);
        $context2->addEscaper('custom', $escaper);

        $markdown = new MarkdownEscape();
        $context2->setMarkdownEscape($markdown);

        $context1->merge($context2);

        // Escapers should not be merged through variables
        $this->assertNull($context1->getEscaper('custom'));

        // Markdown escape should be merged if not set
        $this->assertSame($markdown, $context1->getMarkdownEscape());
    }

    public function testMergeDoesNotOverrideMarkdownEscape(): void
    {
        $context1 = new TemplateContext();
        $context2 = new TemplateContext();

        $markdown1 = new MarkdownEscape();
        $markdown2 = new MarkdownEscape();

        $context1->setMarkdownEscape($markdown1);
        $context2->setMarkdownEscape($markdown2);

        $context1->merge($context2);

        // Should keep the original markdown escape
        $this->assertSame($markdown1, $context1->getMarkdownEscape());
    }
}
