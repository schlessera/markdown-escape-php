<?php

declare(strict_types=1);

namespace Markdown\Escape\Tests\Unit\Dialect;

use Markdown\Escape\Context\GeneralContentContext;
use Markdown\Escape\Dialect\AbstractDialect;
use PHPUnit\Framework\TestCase;

class AbstractDialectTest extends TestCase
{
    public function testEscapeCharacterFallsBackToBackslashEscaping(): void
    {
        $dialect = new class ('test') extends AbstractDialect {
            protected function configure(): void
            {
                $this->characterMappings = [
                    'general_content' => [
                        '*' => '\\*',
                    ],
                ];
            }

            protected function getDefaultSpecialCharacters(): array
            {
                return ['*', '_'];
            }

            protected function getDefaultCharacterMappings(): array
            {
                return [
                    '_' => '\\_',
                ];
            }
        };

        $context = new GeneralContentContext();

        // Test character with context-specific mapping
        $this->assertSame('\\*', $dialect->escapeCharacter('*', $context));

        // Test character with default mapping
        $this->assertSame('\\_', $dialect->escapeCharacter('_', $context));

        // Test character with no mapping at all - should fall back to backslash escaping
        $this->assertSame('\\#', $dialect->escapeCharacter('#', $context));
    }
}
