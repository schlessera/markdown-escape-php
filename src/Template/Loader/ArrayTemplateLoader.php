<?php

declare(strict_types=1);

namespace Markdown\Escape\Template\Loader;

use Markdown\Escape\Contract\TemplateInterface;
use Markdown\Escape\Contract\TemplateLoaderInterface;
use Markdown\Escape\Exception\TemplateNotFoundException;
use Markdown\Escape\Template\Template;

/**
 * Array-based template loader for storing templates in memory.
 */
class ArrayTemplateLoader implements TemplateLoaderInterface
{
    /**
     * @var array<string, string|TemplateInterface>
     */
    private $templates = [];

    /**
     * @var int
     */
    private $priority = 10; // Higher priority than file loader by default

    /**
     * @param array<string, string|TemplateInterface> $templates
     */
    public function __construct(array $templates = [])
    {
        foreach ($templates as $name => $template) {
            $this->addTemplate($name, $template);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function load(string $name): TemplateInterface
    {
        if (!isset($this->templates[$name])) {
            throw new TemplateNotFoundException($name, ['memory']);
        }

        $template = $this->templates[$name];

        if (is_string($template)) {
            $template = new Template($name, $template, [
                'loader' => get_class($this),
                'source' => 'array',
            ]);

            // Cache as Template instance
            $this->templates[$name] = $template;
        }

        return $template;
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $name): bool
    {
        return isset($this->templates[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplateNames(): array
    {
        return array_keys($this->templates);
    }

    /**
     * {@inheritdoc}
     */
    public function addPath(string $namespace, $path): void
    {
        // Array loader doesn't use paths, but we can use this to add templates
        if (is_array($path)) {
            foreach ($path as $name => $template) {
                $fullName = $namespace ? $namespace . '/' . $name : $name;
                $this->addTemplate($fullName, $template);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * Add a template to the loader.
     *
     * @param string                   $name
     * @param string|TemplateInterface $template
     */
    public function addTemplate(string $name, $template): void
    {
        if (!is_string($template) && !$template instanceof TemplateInterface) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Template must be a string or implement TemplateInterface, %s given',
                    is_object($template) ? get_class($template) : gettype($template)
                )
            );
        }

        $this->templates[$name] = $template;
    }

    /**
     * Remove a template from the loader.
     *
     * @param string $name
     */
    public function removeTemplate(string $name): void
    {
        unset($this->templates[$name]);
    }

    /**
     * Clear all templates.
     */
    public function clear(): void
    {
        $this->templates = [];
    }

    /**
     * Get all templates.
     *
     * @return array<string, string|TemplateInterface>
     */
    public function getTemplates(): array
    {
        return $this->templates;
    }

    /**
     * Set templates in bulk.
     *
     * @param array<string, string|TemplateInterface> $templates
     */
    public function setTemplates(array $templates): void
    {
        $this->clear();
        foreach ($templates as $name => $template) {
            $this->addTemplate($name, $template);
        }
    }
}
