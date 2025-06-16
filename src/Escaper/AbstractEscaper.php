<?php

declare(strict_types=1);

namespace Markdown\Escape\Escaper;

use Markdown\Escape\Contract\ContextInterface;
use Markdown\Escape\Contract\DialectInterface;
use Markdown\Escape\Contract\EscaperInterface;

abstract class AbstractEscaper implements EscaperInterface
{
    /**
     * @var ContextInterface
     */
    protected $context;

    /**
     * @var DialectInterface
     */
    protected $dialect;

    public function __construct(ContextInterface $context, DialectInterface $dialect)
    {
        $this->context = $context;
        $this->dialect = $dialect;
    }

    /**
     * {@inheritdoc}
     */
    public function escape(string $content): string
    {
        $specialCharacters = $this->dialect->getSpecialCharacters($this->context);

        if (empty($specialCharacters)) {
            return $content;
        }

        $escapedContent = $content;

        foreach ($specialCharacters as $character) {
            $escaped        = $this->dialect->escapeCharacter($character, $this->context);
            $escapedContent = str_replace($character, $escaped, $escapedContent);
        }

        return $this->postProcess($escapedContent);
    }

    /**
     * {@inheritdoc}
     */
    public function getContext(): ContextInterface
    {
        return $this->context;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDialect(DialectInterface $dialect): bool
    {
        return $this->dialect->getName() === $dialect->getName();
    }

    /**
     * Post-process the escaped content.
     *
     * @param string $content The escaped content
     *
     * @return string The post-processed content
     */
    protected function postProcess(string $content): string
    {
        return $content;
    }
}
