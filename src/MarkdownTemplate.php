<?php

declare(strict_types=1);

namespace Markdown\Escape;

use Markdown\Escape\Contract\DialectInterface;
use Markdown\Escape\Contract\TemplateRendererInterface;
use Markdown\Escape\Dialect\CommonMarkDialect;
use Markdown\Escape\Dialect\GitHubFlavoredMarkdownDialect;
use Markdown\Escape\Template\Engine\PhpTemplateEngine;
use Markdown\Escape\Template\Loader\ArrayTemplateLoader;
use Markdown\Escape\Template\Loader\ChainTemplateLoader;
use Markdown\Escape\Template\Loader\FileTemplateLoader;
use Markdown\Escape\Template\TemplateRenderer;

/**
 * Facade for template rendering with Markdown escaping.
 */
class MarkdownTemplate
{
    /**
     * @var TemplateRendererInterface
     */
    private $renderer;

    /**
     * @var MarkdownEscape
     */
    private $markdownEscape;

    /**
     * @param DialectInterface|null $dialect
     */
    public function __construct(?DialectInterface $dialect = null)
    {
        $this->markdownEscape = new MarkdownEscape($dialect);
        $this->renderer       = $this->createDefaultRenderer();
    }

    /**
     * Create instance for CommonMark.
     *
     * @return self
     */
    public static function commonMark(): self
    {
        return new self(new CommonMarkDialect());
    }

    /**
     * Create instance for GitHub Flavored Markdown.
     *
     * @return self
     */
    public static function gfm(): self
    {
        return new self(new GitHubFlavoredMarkdownDialect());
    }

    /**
     * Create instance with custom dialect.
     *
     * @param DialectInterface $dialect
     *
     * @return self
     */
    public static function withDialect(DialectInterface $dialect): self
    {
        return new self($dialect);
    }

    /**
     * Render a template.
     *
     * @param string               $templateName
     * @param array<string, mixed> $variables
     *
     * @return string
     */
    public function render(string $templateName, array $variables = []): string
    {
        return $this->renderer->render($templateName, $variables);
    }

    /**
     * Render a template string.
     *
     * @param string               $templateContent
     * @param array<string, mixed> $variables
     *
     * @return string
     */
    public function renderString(string $templateContent, array $variables = []): string
    {
        return $this->renderer->renderString($templateContent, $variables);
    }

    /**
     * Add template directory.
     *
     * @param string $path
     * @param string $namespace
     *
     * @return self
     */
    public function addPath(string $path, string $namespace = ''): self
    {
        $loader = $this->renderer->getLoader();

        if ($loader instanceof ChainTemplateLoader) {
            // Find or create FileTemplateLoader
            $fileLoader = null;
            foreach ($loader->getLoaders() as $l) {
                if ($l instanceof FileTemplateLoader) {
                    $fileLoader = $l;
                    break;
                }
            }

            if ($fileLoader === null) {
                $fileLoader = new FileTemplateLoader();
                $loader->addLoader($fileLoader);
            }

            $fileLoader->addPath($namespace, $path);
        } elseif ($loader instanceof FileTemplateLoader) {
            $loader->addPath($namespace, $path);
        }

        return $this;
    }

    /**
     * Add default templates.
     *
     * @param array<string, string> $templates
     *
     * @return self
     */
    public function addDefaultTemplates(array $templates): self
    {
        $loader = $this->renderer->getLoader();

        if ($loader instanceof ChainTemplateLoader) {
            // Find or create ArrayTemplateLoader
            $arrayLoader = null;
            foreach ($loader->getLoaders() as $l) {
                if ($l instanceof ArrayTemplateLoader) {
                    $arrayLoader = $l;
                    break;
                }
            }

            if ($arrayLoader === null) {
                $arrayLoader = new ArrayTemplateLoader();
                $loader->addLoader($arrayLoader);
            }

            foreach ($templates as $name => $content) {
                $arrayLoader->addTemplate($name, $content);
            }
        } elseif ($loader instanceof ArrayTemplateLoader) {
            foreach ($templates as $name => $content) {
                $loader->addTemplate($name, $content);
            }
        }

        return $this;
    }

    /**
     * Get the renderer.
     *
     * @return TemplateRendererInterface
     */
    public function getRenderer(): TemplateRendererInterface
    {
        return $this->renderer;
    }

    /**
     * Get the markdown escape instance.
     *
     * @return MarkdownEscape
     */
    public function getMarkdownEscape(): MarkdownEscape
    {
        return $this->markdownEscape;
    }

    /**
     * Configure options.
     *
     * @param array<string, mixed> $options
     *
     * @return self
     */
    public function configure(array $options): self
    {
        $this->renderer->configure($options);

        return $this;
    }

