<?php

declare(strict_types=1);

namespace Markdown\Escape\Template;

use Markdown\Escape\Contract\TemplateInterface;

/**
 * Basic template implementation.
 */
class Template implements TemplateInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $content;

    /**
     * @var array<string, mixed>
     */
    private $metadata;

    /**
     * @param string               $name
     * @param string               $content
     * @param array<string, mixed> $metadata
     */
    public function __construct(string $name, string $content, array $metadata = [])
    {
        $this->name     = $name;
        $this->content  = $content;
        $this->metadata = $metadata;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * {@inheritdoc}
     */
    public function hasMetadata(string $key): bool
    {
        return array_key_exists($key, $this->metadata);
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadataValue(string $key, $default = null)
    {
        return $this->metadata[$key] ?? $default;
    }
}
