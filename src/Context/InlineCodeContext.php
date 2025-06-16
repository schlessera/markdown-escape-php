<?php

declare(strict_types=1);

namespace Markdown\Escape\Context;

class InlineCodeContext extends AbstractContext
{
    public const NAME = 'inline_code';

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
            'backtick',
        ];
    }
}
