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
use Markdown\Escape\Dialect\CommonMarkDialect;
use Markdown\Escape\Dialect\GitHubFlavoredMarkdownDialect;

class MarkdownEscape
{
    /**
     * @var EscaperFactoryInterface
     */
    private $factory;

    /**
     * @var DialectInterface
     */
    private $dialect;

    public function __construct(?DialectInterface $dialect = null, ?EscaperFactoryInterface $factory = null)
    {
        $this->dialect = $dialect ?? new CommonMarkDialect();
        $this->factory = $factory ?? new EscaperFactory();
    }

    /**
     * Create a new instance with CommonMark dialect.
     *
     * @return self
     */
    public static function commonMark(): self
    {
        return new self(new CommonMarkDialect());
    }

    /**
     * Create a new instance with GitHub Flavored Markdown dialect.
     *
     * @return self
     */
    public static function gfm(): self
    {
        return new self(new GitHubFlavoredMarkdownDialect());
    }

    /**
     * Escape general content for safe embedding in Markdown.
     *
     * @param string $content
     * @param array  $options
     *
     * @return string
     */
    public function escapeContent(string $content, array $options = []): string
    {
        $context = new GeneralContentContext($options);
        $escaper = $this->factory->createEscaper($context, $this->dialect);

        return $escaper->escape($content);
    }

    /**
     * Escape a URL for safe embedding in Markdown links.
     *
     * @param string $url
     * @param array  $options
     *
     * @return string
     */
    public function escapeUrl(string $url, array $options = []): string
    {
        $context = new UrlContext($options);
        $escaper = $this->factory->createEscaper($context, $this->dialect);

        return $escaper->escape($url);
    }

    /**
     * Escape content for inline code.
     *
     * @param string $code
     * @param array  $options
     *
     * @return string
     */
    public function escapeInlineCode(string $code, array $options = []): string
    {
        $context = new InlineCodeContext($options);
        $escaper = $this->factory->createEscaper($context, $this->dialect);

        return $escaper->escape($code);
    }

    /**
     * Escape content for code blocks.
     *
     * @param string $code
     * @param array  $options
     *
     * @return string
     */
    public function escapeCodeBlock(string $code, array $options = []): string
    {
        $context = new CodeBlockContext($options);
        $escaper = $this->factory->createEscaper($context, $this->dialect);

        return $escaper->escape($code);
    }

    /**
     * Escape content to be safe within an existing code block.
     * This prevents content from breaking out of code fences.
     *
     * @param string $code
     *
     * @return string
     */
    public function escapeWithinCodeBlock(string $code): string
    {
        $context = new CodeBlockContext(['within' => true]);
        $escaper = $this->factory->createEscaper($context, $this->dialect);

        return $escaper->escape($code);
    }

    /**
     * Escape content with a custom context.
     *
     * @param string           $content
     * @param ContextInterface $context
     *
     * @return string
     */
    public function escape(string $content, ContextInterface $context): string
    {
        $escaper = $this->factory->createEscaper($context, $this->dialect);

        return $escaper->escape($content);
    }

    /**
     * Set the dialect to use for escaping.
     *
     * @param DialectInterface $dialect
     *
     * @return self
     */
    public function withDialect(DialectInterface $dialect): self
    {
        $clone          = clone $this;
        $clone->dialect = $dialect;

        return $clone;
    }

    /**
     * Get the current dialect.
     *
     * @return DialectInterface
     */
    public function getDialect(): DialectInterface
    {
        return $this->dialect;
    }

    /**
     * Get the escaper factory.
     *
     * @return EscaperFactoryInterface
     */
    public function getFactory(): EscaperFactoryInterface
    {
        return $this->factory;
    }
}
