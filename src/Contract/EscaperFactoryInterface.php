<?php

declare(strict_types=1);

namespace Markdown\Escape\Contract;

interface EscaperFactoryInterface
{
    /**
     * Create an escaper for the given context and dialect.
     *
     * @param ContextInterface $context
     * @param DialectInterface $dialect
     *
     * @return EscaperInterface
     */
    public function createEscaper(ContextInterface $context, DialectInterface $dialect): EscaperInterface;

    /**
     * Register a custom escaper for a specific context and dialect combination.
     *
     * @param string           $contextName
     * @param string           $dialectName
     * @param EscaperInterface $escaper
     */
    public function registerEscaper(string $contextName, string $dialectName, EscaperInterface $escaper): void;

    /**
     * Check if an escaper is available for the given context and dialect.
     *
     * @param ContextInterface $context
     * @param DialectInterface $dialect
     *
     * @return bool
     */
    public function hasEscaper(ContextInterface $context, DialectInterface $dialect): bool;
}
