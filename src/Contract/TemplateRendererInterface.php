<?php

declare(strict_types=1);

namespace Markdown\Escape\Contract;

use Markdown\Escape\MarkdownEscape;

/**
 * Interface for template rendering with escaping integration.
 */
interface TemplateRendererInterface
{
    /**
     * Render a template by name with context.
     *
     * @param string               $templateName
     * @param array<string, mixed> $variables
     *
     * @return string
     */
    public function render(string $templateName, array $variables = []): string;

    /**
     * Render a template string directly.
     *
     * @param string               $templateContent
     * @param array<string, mixed> $variables
     *
     * @return string
     */
    public function renderString(string $templateContent, array $variables = []): string;

    /**
     * Set the template loader.
     *
     * @param TemplateLoaderInterface $loader
     *
     * @return self
     */
    public function setLoader(TemplateLoaderInterface $loader): self;

    /**
     * Set the template engine.
     *
     * @param TemplateEngineInterface $engine
     *
     * @return self
     */
    public function setEngine(TemplateEngineInterface $engine): self;

    /**
     * Set the default Markdown escaper.
     *
     * @param MarkdownEscape $markdownEscape
     *
     * @return self
     */
    public function setMarkdownEscape(MarkdownEscape $markdownEscape): self;

    /**
     * Add a custom escaper.
     *
     * @param string           $name
     * @param EscaperInterface $escaper
     *
     * @return self
     */
    public function addEscaper(string $name, EscaperInterface $escaper): self;

    /**
     * Configure renderer options.
     *
     * @param array<string, mixed> $options
     *
     * @return self
     */
    public function configure(array $options): self;

    /**
     * Get the template loader.
     *
     * @return TemplateLoaderInterface
     */
    public function getLoader(): TemplateLoaderInterface;
}
