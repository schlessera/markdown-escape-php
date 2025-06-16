<?php

declare(strict_types=1);

namespace Markdown\Escape;

use Markdown\Escape\Context\CodeBlockContext;
use Markdown\Escape\Context\GeneralContentContext;
use Markdown\Escape\Context\InlineCodeContext;
use Markdown\Escape\Context\UrlContext;
use Markdown\Escape\Contract\ContextInterface;
use Markdown\Escape\Contract\DialectInterface;
use Markdown\Escape\Contract\EscaperFactoryInterface;
use Markdown\Escape\Contract\EscaperInterface;
use Markdown\Escape\Escaper\CodeBlockEscaper;
use Markdown\Escape\Escaper\GeneralContentEscaper;
use Markdown\Escape\Escaper\InlineCodeEscaper;
use Markdown\Escape\Escaper\UrlEscaper;
use Markdown\Escape\Exception\UnsupportedContextException;
use Markdown\Escape\Exception\UnsupportedDialectException;

class EscaperFactory implements EscaperFactoryInterface
{
    /**
     * @var array<string, array<string, EscaperInterface>>
     */
    private $escapers = [];

    /**
     * @var array<string, string>
     */
    private $defaultEscaperClasses = [
        GeneralContentContext::NAME => GeneralContentEscaper::class,
        UrlContext::NAME            => UrlEscaper::class,
        InlineCodeContext::NAME     => InlineCodeEscaper::class,
        CodeBlockContext::NAME      => CodeBlockEscaper::class,
    ];

    /**
     * {@inheritdoc}
     */
    public function createEscaper(ContextInterface $context, DialectInterface $dialect): EscaperInterface
    {
        $contextName = $context->getName();
        $dialectName = $dialect->getName();

        if (isset($this->escapers[$contextName][$dialectName])) {
            return $this->escapers[$contextName][$dialectName];
        }

        if (!isset($this->defaultEscaperClasses[$contextName])) {
            throw new UnsupportedContextException(
                sprintf('No escaper available for context "%s"', $contextName)
            );
        }

        $escaperClass = $this->defaultEscaperClasses[$contextName];
        $escaper      = new $escaperClass($context, $dialect);

        if (!$escaper instanceof EscaperInterface) {
            throw new UnsupportedContextException(
                sprintf('Class "%s" must implement EscaperInterface', $escaperClass)
            );
        }

        if (!$escaper->supportsDialect($dialect)) {
            throw new UnsupportedDialectException(
                sprintf('Dialect "%s" is not supported by escaper for context "%s"', $dialectName, $contextName)
            );
        }

        $this->escapers[$contextName][$dialectName] = $escaper;

        return $escaper;
    }

    /**
     * {@inheritdoc}
     */
    public function registerEscaper(string $contextName, string $dialectName, EscaperInterface $escaper): void
    {
        if (!isset($this->escapers[$contextName])) {
            $this->escapers[$contextName] = [];
        }

        $this->escapers[$contextName][$dialectName] = $escaper;
    }

    /**
     * {@inheritdoc}
     */
    public function hasEscaper(ContextInterface $context, DialectInterface $dialect): bool
    {
        $contextName = $context->getName();
        $dialectName = $dialect->getName();

        return isset($this->escapers[$contextName][$dialectName]) ||
               isset($this->defaultEscaperClasses[$contextName]);
    }

    /**
     * Register a default escaper class for a context.
     *
     * @param string $contextName
     * @param string $escaperClass
     */
    public function registerDefaultEscaperClass(string $contextName, string $escaperClass): void
    {
        $this->defaultEscaperClasses[$contextName] = $escaperClass;
    }
}
