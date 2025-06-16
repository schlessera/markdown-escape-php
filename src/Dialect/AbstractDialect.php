<?php

declare(strict_types=1);

namespace Markdown\Escape\Dialect;

use Markdown\Escape\Contract\ContextInterface;
use Markdown\Escape\Contract\DialectInterface;

abstract class AbstractDialect implements DialectInterface
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $features = [];

    /**
     * @var array
     */
    protected $characterMappings = [];

    public function __construct(string $name)
    {
        $this->name = $name;
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
    public function getSpecialCharacters(ContextInterface $context): array
    {
        $contextName = $context->getName();

        if (!isset($this->characterMappings[$contextName])) {
            return $this->getDefaultSpecialCharacters();
        }

        $keys = array_keys($this->characterMappings[$contextName]);

        // Ensure all keys are strings
        return array_map('strval', $keys);
    }

    /**
     * {@inheritdoc}
     */
    public function escapeCharacter(string $character, ContextInterface $context): string
    {
        $contextName = $context->getName();

        if (isset($this->characterMappings[$contextName][$character])) {
            return $this->characterMappings[$contextName][$character];
        }

        $defaultMappings = $this->getDefaultCharacterMappings();

        if (isset($defaultMappings[$character])) {
            return $defaultMappings[$character];
        }

        return '\\' . $character;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsFeature(string $feature): bool
    {
        return in_array($feature, $this->features, true);
    }

    /**
     * Configure the dialect with character mappings and features.
     */
    abstract protected function configure(): void;

    /**
     * Get the default special characters for this dialect.
     *
     * @return array<string>
     */
    abstract protected function getDefaultSpecialCharacters(): array;

    /**
     * Get the default character mappings for this dialect.
     *
     * @return array<string, string>
     */
    abstract protected function getDefaultCharacterMappings(): array;
}
