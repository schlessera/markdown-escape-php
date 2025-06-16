<?php

declare(strict_types=1);

namespace Markdown\Escape\Contract;

/**
 * Interface for template rendering engines.
 */
interface TemplateEngineInterface
{
    /**
     * Render a template with given context.
     *
     * @param TemplateInterface        $template
     * @param TemplateContextInterface $context
     *
     * @return string
     */
    public function render(TemplateInterface $template, TemplateContextInterface $context): string;

    /**
     * Check if engine supports a template.
     *
     * @param TemplateInterface $template
     *
     * @return bool
     */
    public function supports(TemplateInterface $template): bool;

    /**
     * Get engine name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Configure engine options.
     *
     * @param array<string, mixed> $options
     */
    public function configure(array $options): void;
}
