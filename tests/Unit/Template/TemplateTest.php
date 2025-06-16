<?php

declare(strict_types=1);

namespace Markdown\Escape\Tests\Unit\Template;

use Markdown\Escape\Template\Template;
use Markdown\Escape\Tests\TestCase;

class TemplateTest extends TestCase
{
    public function testConstructor(): void
    {
        $template = new Template('test', 'content', ['key' => 'value']);

        $this->assertSame('test', $template->getName());
        $this->assertSame('content', $template->getContent());
        $this->assertSame(['key' => 'value'], $template->getMetadata());
    }

    public function testGetName(): void
    {
        $template = new Template('my-template', 'content');

        $this->assertSame('my-template', $template->getName());
    }

    public function testGetContent(): void
    {
        $content  = '<?= $variable ?>';
        $template = new Template('test', $content);

        $this->assertSame($content, $template->getContent());
    }

    public function testGetMetadata(): void
    {
        $metadata = [
            'engine'  => 'php',
            'version' => '1.0',
            'author'  => 'Test',
        ];

        $template = new Template('test', 'content', $metadata);

        $this->assertSame($metadata, $template->getMetadata());
    }

    public function testHasMetadata(): void
    {
        $template = new Template('test', 'content', [
            'engine'  => 'php',
            'version' => '1.0',
        ]);

        $this->assertTrue($template->hasMetadata('engine'));
        $this->assertTrue($template->hasMetadata('version'));
        $this->assertFalse($template->hasMetadata('author'));
        $this->assertFalse($template->hasMetadata('nonexistent'));
    }

    public function testGetMetadataValue(): void
    {
        $template = new Template('test', 'content', [
            'engine'  => 'php',
            'version' => '1.0',
            'nested'  => ['key' => 'value'],
        ]);

        $this->assertSame('php', $template->getMetadataValue('engine'));
        $this->assertSame('1.0', $template->getMetadataValue('version'));
        $this->assertSame(['key' => 'value'], $template->getMetadataValue('nested'));
        $this->assertNull($template->getMetadataValue('nonexistent'));
        $this->assertSame('default', $template->getMetadataValue('nonexistent', 'default'));
    }

    public function testEmptyMetadata(): void
    {
        $template = new Template('test', 'content');

        $this->assertSame([], $template->getMetadata());
        $this->assertFalse($template->hasMetadata('any'));
        $this->assertNull($template->getMetadataValue('any'));
    }
}
