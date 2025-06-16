<?php

declare(strict_types=1);

namespace Markdown\Escape\Template\Engine;

use Markdown\Escape\Contract\TemplateContextInterface;
use Markdown\Escape\Contract\TemplateEngineInterface;
use Markdown\Escape\Contract\TemplateInterface;
use Markdown\Escape\Exception\TemplateRenderException;

/**
 * PHP template engine with short tags support.
 */
class PhpTemplateEngine implements TemplateEngineInterface
{
    /**
     * @var array<string, mixed>
     */
    private $options = [
        'strict_variables' => false,
        'auto_escape'      => false,
        'short_tags'       => true,
    ];

    /**
     * @var string
     */
    private $name = 'php';

    /**
     * {@inheritdoc}
     */
    public function render(TemplateInterface $template, TemplateContextInterface $context): string
    {
        $templateName = $template->getName();

        try {
            return $this->doRender($template->getContent(), $context, $template);
        } catch (\Throwable $e) {
            throw new TemplateRenderException(
                $templateName,
                $context->all(),
                sprintf('Template rendering failed: %s', $e->getMessage()),
                0,
                $e
            );
        }
    }

    /**
     * Perform the actual rendering.
     *
     * @param string                   $templateContent
     * @param TemplateContextInterface $context
     *
     * @return string
     */
    private function doRender(string $templateContent, TemplateContextInterface $context, TemplateInterface $template = null): string
    {
        // Extract variables to local scope
        $variables = $context->all();

        // Make context available as $context
        $variables['context'] = $context;

        // Make markdown escape available
        if ($markdownEscape = $context->getMarkdownEscape()) {
            $variables['markdown'] = $markdownEscape;
            $variables['md']       = $markdownEscape; // Short alias
        }

        // Make escapers available
        $variables['escapers'] = new class ($context) {
            /** @var TemplateContextInterface */
            private $context;

            public function __construct(TemplateContextInterface $context)
            {
                $this->context = $context;
            }

            /**
             * @return \Markdown\Escape\Contract\EscaperInterface|null
             */
            public function __get(string $name)
            {
                return $this->context->getEscaper($name);
            }

            /**
             * @return string
             */
            public function __call(string $name, array $args)
            {
                $escaper = $this->context->getEscaper($name);
                if ($escaper && isset($args[0])) {
                    return $escaper->escape((string) $args[0]);
                }

                return '';
            }
        };

        // Use output buffering to capture template output
        $level = ob_get_level();
        ob_start();

        try {
            // Create a closure to isolate variable scope
            $render = function () use ($templateContent, $variables, $template) {
                extract($variables, EXTR_SKIP);

                // Get template directory from metadata if available
                $templateDir = null;
                if ($template !== null) {
                    $metadata = $template->getMetadata();
                    if (isset($metadata['file'])) {
                        $templateDir = dirname($metadata['file']);
                    }
                }

                // Create temporary file for PHP evaluation
                $tmpFile = tempnam(sys_get_temp_dir(), 'markdown_template_');
                if ($tmpFile === false) {
                    throw new \RuntimeException('Failed to create temporary file for template rendering');
                }

                try {
                    // Write template content
                    if (file_put_contents($tmpFile, $templateContent) === false) {
                        throw new \RuntimeException('Failed to write template content to temporary file');
                    }

                    // Save current working directory and include path
                    $oldCwd         = getcwd();
                    $oldIncludePath = get_include_path();
                    $dirChanged     = false;

                    try {
                        // Change to template directory if available
                        if ($templateDir !== null && is_dir($templateDir)) {
                            // Try to change directory, but don't fail if it doesn't work (e.g., vfsStream)
                            $dirChanged = @chdir($templateDir);
                            // Always update the include path
                            set_include_path($templateDir . PATH_SEPARATOR . $oldIncludePath);
                        }

                        // Include the template file
                        include $tmpFile;
                    } finally {
                        // Restore working directory and include path
                        if ($dirChanged && $oldCwd !== false) {
                            @chdir($oldCwd);
                        }
                        set_include_path($oldIncludePath);
                    }
                } finally {
                    // Clean up temporary file
                    @unlink($tmpFile);
                }
            };

            $render();

            return ob_get_clean() ?: '';
        } catch (\Throwable $e) {
            // Clean up output buffers
            while (ob_get_level() > $level) {
                ob_end_clean();
            }

            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supports(TemplateInterface $template): bool
    {
        // Support templates with php engine metadata or .php extension
        $engineType = $template->getMetadataValue('engine', '');
        if ($engineType === 'php' || $engineType === $this->name) {
            return true;
        }

        // Check file extension in template name
        $name = $template->getName();

        return substr($name, -4) === '.php' || substr($name, -6) === '.phtml';
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
    public function configure(array $options): void
    {
        $this->options = array_merge($this->options, $options);
    }

    /**
     * Get engine options.
     *
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
