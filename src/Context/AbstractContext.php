<?php

declare(strict_types=1);

namespace Markdown\Escape\Context;

use Markdown\Escape\Contract\ContextInterface;

abstract class AbstractContext implements ContextInterface
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var array
     */
    protected $escapingTypes = [];

    public function __construct(string $name, array $options = [])
    {
        $this->name    = $name;
        $this->options = $options;
        $this->configure();
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
    public function requiresEscaping(string $type): bool
    {
        return in_array($type, $this->escapingTypes, true);
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Configure the context with escaping types.
     */
    abstract protected function configure(): void;
}
