<?php

declare(strict_types=1);

namespace Markdown\Escape\Dialect;

use Markdown\Escape\Context\CodeBlockContext;
use Markdown\Escape\Context\GeneralContentContext;
use Markdown\Escape\Context\InlineCodeContext;
use Markdown\Escape\Context\UrlContext;

class CommonMarkDialect extends AbstractDialect
{
    public const NAME = 'commonmark';

    public function __construct()
    {
        parent::__construct(self::NAME);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->features = [
            'emphasis',
            'strong_emphasis',
            'strikethrough',
            'links',
            'images',
            'code_blocks',
            'inline_code',
            'lists',
            'blockquotes',
            'headings',
            'horizontal_rules',
            'html_blocks',
            'tables',
        ];

        $this->characterMappings = [
            GeneralContentContext::NAME => [
                '\\' => '\\\\',
                '*'  => '\\*',
                '_'  => '\\_',
                '['  => '\\[',
                ']'  => '\\]',
                '('  => '\\(',
                ')'  => '\\)',
                '#'  => '\\#',
                '+'  => '\\+',
                // '-' removed - only escape at start of line
                // '.' removed - only escape after numbers at start of line
                // '!' removed - only escape before [
                '|' => '\\|',
                '{' => '\\{',
                '}' => '\\}',
                '>' => '\\>',
                '`' => '\\`',
            ],
            UrlContext::NAME => [
                ' '  => '%20',
                '('  => '%28',
                ')'  => '%29',
                '<'  => '%3C',
                '>'  => '%3E',
                '"'  => '%22',
                '\'' => '%27',
                '\\' => '%5C',
            ],
            InlineCodeContext::NAME => [
                '`' => '\\`',
            ],
            CodeBlockContext::NAME => [],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultSpecialCharacters(): array
    {
        return [
            '\\', '*', '_', '[', ']', '(', ')', '#', '+', '|', '{', '}', '>', '`',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultCharacterMappings(): array
    {
        return [
            '\\' => '\\\\',
            '*'  => '\\*',
            '_'  => '\\_',
            '['  => '\\[',
            ']'  => '\\]',
            '('  => '\\(',
            ')'  => '\\)',
            '#'  => '\\#',
            '+'  => '\\+',
            '|'  => '\\|',
            '{'  => '\\{',
            '}'  => '\\}',
            '>'  => '\\>',
            '`'  => '\\`',
        ];
    }
}
