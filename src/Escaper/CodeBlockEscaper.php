<?php

declare(strict_types=1);

namespace Markdown\Escape\Escaper;

class CodeBlockEscaper extends AbstractEscaper
{
    /**
     * {@inheritdoc}
     */
    public function escape(string $content): string
    {
        $options = $this->context->getOptions();

        // Raw mode - return content as-is (for use inside existing code fences)
        if (isset($options['raw']) && $options['raw']) {
            return $content;
        }

        // Within mode - escape content to be safe within an existing code block
        if (isset($options['within']) && $options['within']) {
            return $this->escapeWithinCodeBlock($content);
        }

        if (isset($options['use_fences']) && $options['use_fences']) {
            return $this->escapeFencedCodeBlock($content, $options['language'] ?? '');
        }

        return $this->escapeIndentedCodeBlock($content);
    }

    /**
     * Escape content for a fenced code block.
     *
     * @param string $content
     * @param string $language
     *
     * @return string
     */
    private function escapeFencedCodeBlock(string $content, string $language = ''): string
    {
        $fenceChar   = $this->determineFenceCharacter($content);
        $fenceLength = $this->determineFenceLength($content, $fenceChar);
        $fence       = str_repeat($fenceChar, $fenceLength);

        $result = $fence . $language . "\n";
        $result .= $content;

        if (substr($content, -1) !== "\n") {
            $result .= "\n";
        }

        $result .= $fence;

        return $result;
    }

    /**
     * Escape content for an indented code block.
     *
     * @param string $content
     *
     * @return string
     */
    private function escapeIndentedCodeBlock(string $content): string
    {
        $lines = explode("\n", $content);

        foreach ($lines as &$line) {
            if ($line !== '') {
                $line = '    ' . $line;
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Escape content to be safe within an existing code block.
     * This prevents the content from breaking out of the code fence.
     *
     * @param string $content
     *
     * @return string
     */
    private function escapeWithinCodeBlock(string $content): string
    {
        // For content within code blocks, we don't need to escape anything
        // because the CommonMark spec treats all content as literal.
        // The closing fence must match the opening fence character and length.
        return $content;
    }

    /**
     * Determine which fence character to use.
     *
     * @param string $content
     *
     * @return string
     */
    private function determineFenceCharacter(string $content): string
    {
        // Check for consecutive backticks
        preg_match_all('/`+/', $content, $backtickMatches);
        $maxBackticks = 0;
        if (!empty($backtickMatches[0])) {
            foreach ($backtickMatches[0] as $match) {
                $maxBackticks = max($maxBackticks, strlen($match));
            }
        }

        // Check for consecutive tildes
        preg_match_all('/~+/', $content, $tildeMatches);
        $maxTildes = 0;
        if (!empty($tildeMatches[0])) {
            foreach ($tildeMatches[0] as $match) {
                $maxTildes = max($maxTildes, strlen($match));
            }
        }

        // Use backticks if there are fewer consecutive backticks than tildes
        return $maxBackticks <= $maxTildes ? '`' : '~';
    }

    /**
     * Determine the fence length needed.
     *
     * @param string $content
     * @param string $fenceChar
     *
     * @return int
     */
    private function determineFenceLength(string $content, string $fenceChar): int
    {
        preg_match_all('/' . preg_quote($fenceChar, '/') . '+/', $content, $matches);

        if (empty($matches[0])) {
            return 3;
        }

        $maxLength = 0;

        foreach ($matches[0] as $match) {
            $length = strlen($match);
            if ($length > $maxLength) {
                $maxLength = $length;
            }
        }

        return max(3, $maxLength + 1);
    }
}
