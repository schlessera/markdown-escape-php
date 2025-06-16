<?php

declare(strict_types=1);

namespace Markdown\Escape\Tests\Unit\Template\Loader;

use Markdown\Escape\Exception\TemplateNotFoundException;
use Markdown\Escape\Template\Loader\FileTemplateLoader;
use Markdown\Escape\Tests\TestCase;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

class FileTemplateLoaderTest extends TestCase
{
    /**
     * @var vfsStreamDirectory
     */
    private $root;

    /**
     * @var string
     */
    private $rootPath;

    protected function setUp(): void
    {
        $this->root = vfsStream::setup('templates', null, [
            'default' => [
                'hello.php' => 'Hello, <?= $name ?>!',
                'nested'    => [
                    'template.php' => 'Nested template',
                ],
            ],
            'custom' => [
                'special.php' => 'Special template',
            ],
            'other.txt' => 'Not a template',
        ]);

        $this->rootPath = vfsStream::url('templates');
    }

    public function testConstructorWithString(): void
    {
        $loader = new FileTemplateLoader($this->rootPath . '/default');

        $this->assertTrue($loader->exists('hello'));
    }

    public function testConstructorWithArray(): void
    {
        $loader = new FileTemplateLoader([
            'default' => $this->rootPath . '/default',
            'custom'  => $this->rootPath . '/custom',
        ]);

        $this->assertTrue($loader->exists('default/hello'));
        $this->assertTrue($loader->exists('custom/special'));
    }

    public function testConstructorWithIndexedArray(): void
    {
        $loader = new FileTemplateLoader([
            $this->rootPath . '/default',
            $this->rootPath . '/custom',
        ]);

        $this->assertTrue($loader->exists('hello'));
        $this->assertTrue($loader->exists('special'));
    }

    public function testLoad(): void
    {
        $loader = new FileTemplateLoader($this->rootPath . '/default');

        $template = $loader->load('hello');

        $this->assertSame('hello', $template->getName());
        $this->assertSame('Hello, <?= $name ?>!', $template->getContent());
        $this->assertArrayHasKey('file', $template->getMetadata());
        $this->assertArrayHasKey('loader', $template->getMetadata());
    }

    public function testLoadWithExtension(): void
    {
        $loader = new FileTemplateLoader($this->rootPath . '/default');

        $template = $loader->load('hello.php');

        $this->assertSame('hello.php', $template->getName());
        $this->assertSame('Hello, <?= $name ?>!', $template->getContent());
    }

    public function testLoadNested(): void
    {
        $loader = new FileTemplateLoader($this->rootPath . '/default');

        $template = $loader->load('nested/template');

        $this->assertSame('nested/template', $template->getName());
        $this->assertSame('Nested template', $template->getContent());
    }

    public function testLoadWithNamespace(): void
    {
        $loader = new FileTemplateLoader([
            'default' => $this->rootPath . '/default',
            'custom'  => $this->rootPath . '/custom',
        ]);

        $template = $loader->load('custom/special');

        $this->assertSame('custom/special', $template->getName());
        $this->assertSame('Special template', $template->getContent());
    }

    public function testLoadCachesTemplate(): void
    {
        $loader = new FileTemplateLoader($this->rootPath . '/default');

        $template1 = $loader->load('hello');
        $template2 = $loader->load('hello');

        $this->assertSame($template1, $template2);
    }

    public function testLoadThrowsExceptionWhenNotFound(): void
    {
        $loader = new FileTemplateLoader($this->rootPath . '/default');

        $this->expectException(TemplateNotFoundException::class);
        $this->expectExceptionMessage('Template "nonexistent" not found');

        $loader->load('nonexistent');
    }

    public function testExists(): void
    {
        $loader = new FileTemplateLoader($this->rootPath . '/default');

        $this->assertTrue($loader->exists('hello'));
        $this->assertTrue($loader->exists('hello.php'));
        $this->assertTrue($loader->exists('nested/template'));
        $this->assertFalse($loader->exists('nonexistent'));
    }

