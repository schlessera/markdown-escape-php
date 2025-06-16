<?php

declare(strict_types=1);

namespace Markdown\Escape\Template;

use Markdown\Escape\Contract\EscaperInterface;
use Markdown\Escape\Contract\TemplateEngineInterface;
use Markdown\Escape\Contract\TemplateLoaderInterface;
use Markdown\Escape\Contract\TemplateRendererInterface;
use Markdown\Escape\Exception\TemplateNotFoundException;
use Markdown\Escape\Exception\TemplateRenderException;
use Markdown\Escape\MarkdownEscape;
use Markdown\Escape\Template\Engine\PhpTemplateEngine;
use Markdown\Escape\Template\Loader\ChainTemplateLoader;

/**
 * Template renderer implementation.
 */
class TemplateRenderer implements TemplateRendererInterface
{
    /**
     * @var TemplateLoaderInterface
     */
    private $loader;

    /**
     * @var TemplateEngineInterface
     */
    private $engine;

    /**
     * @var MarkdownEscape|null
     */
    private $markdownEscape;

    /**
     * @var array<string, EscaperInterface>
     */
    private $escapers = [];

    /**
     * @var array<string, mixed>
     */
    private $options = [
        'auto_reload'      => true,
        'strict_variables' => false,
    ];

    /**
     * @param TemplateLoaderInterface|null $loader
     * @param TemplateEngineInterface|null $engine
     * @param MarkdownEscape|null          $markdownEscape
     */
    public function __construct(
        ?TemplateLoaderInterface $loader = null,
        ?TemplateEngineInterface $engine = null,
        ?MarkdownEscape $markdownEscape = null
    ) {
        $this->loader         = $loader ?? new ChainTemplateLoader();
        $this->engine         = $engine ?? new PhpTemplateEngine();
        $this->markdownEscape = $markdownEscape;
    }

    /**
     * {@inheritdoc}
     */
    public function render(string $templateName, array $variables = []): string
    {
        try {
            $template = $this->loader->load($templateName);
            $context  = $this->createContext($variables);

            return $this->engine->render($template, $context);
        } catch (TemplateNotFoundException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new TemplateRenderException(
                $templateName,
                $variables,
                sprintf('Failed to render template "%s"', $templateName),
                0,
                $e
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function renderString(string $templateContent, array $variables = []): string
    {
        $template = new Template('string:' . md5($templateContent), $templateContent, [
            'source' => 'string',
        ]);

        try {
            $context = $this->createContext($variables);

            return $this->engine->render($template, $context);
        } catch (\Throwable $e) {
            throw new TemplateRenderException(
                'string',
                $variables,
                '',
                0,
                $e
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setLoader(TemplateLoaderInterface $loader): TemplateRendererInterface
    {
        $this->loader = $loader;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setEngine(TemplateEngineInterface $engine): TemplateRendererInterface
    {
        $this->engine = $engine;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setMarkdownEscape(MarkdownEscape $markdownEscape): TemplateRendererInterface
    {
        $this->markdownEscape = $markdownEscape;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addEscaper(string $name, EscaperInterface $escaper): TemplateRendererInterface
    {
        $this->escapers[$name] = $escaper;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(array $options): TemplateRendererInterface
    {
        $this->options = array_merge($this->options, $options);

        // Configure engine if it has options
        if (isset($options['engine']) && is_array($options['engine'])) {
            $this->engine->configure($options['engine']);
        }

        return $this;
    }

    /**
     * Get the template loader.
     *
     * @return TemplateLoaderInterface
     */
    public function getLoader(): TemplateLoaderInterface
    {
        return $this->loader;
    }

    /**
     * Get the template engine.
     *
     * @return TemplateEngineInterface
     */
    public function getEngine(): TemplateEngineInterface
    {
        return $this->engine;
    }

    /**
     * Get options.
     *
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Create template context from variables.
     *
     * @param array<string, mixed> $variables
     *
     * @return TemplateContext
     */
    private function createContext(array $variables): TemplateContext
    {
        $context = new TemplateContext($variables);

        // Set markdown escape if available
        if ($this->markdownEscape !== null) {
            $context->setMarkdownEscape($this->markdownEscape);
        }

        // Add custom escapers
        foreach ($this->escapers as $name => $escaper) {
            $context->addEscaper($name, $escaper);
        }

        // Set the renderer so templates can render sub-templates
        $context->setRenderer($this);

        return $context;
    }
}
