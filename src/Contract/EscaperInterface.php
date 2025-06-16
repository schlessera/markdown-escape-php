<?php

declare(strict_types=1);

namespace Markdown\Escape\Contract;

interface EscaperInterface
{
    /**
     * Escape the given content for safe embedding in Markdown.
     *
     * @param string $content The content to escape
     *
     * @return string The escaped content
     */
    public function escape(string $content): string;

    /**
     * Get the context this escaper is designed for.
     *
     * @return ContextInterface
     */
    public function getContext(): ContextInterface;

    /**
     * Check if this escaper supports the given dialect.
     *
     * @param DialectInterface $dialect
     *
     * @return bool
     */
    public function supportsDialect(DialectInterface $dialect): bool;
}
