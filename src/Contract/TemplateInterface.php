<?php

declare(strict_types=1);

namespace Markdown\Escape\Contract;

/**
 * Interface for template representations.
 */
interface TemplateInterface
{
    /**
     * Get the template name/identifier.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get the template content.
     *
     * @return string
     */
    public function getContent(): string;

    /**
     * Get template metadata.
     *
     * @return array<string, mixed>
     */
    public function getMetadata(): array;

    /**
     * Check if template has specific metadata.
     *
     * @param string $key
     *
     * @return bool
     */
    public function hasMetadata(string $key): bool;

    /**
     * Get specific metadata value.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getMetadataValue(string $key, $default = null);
}