    public function testGetTemplateNames(): void
    {
        $loader = new FileTemplateLoader($this->rootPath . '/default');

        $names = $loader->getTemplateNames();

        $this->assertContains('hello', $names);
        $this->assertContains('nested/template', $names);
        $this->assertCount(2, $names);
    }

    public function testGetTemplateNamesWithNamespaces(): void
    {
        $loader = new FileTemplateLoader([
            'default' => $this->rootPath . '/default',
            'custom'  => $this->rootPath . '/custom',
        ]);

        $names = $loader->getTemplateNames();

        $this->assertContains('default/hello', $names);
        $this->assertContains('default/nested/template', $names);
        $this->assertContains('custom/special', $names);
    }

    public function testAddPath(): void
    {
        $loader = new FileTemplateLoader();

        $this->assertFalse($loader->exists('hello'));

        $loader->addPath('', $this->rootPath . '/default');

        $this->assertTrue($loader->exists('hello'));
    }

    public function testAddPathWithNamespace(): void
    {
        $loader = new FileTemplateLoader();

        $loader->addPath('custom', $this->rootPath . '/custom');

        $this->assertTrue($loader->exists('custom/special'));
    }

    public function testAddPathClearsCache(): void
    {
        $loader = new FileTemplateLoader($this->rootPath . '/default');

        $template1 = $loader->load('hello');

        $loader->addPath('custom', $this->rootPath . '/custom');

        $template2 = $loader->load('hello');

        // Should be different instances due to cache clear
        $this->assertNotSame($template1, $template2);
    }

    public function testPriority(): void
    {
        $loader = new FileTemplateLoader();

        $this->assertSame(0, $loader->getPriority());

        $loader->setPriority(10);

        $this->assertSame(10, $loader->getPriority());
    }

    public function testCustomExtension(): void
    {
        // Create the file using file_put_contents which is more reliable with vfsStream
        file_put_contents($this->rootPath . '/default/template.phtml', 'PHTML template');

        $loader = new FileTemplateLoader($this->rootPath . '/default', '.phtml');

        $template = $loader->load('template');

        $this->assertSame('PHTML template', $template->getContent());
    }

    public function testFallbackToOtherNamespaces(): void
    {
        $loader = new FileTemplateLoader([
            'ns1' => $this->rootPath . '/custom',
            'ns2' => $this->rootPath . '/default',
        ]);

        // Template exists in ns2 but we don't specify namespace
        $template = $loader->load('hello');

        $this->assertSame('hello', $template->getName());
        $this->assertSame('Hello, <?= $name ?>!', $template->getContent());
    }

    public function testNoFallbackWhenNamespaceSpecified(): void
    {
        $loader = new FileTemplateLoader([
            'ns1' => $this->rootPath . '/custom',
            'ns2' => $this->rootPath . '/default',
        ]);

        $this->expectException(TemplateNotFoundException::class);

        // Template exists in ns2 but we're looking in ns1
        $loader->load('ns1/hello');
    }

    public function testAddPathWithArray(): void
    {
        $extraDir = vfsStream::newDirectory('extra');
        $this->root->addChild($extraDir);
        vfsStream::newFile('extra.php')
            ->at($extraDir)
            ->setContent('Extra template');

        $loader = new FileTemplateLoader();

        $loader->addPath('', [
            $this->rootPath . '/default',
            $this->rootPath . '/extra',
        ]);

        $this->assertTrue($loader->exists('hello'));
        $this->assertTrue($loader->exists('extra'));
    }

    public function testInvalidPath(): void
    {
        $loader = new FileTemplateLoader();

        // Should not throw, just ignore invalid path
        $loader->addPath('', '/nonexistent/path');

        $this->assertSame([], $loader->getTemplateNames());
    }
}
