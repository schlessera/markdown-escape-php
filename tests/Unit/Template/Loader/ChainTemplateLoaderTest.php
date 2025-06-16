<?php

declare(strict_types=1);

namespace Markdown\Escape\Tests\Unit\Template\Loader;

use Markdown\Escape\Exception\TemplateNotFoundException;
use Markdown\Escape\Template\Loader\ArrayTemplateLoader;
use Markdown\Escape\Template\Loader\ChainTemplateLoader;
use Markdown\Escape\Template\Loader\FileTemplateLoader;
use Markdown\Escape\Template\Template;
use Markdown\Escape\Tests\TestCase;

class ChainTemplateLoaderTest extends TestCase
{
    public function testConstructorWithLoaders(): void
    {
        $loader1 = new ArrayTemplateLoader(['template1' => 'content1']);
        $loader2 = new ArrayTemplateLoader(['template2' => 'content2']);

        $chain = new ChainTemplateLoader([$loader1, $loader2]);

        $this->assertTrue($chain->exists('template1'));
        $this->assertTrue($chain->exists('template2'));
    }

    public function testLoad(): void
    {
        $loader1 = new ArrayTemplateLoader(['template1' => 'content1']);
        $loader2 = new ArrayTemplateLoader(['template2' => 'content2']);

        $chain = new ChainTemplateLoader([$loader1, $loader2]);

        $template1 = $chain->load('template1');
        $this->assertSame('content1', $template1->getContent());

        $template2 = $chain->load('template2');
        $this->assertSame('content2', $template2->getContent());
    }

    public function testLoadRespectsPriority(): void
    {
        $highPriority = new ArrayTemplateLoader(['shared' => 'high priority']);
        $highPriority->setPriority(20);

        $lowPriority = new ArrayTemplateLoader(['shared' => 'low priority']);
        $lowPriority->setPriority(10);

        $chain = new ChainTemplateLoader([$lowPriority, $highPriority]);

        $template = $chain->load('shared');
        $this->assertSame('high priority', $template->getContent());
    }

    public function testLoadThrowsExceptionWhenNotFound(): void
    {
        $loader1 = new ArrayTemplateLoader(['template1' => 'content1']);
        $loader2 = new ArrayTemplateLoader(['template2' => 'content2']);

        $chain = new ChainTemplateLoader([$loader1, $loader2]);

        $this->expectException(TemplateNotFoundException::class);
        $this->expectExceptionMessage('Template "nonexistent" not found');

        $chain->load('nonexistent');
    }

    public function testLoadCombinesSearchPaths(): void
    {
        $loader1 = new ArrayTemplateLoader();
        $loader2 = new ArrayTemplateLoader();

        $chain = new ChainTemplateLoader([$loader1, $loader2]);

        try {
            $chain->load('nonexistent');
        } catch (TemplateNotFoundException $e) {
            $this->assertSame(['memory', 'memory'], $e->getSearchPaths());
        }
    }

    public function testExists(): void
    {
        $loader1 = new ArrayTemplateLoader(['template1' => 'content1']);
        $loader2 = new ArrayTemplateLoader(['template2' => 'content2']);

        $chain = new ChainTemplateLoader([$loader1, $loader2]);

        $this->assertTrue($chain->exists('template1'));
        $this->assertTrue($chain->exists('template2'));
        $this->assertFalse($chain->exists('nonexistent'));
    }

    public function testGetTemplateNames(): void
    {
        $loader1 = new ArrayTemplateLoader(['a' => 'a', 'b' => 'b']);
        $loader2 = new ArrayTemplateLoader(['b' => 'b2', 'c' => 'c']);

        $chain = new ChainTemplateLoader([$loader1, $loader2]);

        $names = $chain->getTemplateNames();

        $this->assertContains('a', $names);
        $this->assertContains('b', $names);
        $this->assertContains('c', $names);
        $this->assertCount(3, $names); // Duplicates removed
    }

    public function testAddLoader(): void
    {
        $chain = new ChainTemplateLoader();

        $this->assertFalse($chain->exists('test'));

        $loader = new ArrayTemplateLoader(['test' => 'content']);
        $chain->addLoader($loader);

        $this->assertTrue($chain->exists('test'));
    }

    public function testAddLoaderPreventsCircularReference(): void
    {
        $chain = new ChainTemplateLoader();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot add chain loader to itself');

        $chain->addLoader($chain);
    }

    public function testRemoveLoader(): void
    {
        $loader = new ArrayTemplateLoader(['test' => 'content']);
        $chain  = new ChainTemplateLoader([$loader]);

        $this->assertTrue($chain->exists('test'));

        $chain->removeLoader($loader);

        $this->assertFalse($chain->exists('test'));
    }

    public function testGetLoaders(): void
    {
        $loader1 = new ArrayTemplateLoader();
        $loader2 = new ArrayTemplateLoader();

        $chain = new ChainTemplateLoader([$loader1, $loader2]);

        $loaders = $chain->getLoaders();

        $this->assertCount(2, $loaders);
        $this->assertSame($loader1, $loaders[0]);
        $this->assertSame($loader2, $loaders[1]);
    }

    public function testClear(): void
    {
        $loader = new ArrayTemplateLoader(['test' => 'content']);
        $chain  = new ChainTemplateLoader([$loader]);

        $this->assertTrue($chain->exists('test'));

        $chain->clear();

        $this->assertFalse($chain->exists('test'));
        $this->assertCount(0, $chain->getLoaders());
    }

    public function testAddPath(): void
    {
        $fileLoader = $this->createMock(FileTemplateLoader::class);
        $fileLoader->expects($this->once())
            ->method('addPath')
            ->with('namespace', '/path/to/templates');

        $arrayLoader = new ArrayTemplateLoader();

        $chain = new ChainTemplateLoader([$fileLoader, $arrayLoader]);

        $chain->addPath('namespace', '/path/to/templates');
    }

    public function testPriority(): void
    {
        $chain = new ChainTemplateLoader();

        $this->assertSame(0, $chain->getPriority());

        $chain->setPriority(15);

        $this->assertSame(15, $chain->getPriority());
    }

    public function testComplexPriorityScenario(): void
    {
        // Create loaders with different priorities
        $loader1 = new ArrayTemplateLoader(['template' => 'priority 5']);
        $loader1->setPriority(5);

        $loader2 = new ArrayTemplateLoader(['template' => 'priority 10']);
        $loader2->setPriority(10);

        $loader3 = new ArrayTemplateLoader(['template' => 'priority 1']);
        $loader3->setPriority(1);

        $loader4 = new ArrayTemplateLoader(['unique' => 'unique content']);
        $loader4->setPriority(0);

        // Add in random order
        $chain = new ChainTemplateLoader([$loader3, $loader1, $loader4, $loader2]);

        // Should load from highest priority (10)
        $template = $chain->load('template');
        $this->assertSame('priority 10', $template->getContent());

        // Unique template should still be found
        $unique = $chain->load('unique');
        $this->assertSame('unique content', $unique->getContent());
    }

    public function testEmptyChain(): void
    {
        $chain = new ChainTemplateLoader();

        $this->assertFalse($chain->exists('any'));
        $this->assertSame([], $chain->getTemplateNames());

        $this->expectException(TemplateNotFoundException::class);
        $chain->load('any');
    }
}
