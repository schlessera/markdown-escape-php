<?php

declare(strict_types=1);

namespace Markdown\Escape\Escaper;

class InlineCodeEscaper extends AbstractEscaper
{
    /**
     * {@inheritdoc}
     */
    public function escape(string $content): string
    {
        $backtickCount = $this->countConsecutiveBackticks($content);

        if ($backtickCount === 0) {
            return '`' . $content . '`';
        }

        $delimiter = str_repeat('`', $backtickCount + 1);

        $needsSpace = (substr($content, 0, 1) === '`' || substr($content, -1) === '`');

        if ($needsSpace) {
            return $delimiter . ' ' . $content . ' ' . $delimiter;
        }

        return $delimiter . $content . $delimiter;
    }

    /**
     * Count the maximum number of consecutive backticks in the content.
     *
     * @param string $content
     *
     * @return int
     */
    private function countConsecutiveBackticks(string $content): int
    {
        preg_match_all('/`+/', $content, $matches);

        if (empty($matches[0])) {
            return 0;
        }

        $maxLength = 0;

        foreach ($matches[0] as $match) {
            $length = strlen($match);
            if ($length > $maxLength) {
                $maxLength = $length;
            }
        }

        return $maxLength;
    }
}