    /**
     * Create default renderer with common templates.
     *
     * @return TemplateRendererInterface
     */
    private function createDefaultRenderer(): TemplateRendererInterface
    {
        $arrayLoader = new ArrayTemplateLoader($this->getDefaultTemplates());
        $fileLoader  = new FileTemplateLoader();

        $chainLoader = new ChainTemplateLoader([
            $arrayLoader, // Higher priority for defaults
            $fileLoader,
        ]);

        $engine = new PhpTemplateEngine();

        return new TemplateRenderer($chainLoader, $engine, $this->markdownEscape);
    }

    /**
     * Get default templates.
     *
     * @return array<string, string>
     */
    private function getDefaultTemplates(): array
    {
        return [
            // Document template
            'document' => <<<'PHP'
# <?= $md->escapeContent($title ?? 'Document') ?>

<?php if (isset($description)): ?>

<?= $md->escapeContent($description) ?>

<?php endif; ?>
<?php if (isset($sections) && is_array($sections)): ?>
<?php foreach ($sections as $section): ?>

## <?= $md->escapeContent($section['title'] ?? 'Section') ?>
<?php echo "\n\n"; ?><?= $md->escapeContent($section['content'] ?? '') ?>

<?php endforeach; ?>

<?php endif; ?>
PHP
            ,

            // Table template
            'table' => <<<'PHP'
<?php if (isset($headers) && is_array($headers) && isset($rows) && is_array($rows)): ?>
| <?= implode(' | ', array_map([$md, 'escapeContent'], $headers)) ?> |
| <?= implode(' | ', array_fill(0, count($headers), '---')) ?> |
<?php foreach ($rows as $row): ?>
| <?= implode(' | ', array_map([$md, 'escapeContent'], $row)) ?> |
<?php endforeach; ?>
<?php endif; ?>
PHP
            ,

            // List template
            'list' => <<<'PHP'
<?php if (isset($items) && is_array($items)): ?>
<?php foreach ($items as $item): ?>
<?php if (is_array($item) && isset($item['text'])): ?>
- <?= $md->escapeContent($item['text']) ?>

<?php if (isset($item['subItems']) && is_array($item['subItems'])): ?>
<?php foreach ($item['subItems'] as $subItem): ?>
  - <?= $md->escapeContent($subItem) ?>

<?php endforeach; ?>
<?php endif; ?>
<?php else: ?>
- <?= $md->escapeContent((string) $item) ?>

<?php endif; ?>
<?php endforeach; ?>
<?php endif; ?>
PHP
            ,

            // Code example template
            'code-example' => <<<'PHP'
<?php if (isset($title)): ?>
### <?= $md->escapeContent($title) ?>

<?php endif; ?>
<?php if (isset($description)): ?>

<?= $md->escapeContent($description) ?>

<?php endif; ?>

```<?= $language ?? '' ?>

<?= $code ?? '' ?>

```
<?php if (isset($output)): ?>

Output:
```
<?= $output ?>

```
<?php endif; ?>
PHP
            ,

            // Link list template
            'link-list' => <<<'PHP'
<?php if (isset($links) && is_array($links)): ?>
<?php foreach ($links as $link): ?>
- [<?= $md->escapeContent($link['text'] ?? '') ?>](<?= $md->escapeUrl($link['url'] ?? '') ?>)<?php if (isset($link['description'])): ?> - <?= $md->escapeContent($link['description']) ?><?php endif; ?>

<?php endforeach; ?>
<?php endif; ?>
PHP
            ,

            // API documentation template
            'api-method' => <<<'PHP'
<?php
$method = strtoupper($method ?? 'GET');
$endpoint = $endpoint ?? '/';
?>
### <?= $method ?> <?= $md->escapeInlineCode($endpoint) ?>

<?php if (isset($description)): ?>
<?= $md->escapeContent($description) ?>

<?php endif; ?>
<?php if (isset($parameters) && is_array($parameters)): ?>
#### Parameters

| Name | Type | Required | Description |
| --- | --- | --- | --- |
<?php foreach ($parameters as $param): ?>
| <?= $md->escapeInlineCode($param['name'] ?? '') ?> | <?= $md->escapeContent($param['type'] ?? 'string') ?> | <?= ($param['required'] ?? false) ? 'Yes' : 'No' ?> | <?= $md->escapeContent($param['description'] ?? '') ?> |
<?php endforeach; ?>

<?php endif; ?>
<?php if (isset($example)): ?>
#### Example

```<?= $example['language'] ?? 'json' ?>

<?= $example['code'] ?? '' ?>

```
<?php endif; ?>
PHP
            ,
        ];
    }
}
