<?php

declare(strict_types=1);

namespace Markdown\Escape\Exception;

/**
 * Exception thrown when a template cannot be found.
 */
class TemplateNotFoundException extends MarkdownEscapeException
{
    /**
     * @var string
     */
    private $templateName;

    /**
     * @var array<string>
     */
    private $searchPaths;

    /**
     * @param string          $templateName
     * @param array<string>   $searchPaths
     * @param string          $message
     * @param int             $code
     * @param \Throwable|null $previous
     */
    public function __construct(
        string $templateName,
        array $searchPaths = [],
        string $message = '',
        int $code = 0,
        \Throwable $previous = null
    ) {
        $this->templateName = $templateName;
        $this->searchPaths  = $searchPaths;

        if ($message === '') {
            $message = sprintf(
                'Template "%s" not found%s.',
                $templateName,
                $searchPaths ? ' in paths: ' . implode(', ', $searchPaths) : ''
            );
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the template name that was not found.
     *
     * @return string
     */
    public function getTemplateName(): string
    {
        return $this->templateName;
    }

    /**
     * Get the paths that were searched.
     *
     * @return array<string>
     */
    public function getSearchPaths(): array
    {
        return $this->searchPaths;
    }
}
