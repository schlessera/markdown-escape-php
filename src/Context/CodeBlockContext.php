<?php

declare(strict_types=1);

namespace Markdown\Escape\Context;

class CodeBlockContext extends AbstractContext
{
    public const NAME = 'code_block';

    public function __construct(array $options = [])
    {
        parent::__construct(self::NAME, $options);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->escapingTypes = [
            'fence',
            'indentation',
        ];
    }
}
