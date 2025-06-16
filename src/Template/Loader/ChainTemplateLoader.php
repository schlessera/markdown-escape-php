<?php

declare(strict_types=1);

namespace Markdown\Escape\Template\Loader;

use Markdown\Escape\Contract\TemplateInterface;
use Markdown\Escape\Contract\TemplateLoaderInterface;
use Markdown\Escape\Exception\TemplateNotFoundException;

/**
 * Chain template loader that tries multiple loaders in priority order.
 */
class ChainTemplateLoader implements TemplateLoaderInterface
{
    /**
     * @var array<TemplateLoaderInterface>
     */
    private $loaders = [];

    /**
     * @var int
     */
    private $priority = 0;

    /**
     * @param array<TemplateLoaderInterface> $loaders
     */
    public function __construct(array $loaders = [])
    {
        foreach ($loaders as $loader) {
            $this->addLoader($loader);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function load(string $name): TemplateInterface
    {
        $searchPaths = [];

        foreach ($this->getSortedLoaders() as $loader) {
            try {
                return $loader->load($name);
            } catch (TemplateNotFoundException $e) {
                $searchPaths = array_merge($searchPaths, $e->getSearchPaths());
            }
        }

        throw new TemplateNotFoundException($name, $searchPaths);
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $name): bool
    {
        foreach ($this->getSortedLoaders() as $loader) {
            if ($loader->exists($name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplateNames(): array
    {
        $names = [];

        foreach ($this->loaders as $loader) {
            $names = array_merge($names, $loader->getTemplateNames());
        }

        return array_unique($names);
    }

    /**
     * {@inheritdoc}
     */
    public function addPath(string $namespace, $path): void
    {
        // Delegate to all file-based loaders
        foreach ($this->loaders as $loader) {
            if ($loader instanceof FileTemplateLoader) {
                $loader->addPath($namespace, $path);
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
     * Add a loader to the chain.
     *
     * @param TemplateLoaderInterface $loader
     */
    public function addLoader(TemplateLoaderInterface $loader): void
    {
        // Prevent circular references
        if ($loader === $this) {
            throw new \InvalidArgumentException('Cannot add chain loader to itself');
        }

        $this->loaders[] = $loader;
    }

    /**
     * Remove a loader from the chain.
     *
     * @param TemplateLoaderInterface $loader
     */
    public function removeLoader(TemplateLoaderInterface $loader): void
    {
        $this->loaders = array_filter(
            $this->loaders,
            function ($l) use ($loader) {
                return $l !== $loader;
            }
        );

        // Re-index array
        $this->loaders = array_values($this->loaders);
    }

    /**
     * Get all loaders.
     *
     * @return array<TemplateLoaderInterface>
     */
    public function getLoaders(): array
    {
        return $this->loaders;
    }

    /**
     * Clear all loaders.
     */
    public function clear(): void
    {
        $this->loaders = [];
    }

    /**
     * Get loaders sorted by priority (highest first).
     *
     * @return array<TemplateLoaderInterface>
     */
    private function getSortedLoaders(): array
    {
        $loaders = $this->loaders;

        usort($loaders, function (TemplateLoaderInterface $a, TemplateLoaderInterface $b) {
            return $b->getPriority() <=> $a->getPriority();
        });

        return $loaders;
    }
}
