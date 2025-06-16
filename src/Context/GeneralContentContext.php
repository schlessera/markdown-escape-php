<?php

declare(strict_types=1);

namespace Markdown\Escape\Context;

class GeneralContentContext extends AbstractContext
{
    public const NAME = 'general_content';

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
            'emphasis',
            'strong',
            'link',
            'image',
            'code',
            'heading',
            'list',
            'blockquote',
            'horizontal_rule',
            'html',
        ];
    }
}
