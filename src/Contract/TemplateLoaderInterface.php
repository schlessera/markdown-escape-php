<?php

declare(strict_types=1);

namespace Markdown\Escape\Contract;

/**
 * Interface for template loading strategies.
 */
interface TemplateLoaderInterface
{
    /**
     * Load a template by name.
     *
     * @param string $name
     *
     * @return TemplateInterface
     *
     * @throws \Markdown\Escape\Exception\TemplateNotFoundException
     */
    public function load(string $name): TemplateInterface;

    /**
     * Check if a template exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public function exists(string $name): bool;

    /**
     * Get all available template names.
     *
     * @return array<string>
     */
    public function getTemplateNames(): array;

    /**
     * Add a path or source for templates.
     *
     * @param string $namespace
     * @param mixed  $path
     */
    public function addPath(string $namespace, $path): void;

    /**
     * Set loader priority (for chain loading).
     *
     * @param int $priority
     */
    public function setPriority(int $priority): void;

    /**
     * Get loader priority.
     *
     * @return int
     */
    public function getPriority(): int;
}
