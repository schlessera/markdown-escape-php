<?php

declare(strict_types=1);

namespace Markdown\Escape\Tests\Unit\Template\Loader;

use Markdown\Escape\Exception\TemplateNotFoundException;
use Markdown\Escape\Template\Loader\ArrayTemplateLoader;
use Markdown\Escape\Template\Template;
use Markdown\Escape\Tests\TestCase;

class ArrayTemplateLoaderTest extends TestCase
{
    public function testConstructorWithTemplates(): void
    {
        $templates = [
            'hello'   => 'Hello, <?= $name ?>!',
            'goodbye' => 'Goodbye!',
        ];

        $loader = new ArrayTemplateLoader($templates);

        $this->assertTrue($loader->exists('hello'));
        $this->assertTrue($loader->exists('goodbye'));
    }

    public function testLoadStringTemplate(): void
    {
        $loader = new ArrayTemplateLoader(['test' => 'Test content']);

        $template = $loader->load('test');

        $this->assertInstanceOf(Template::class, $template);
        $this->assertSame('test', $template->getName());
        $this->assertSame('Test content', $template->getContent());
        $this->assertSame('array', $template->getMetadataValue('source'));
        $this->assertSame(ArrayTemplateLoader::class, $template->getMetadataValue('loader'));
    }

    public function testLoadTemplateInstance(): void
    {
        $templateInstance = new Template('custom', 'Custom content', ['custom' => true]);
        $loader           = new ArrayTemplateLoader(['test' => $templateInstance]);

        $template = $loader->load('test');

        $this->assertSame($templateInstance, $template);
        $this->assertTrue($template->getMetadataValue('custom'));
    }

    public function testLoadCachesStringAsTemplate(): void
    {
        $loader = new ArrayTemplateLoader(['test' => 'Test content']);

        $template1 = $loader->load('test');
        $template2 = $loader->load('test');

        $this->assertSame($template1, $template2);
        $this->assertInstanceOf(Template::class, $template1);
    }

    public function testLoadThrowsExceptionWhenNotFound(): void
    {
        $loader = new ArrayTemplateLoader();

        $this->expectException(TemplateNotFoundException::class);
        $this->expectExceptionMessage('Template "nonexistent" not found in paths: memory');

        $loader->load('nonexistent');
    }

    public function testExists(): void
    {
        $loader = new ArrayTemplateLoader(['test' => 'content']);

        $this->assertTrue($loader->exists('test'));
        $this->assertFalse($loader->exists('nonexistent'));
    }

    public function testGetTemplateNames(): void
    {
        $loader = new ArrayTemplateLoader([
            'template1'           => 'content1',
            'template2'           => 'content2',
            'namespace/template3' => 'content3',
        ]);

        $names = $loader->getTemplateNames();

        $this->assertSame(['template1', 'template2', 'namespace/template3'], $names);
    }

    public function testAddTemplate(): void
    {
        $loader = new ArrayTemplateLoader();

        $this->assertFalse($loader->exists('new'));

        $loader->addTemplate('new', 'New content');

        $this->assertTrue($loader->exists('new'));

        $template = $loader->load('new');
        $this->assertSame('New content', $template->getContent());
    }

    public function testAddTemplateWithInstance(): void
    {
        $loader           = new ArrayTemplateLoader();
        $templateInstance = new Template('custom', 'Custom');

        $loader->addTemplate('custom', $templateInstance);

        $this->assertSame($templateInstance, $loader->load('custom'));
    }

    public function testAddTemplateThrowsExceptionForInvalidType(): void
    {
        $loader = new ArrayTemplateLoader();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Template must be a string or implement TemplateInterface');

        $loader->addTemplate('invalid', 123); // @phpstan-ignore-line
    }

    public function testRemoveTemplate(): void
    {
        $loader = new ArrayTemplateLoader(['test' => 'content']);

        $this->assertTrue($loader->exists('test'));

        $loader->removeTemplate('test');

        $this->assertFalse($loader->exists('test'));
    }

    public function testClear(): void
    {
        $loader = new ArrayTemplateLoader([
            'template1' => 'content1',
            'template2' => 'content2',
        ]);

        $this->assertCount(2, $loader->getTemplateNames());

        $loader->clear();

        $this->assertCount(0, $loader->getTemplateNames());
    }

    public function testGetTemplates(): void
    {
        $templates = [
            'template1' => 'content1',
            'template2' => new Template('t2', 'content2'),
        ];

        $loader = new ArrayTemplateLoader($templates);

        $retrieved = $loader->getTemplates();

        $this->assertArrayHasKey('template1', $retrieved);
        $this->assertArrayHasKey('template2', $retrieved);

        // First should still be string
        $this->assertIsString($retrieved['template1']);

        // Second should be Template instance
        $this->assertInstanceOf(Template::class, $retrieved['template2']);
    }

    public function testSetTemplates(): void
    {
        $loader = new ArrayTemplateLoader(['old' => 'old content']);

        $newTemplates = [
            'new1' => 'content1',
            'new2' => 'content2',
        ];

        $loader->setTemplates($newTemplates);

        $this->assertFalse($loader->exists('old'));
        $this->assertTrue($loader->exists('new1'));
        $this->assertTrue($loader->exists('new2'));
    }

    public function testAddPath(): void
    {
        $loader = new ArrayTemplateLoader();

        $loader->addPath('namespace', [
            'template1' => 'content1',
            'template2' => 'content2',
        ]);

        $this->assertTrue($loader->exists('namespace/template1'));
        $this->assertTrue($loader->exists('namespace/template2'));
    }

    public function testAddPathWithEmptyNamespace(): void
    {
        $loader = new ArrayTemplateLoader();

        $loader->addPath('', [
            'template1' => 'content1',
        ]);

        $this->assertTrue($loader->exists('template1'));
    }

    public function testPriority(): void
    {
        $loader = new ArrayTemplateLoader();

        // Default priority is higher than file loader
        $this->assertSame(10, $loader->getPriority());

        $loader->setPriority(20);

        $this->assertSame(20, $loader->getPriority());
    }
}
