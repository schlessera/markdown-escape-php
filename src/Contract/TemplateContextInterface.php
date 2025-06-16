<?php

declare(strict_types=1);

namespace Markdown\Escape\Contract;

use Markdown\Escape\MarkdownEscape;

/**
 * Interface for template variable context.
 */
interface TemplateContextInterface
{
    /**
     * Set a variable in the context.
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return self
     */
    public function set(string $name, $value): self;

    /**
     * Get a variable from the context.
     *
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get(string $name, $default = null);

    /**
     * Check if a variable exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public function has(string $name): bool;

    /**
     * Remove a variable from the context.
     *
     * @param string $name
     *
     * @return self
     */
    public function remove(string $name): self;

    /**
     * Get all variables.
     *
     * @return array<string, mixed>
     */
    public function all(): array;

    /**
     * Merge another context or array.
     *
     * @param TemplateContextInterface|array<string, mixed> $context
     *
     * @return self
     */
    public function merge($context): self;

    /**
     * Add an escaper to the context.
     *
     * @param string           $name
     * @param EscaperInterface $escaper
     *
     * @return self
     */
    public function addEscaper(string $name, EscaperInterface $escaper): self;

    /**
     * Get an escaper by name.
     *
     * @param string $name
     *
     * @return EscaperInterface|null
     */
    public function getEscaper(string $name): ?EscaperInterface;

    /**
     * Get the default Markdown escaper.
     *
     * @return MarkdownEscape|null
     */
    public function getMarkdownEscape(): ?MarkdownEscape;

    /**
     * Set the default Markdown escaper.
     *
     * @param MarkdownEscape $markdownEscape
     *
     * @return self
     */
    public function setMarkdownEscape(MarkdownEscape $markdownEscape): self;

    /**
     * Get the template renderer.
     *
     * @return TemplateRendererInterface|null
     */
    public function getRenderer(): ?TemplateRendererInterface;

    /**
     * Set the template renderer.
     *
     * @param TemplateRendererInterface $renderer
     *
     * @return self
     */
    public function setRenderer(TemplateRendererInterface $renderer): self;
}
