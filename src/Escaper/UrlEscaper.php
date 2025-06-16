<?php

declare(strict_types=1);

namespace Markdown\Escape\Escaper;

class UrlEscaper extends AbstractEscaper
{
    /**
     * {@inheritdoc}
     */
    public function escape(string $content): string
    {
        if ($this->isValidUrl($content)) {
            return $this->escapeUrl($content);
        }

        return parent::escape($content);
    }

    /**
     * {@inheritdoc}
     */
    protected function postProcess(string $content): string
    {
        $options = $this->context->getOptions();

        if (isset($options['encode_unicode']) && $options['encode_unicode']) {
            $content = $this->encodeUnicodeCharacters($content);
        }

        return $content;
    }

    /**
     * Check if the given string is a valid URL.
     *
     * @param string $url
     *
     * @return bool
     */
    private function isValidUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Escape a URL for use in Markdown.
     *
     * @param string $url
     *
     * @return string
     */
    private function escapeUrl(string $url): string
    {
        $parsed = parse_url($url);

        if ($parsed === false) {
            return $url;
        }

        $result = '';

        if (isset($parsed['scheme'])) {
            $result .= $parsed['scheme'] . '://';
        }

        if (isset($parsed['user'])) {
            $result .= $parsed['user'];
            if (isset($parsed['pass'])) {
                $result .= ':' . $parsed['pass'];
            }
            $result .= '@';
        }

        if (isset($parsed['host'])) {
            $result .= $parsed['host'];
        }

        if (isset($parsed['port'])) {
            $result .= ':' . $parsed['port'];
        }

        if (isset($parsed['path'])) {
            $result .= $this->escapePath($parsed['path']);
        }

        if (isset($parsed['query'])) {
            $result .= '?' . $this->escapeQuery($parsed['query']);
        }

        if (isset($parsed['fragment'])) {
            $result .= '#' . $this->escapeFragment($parsed['fragment']);
        }

        return $result;
    }

    /**
     * Escape the path component of a URL.
     *
     * @param string $path
     *
     * @return string
     */
    private function escapePath(string $path): string
    {
        $segments = explode('/', $path);

        foreach ($segments as &$segment) {
            $segment = rawurlencode($segment);
        }

        return implode('/', $segments);
    }

    /**
     * Escape the query component of a URL.
     *
     * @param string $query
     *
     * @return string
     */
    private function escapeQuery(string $query): string
    {
        parse_str($query, $params);

        return http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Escape the fragment component of a URL.
     *
     * @param string $fragment
     *
     * @return string
     */
    private function escapeFragment(string $fragment): string
    {
        return rawurlencode($fragment);
    }

    /**
     * Encode Unicode characters in the URL.
     *
     * @param string $content
     *
     * @return string
     */
    private function encodeUnicodeCharacters(string $content): string
    {
        $result = preg_replace_callback('/[\x{80}-\x{10FFFF}]/u', function ($matches) {
            return rawurlencode($matches[0]);
        }, $content);

        return $result ?? $content;
    }
}
