<?php

declare(strict_types=1);

namespace Markdown\Escape\Template;

use Markdown\Escape\Contract\EscaperInterface;
use Markdown\Escape\Contract\TemplateContextInterface;
use Markdown\Escape\Contract\TemplateRendererInterface;
use Markdown\Escape\MarkdownEscape;

/**
 * Template variable context implementation.
 */
class TemplateContext implements TemplateContextInterface
{
    /**
     * @var array<string, mixed>
     */
    private $variables = [];

    /**
     * @var array<string, EscaperInterface>
     */
    private $escapers = [];

    /**
     * @var MarkdownEscape|null
     */
    private $markdownEscape;

    /**
     * @var TemplateRendererInterface|null
     */
    private $renderer;

    /**
     * @param array<string, mixed> $variables
     */
    public function __construct(array $variables = [])
    {
        $this->variables = $variables;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $name, $value): TemplateContextInterface
    {
        $this->variables[$name] = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $name, $default = null)
    {
        return $this->variables[$name] ?? $default;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->variables);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(string $name): TemplateContextInterface
    {
        unset($this->variables[$name]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function all(): array
    {
        return $this->variables;
    }

    /**
     * {@inheritdoc}
     */
    public function merge($context): TemplateContextInterface
    {
        if ($context instanceof TemplateContextInterface) {
            $this->variables = array_merge($this->variables, $context->all());

            // Also merge escapers
            foreach ($context->all() as $name => $value) {
                if ($value instanceof EscaperInterface) {
                    $this->addEscaper($name, $value);
                }
            }

            // Merge markdown escape if not set
            if ($this->markdownEscape === null && $context->getMarkdownEscape() !== null) {
                $this->markdownEscape = $context->getMarkdownEscape();
            }
        } elseif (is_array($context)) {
            $this->variables = array_merge($this->variables, $context);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addEscaper(string $name, EscaperInterface $escaper): TemplateContextInterface
    {
        $this->escapers[$name] = $escaper;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getEscaper(string $name): ?EscaperInterface
    {
        return $this->escapers[$name] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function getMarkdownEscape(): ?MarkdownEscape
    {
        return $this->markdownEscape;
    }

    /**
     * {@inheritdoc}
     */
    public function setMarkdownEscape(MarkdownEscape $markdownEscape): TemplateContextInterface
    {
        $this->markdownEscape = $markdownEscape;

        // Also add common escapers for convenience
        $factory = $markdownEscape->getFactory();

        // Add general content escaper
        $generalContext = new \Markdown\Escape\Context\GeneralContentContext();
        $this->addEscaper('content', $factory->createEscaper($generalContext, $markdownEscape->getDialect()));
        $this->addEscaper('general', $factory->createEscaper($generalContext, $markdownEscape->getDialect()));

        // Add URL escaper
        $urlContext = new \Markdown\Escape\Context\UrlContext();
        $this->addEscaper('url', $factory->createEscaper($urlContext, $markdownEscape->getDialect()));

        // Add inline code escaper
        $inlineCodeContext = new \Markdown\Escape\Context\InlineCodeContext();
        $this->addEscaper('inlineCode', $factory->createEscaper($inlineCodeContext, $markdownEscape->getDialect()));
        $this->addEscaper('inline', $factory->createEscaper($inlineCodeContext, $markdownEscape->getDialect()));

        // Add code block escaper
        $codeBlockContext = new \Markdown\Escape\Context\CodeBlockContext();
        $this->addEscaper('codeBlock', $factory->createEscaper($codeBlockContext, $markdownEscape->getDialect()));
        $this->addEscaper('code', $factory->createEscaper($codeBlockContext, $markdownEscape->getDialect()));

        return $this;
    }

    /**
     * Create a new context with variables.
     *
     * @param array<string, mixed> $variables
     *
     * @return self
     */
    public static function create(array $variables = []): self
    {
        return new self($variables);
    }

    /**
     * {@inheritdoc}
     */
    public function getRenderer(): ?TemplateRendererInterface
    {
        return $this->renderer;
    }

    /**
     * {@inheritdoc}
     */
    public function setRenderer(TemplateRendererInterface $renderer): TemplateContextInterface
    {
        $this->renderer = $renderer;

        return $this;
    }
}
