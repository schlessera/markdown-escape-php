<?php

declare(strict_types=1);

namespace Markdown\Escape\Context;

class UrlContext extends AbstractContext
{
    public const NAME = 'url';

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
            'parentheses',
            'spaces',
            'angle_brackets',
        ];
    }
}
