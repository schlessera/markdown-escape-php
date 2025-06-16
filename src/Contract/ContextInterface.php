<?php

declare(strict_types=1);

namespace Markdown\Escape\Contract;

interface ContextInterface
{
    /**
     * Get the name of the context.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Check if this context requires a specific type of escaping.
     *
     * @param string $type The type of escaping to check for
     *
     * @return bool
     */
    public function requiresEscaping(string $type): bool;

    /**
     * Get any additional options for this context.
     *
     * @return array<string, mixed>
     */
    public function getOptions(): array;
}
