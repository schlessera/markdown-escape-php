<?php

declare(strict_types=1);

namespace Markdown\Escape\Contract;

interface DialectInterface
{
    /**
     * Get the name of the dialect.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get the special characters that need escaping in this dialect.
     *
     * @param ContextInterface $context The context in which the escaping is being done
     *
     * @return array<string> Array of characters that need escaping
     */
    public function getSpecialCharacters(ContextInterface $context): array;

    /**
     * Get the escape sequence for a given character in this dialect.
     *
     * @param string           $character The character to escape
     * @param ContextInterface $context   The context in which the escaping is being done
     *
     * @return string The escaped character sequence
     */
    public function escapeCharacter(string $character, ContextInterface $context): string;

    /**
     * Check if this dialect supports a specific feature.
     *
     * @param string $feature The feature to check for
     *
     * @return bool
     */
    public function supportsFeature(string $feature): bool;
}
