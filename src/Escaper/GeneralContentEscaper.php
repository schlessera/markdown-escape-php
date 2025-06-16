<?php

declare(strict_types=1);

namespace Markdown\Escape\Escaper;

class GeneralContentEscaper extends AbstractEscaper
{
    /**
     * {@inheritdoc}
     */
    public function escape(string $content): string
    {
        // Process line by line for better context awareness
        $lines        = explode("\n", $content);
        $escapedLines = [];

        foreach ($lines as $line) {
            $escapedLines[] = $this->escapeLine($line);
        }

        return implode("\n", $escapedLines);
    }

    /**
     * Escape a single line with context awareness.
     *
     * @param string $line
     *
     * @return string
     */
    private function escapeLine(string $line): string
    {
        // Process in order, being careful about backslashes
        $result = '';
        $i      = 0;
        $len    = strlen($line);

        while ($i < $len) {
            $char = $line[$i];

            // Handle backslashes - escape them but skip the next character
            if ($char === '\\') {
                $result .= '\\\\';
                $i++;
                if ($i < $len) {
                    $result .= $line[$i];
                    $i++;
                }
                continue;
            }

            // For start of line, check if we need to escape
            if ($i === 0) {
                $remaining = substr($line, $i);

                // Check for headers
                if (preg_match('/^(\s*)(#+)/', $remaining, $matches)) {
                    $result .= $matches[1] . '\\' . $matches[2];
                    $i += strlen($matches[1]) + strlen($matches[2]);
                    continue;
                }
                // Check for blockquotes
                elseif (preg_match('/^(\s*)(>+)/', $remaining, $matches)) {
                    $result .= $matches[1] . '\\' . $matches[2];
                    $i += strlen($matches[1]) + strlen($matches[2]);
                    continue;
                }
                // Check for lists
                elseif (preg_match('/^(\s*)([-+*])/', $remaining, $matches)) {
                    $result .= $matches[1] . '\\' . $matches[2];
                    $i += strlen($matches[1]) + strlen($matches[2]);
                    continue;
                }
                // Check for ordered lists (must have space after period)
                elseif (preg_match('/^(\s*)(\d+)(\.)(\s)/', $remaining, $matches)) {
                    $result .= $matches[1] . $matches[2] . '\\' . $matches[3];
                    $i += strlen($matches[1]) + strlen($matches[2]) + strlen($matches[3]);
                    continue;
                }
            }

            // Handle other special characters
            switch ($char) {
                case '*':
                    $result .= '\\' . $char;
                    break;

                case '_':
                    // Always escape underscores to prevent unintended emphasis
                    $result .= '\\' . $char;
                    break;

                case '[':
                case ']':
                case '(':
                case ')':
                case '`':
                case '|':
                case '{':
                case '}':
                case '+':
                    $result .= '\\' . $char;
                    break;

                case '!':
                    // Only escape if followed by [
                    if ($i < $len - 1 && $line[$i + 1] === '[') {
                        $result .= '\\!';
                    } else {
                        $result .= '!';
                    }
                    break;

                case '#':
                    // # is already handled at line start, but custom dialects might want it always escaped
                    $specialChars = $this->dialect->getSpecialCharacters($this->context);
                    if (in_array($char, $specialChars) && !in_array($this->dialect->getName(), ['commonmark', 'gfm'])) {
                        $result .= '\\' . $char;
                    } else {
                        $result .= $char;
                    }
                    break;

                case '@':
                case ':':
                case '~':
                    // These are special in GFM but not CommonMark
                    $specialChars = $this->dialect->getSpecialCharacters($this->context);
                    if (in_array($char, $specialChars)) {
                        $result .= '\\' . $char;
                    } else {
                        $result .= $char;
                    }
                    break;

                default:
                    $result .= $char;
            }

            $i++;
        }

        return $result;
    }
}
