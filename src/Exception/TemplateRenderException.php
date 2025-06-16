<?php

declare(strict_types=1);

namespace Markdown\Escape\Exception;

/**
 * Exception thrown when template rendering fails.
 */
class TemplateRenderException extends MarkdownEscapeException
{
    /**
     * @var string
     */
    private $templateName;

    /**
     * @var array<string, mixed>
     */
    private $context;

    /**
     * @param string               $templateName
     * @param array<string, mixed> $context
     * @param string               $message
     * @param int                  $code
     * @param \Throwable|null      $previous
     */
    public function __construct(
        string $templateName,
        array $context = [],
        string $message = '',
        int $code = 0,
        \Throwable $previous = null
    ) {
        $this->templateName = $templateName;
        $this->context      = $context;

        if ($message === '' && $previous !== null) {
            $message = sprintf(
                'Failed to render template "%s": %s',
                $templateName,
                $previous->getMessage()
            );
        } elseif ($message === '') {
            $message = sprintf('Failed to render template "%s"', $templateName);
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the template name that failed to render.
     *
     * @return string
     */
    public function getTemplateName(): string
    {
        return $this->templateName;
    }

    /**
     * Get the context that was used during rendering.
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
