<?php

declare(strict_types=1);

namespace Markdown\Escape\Template\Loader;

use Markdown\Escape\Contract\TemplateInterface;
use Markdown\Escape\Contract\TemplateLoaderInterface;
use Markdown\Escape\Exception\TemplateNotFoundException;
use Markdown\Escape\Template\Template;

/**
 * File-based template loader.
 */
class FileTemplateLoader implements TemplateLoaderInterface
{
    /**
     * @var array<string, string|array<string>>
     */
    private $paths = [];

    /**
     * @var string
     */
    private $extension = '.php';

    /**
     * @var int
     */
    private $priority = 0;

    /**
     * @var array<string, TemplateInterface>
     */
    private $cache = [];

    /**
     * @param string|array<string> $paths
     * @param string               $extension
     */
    public function __construct($paths = [], string $extension = '.php')
    {
        if (is_string($paths)) {
            $this->addPath('', $paths);
        } elseif (is_array($paths)) {
            foreach ($paths as $namespace => $path) {
                if (is_int($namespace)) {
                    $this->addPath('', $path);
                } else {
                    $this->addPath($namespace, $path);
                }
            }
        }

        $this->extension = $extension;
    }

    /**
     * {@inheritdoc}
     */
    public function load(string $name): TemplateInterface
    {
        // Check cache first
        if (isset($this->cache[$name])) {
            return $this->cache[$name];
        }

        $file = $this->findTemplate($name);

        if ($file === null) {
            throw new TemplateNotFoundException(
                $name,
                $this->getAllSearchPaths()
            );
        }

        $content = file_get_contents($file);
        if ($content === false) {
            throw new TemplateNotFoundException(
                $name,
                $this->getAllSearchPaths(),
                sprintf('Failed to read template file: %s', $file)
            );
        }

        $template = new Template($name, $content, [
            'file'   => $file,
            'loader' => get_class($this),
        ]);

        // Cache the template
        $this->cache[$name] = $template;

        return $template;
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $name): bool
    {
        return $this->findTemplate($name) !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplateNames(): array
    {
        $templates = [];

        foreach ($this->paths as $namespace => $paths) {
            if (!is_array($paths)) {
                $paths = [$paths];
            }

            foreach ($paths as $path) {
                if (!is_dir($path)) {
                    continue;
                }

                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::LEAVES_ONLY
                );

                foreach ($iterator as $file) {
                    if ($file->isFile() && $this->isTemplateFile($file->getFilename())) {
                        $relativePath = str_replace(
                            DIRECTORY_SEPARATOR,
                            '/',
                            substr($file->getPathname(), strlen($path) + 1)
                        );

                        // Remove extension
                        $templateName = substr($relativePath, 0, -strlen($this->extension));

                        // Add namespace prefix if not default
                        if ($namespace !== '') {
                            $templateName = $namespace . '/' . $templateName;
                        }

                        $templates[] = $templateName;
                    }
                }
            }
        }

        return array_unique($templates);
    }

    /**
     * {@inheritdoc}
     */
    public function addPath(string $namespace, $path): void
    {
        $namespace = $this->normalizeNamespace($namespace);

        if (!isset($this->paths[$namespace])) {
            $this->paths[$namespace] = [];
        }

        if (is_string($path)) {
            $path = rtrim($path, '/\\');
            if (is_dir($path)) {
                if (!is_array($this->paths[$namespace])) {
                    $this->paths[$namespace] = [];
                }
                $this->paths[$namespace][] = $path;
            }
        } elseif (is_array($path)) {
            foreach ($path as $p) {
                $this->addPath($namespace, $p);
            }
        }

        // Clear cache when paths change
        $this->cache = [];
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
     * Find template file.
     *
     * @param string $name
     *
     * @return string|null
     */
    private function findTemplate(string $name): ?string
    {
        $namespace = '';
        $shortName = $name;

        // Extract namespace from name
        if (strpos($name, '/') !== false) {
            $parts = explode('/', $name, 2);
            if (isset($this->paths[$parts[0]])) {
                $namespace = $parts[0];
                $shortName = $parts[1];
            }
        }

        // Normalize namespace
        $namespace = $this->normalizeNamespace($namespace);

        // Add extension if not present
        if (!$this->hasExtension($shortName)) {
            $shortName .= $this->extension;
        }

        // Search in namespace paths
        if (isset($this->paths[$namespace])) {
            $paths = is_array($this->paths[$namespace]) ? $this->paths[$namespace] : [$this->paths[$namespace]];

            foreach ($paths as $path) {
                $file = $path . '/' . $shortName;
                if (is_file($file) && is_readable($file)) {
                    return $file;
                }
            }
        }

        // If not found and namespace was specified, don't search in default namespace
        if ($namespace !== '') {
            return null;
        }

        // Search in all namespaces as fallback
        foreach ($this->paths as $ns => $paths) {
            if ($ns === '') {
                continue; // Already searched
            }

            if (!is_array($paths)) {
                $paths = [$paths];
            }

            foreach ($paths as $path) {
                $file = $path . '/' . $shortName;
                if (is_file($file) && is_readable($file)) {
                    return $file;
                }
            }
        }

        return null;
    }

    /**
     * Check if filename is a template file.
     *
     * @param string $filename
     *
     * @return bool
     */
    private function isTemplateFile(string $filename): bool
    {
        return substr($filename, -strlen($this->extension)) === $this->extension;
    }

    /**
     * Check if name has extension.
     *
     * @param string $name
     *
     * @return bool
     */
    private function hasExtension(string $name): bool
    {
        return substr($name, -strlen($this->extension)) === $this->extension;
    }

    /**
     * Normalize namespace.
     *
     * @param string $namespace
     *
     * @return string
     */
    private function normalizeNamespace(string $namespace): string
    {
        return trim($namespace, '/');
    }

    /**
     * Get all search paths.
     *
     * @return array<string>
     */
    private function getAllSearchPaths(): array
    {
        $allPaths = [];

        foreach ($this->paths as $paths) {
            if (is_array($paths)) {
                $allPaths = array_merge($allPaths, $paths);
            } else {
                $allPaths[] = $paths;
            }
        }

        return array_unique($allPaths);
    }
}
