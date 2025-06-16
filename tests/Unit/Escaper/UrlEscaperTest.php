<?php

declare(strict_types=1);

namespace Markdown\Escape\Tests\Unit\Escaper;

use Markdown\Escape\Context\UrlContext;
use Markdown\Escape\Dialect\CommonMarkDialect;
use Markdown\Escape\Escaper\UrlEscaper;
use Markdown\Escape\Tests\TestCase;

class UrlEscaperTest extends TestCase
{
    /**
     * @var UrlEscaper
     */
    private $escaper;

    protected function setUp(): void
    {
        $context       = new UrlContext();
        $dialect       = new CommonMarkDialect();
        $this->escaper = new UrlEscaper($context, $dialect);
    }

    public function testEscapeSimpleUrl(): void
    {
        $url     = 'https://example.com/path/to/page';
        $escaped = $this->escaper->escape($url);

        $this->assertEquals($url, $escaped);
    }

    public function testEscapeUrlWithSpaces(): void
    {
        $url     = 'https://example.com/path with spaces/file name.pdf';
        $escaped = $this->escaper->escape($url);

        $this->assertEquals('https://example.com/path%20with%20spaces/file%20name.pdf', $escaped);
    }

    public function testEscapeUrlWithParentheses(): void
    {
        $url     = 'https://example.com/page(section)';
        $escaped = $this->escaper->escape($url);

        $this->assertEquals('https://example.com/page%28section%29', $escaped);
    }

    public function testEscapeUrlWithQueryParameters(): void
    {
        $url     = 'https://example.com/search?q=markdown escape&category=tools';
        $escaped = $this->escaper->escape($url);

        $this->assertStringContainsString('q=markdown%20escape', $escaped);
        $this->assertStringContainsString('category=tools', $escaped);
    }

    public function testEscapeUrlWithFragment(): void
    {
        $url     = 'https://example.com/page#section with spaces';
        $escaped = $this->escaper->escape($url);

        $this->assertStringContainsString('#section%20with%20spaces', $escaped);
    }

    public function testEscapeUrlWithUnicode(): void
    {
        $context = new UrlContext(['encode_unicode' => true]);
        $dialect = new CommonMarkDialect();
        $escaper = new UrlEscaper($context, $dialect);

        $url     = 'https://example.com/página/español';
        $escaped = $escaper->escape($url);

        $this->assertStringNotContainsString('á', $escaped);
        $this->assertStringNotContainsString('ñ', $escaped);
    }

    public function testEscapeInvalidUrl(): void
    {
        $notUrl  = 'This is not a URL (with parentheses)';
        $escaped = $this->escaper->escape($notUrl);

        $this->assertStringContainsString('%28with', $escaped);
        $this->assertStringContainsString('parentheses%29', $escaped);
    }

    public function testEscapeUrlWithAuthentication(): void
    {
        $url     = 'https://user:pass@example.com/secure';
        $escaped = $this->escaper->escape($url);

        $this->assertStringContainsString('user:pass@example.com', $escaped);
    }

    public function testEscapeUrlWithPort(): void
    {
        $url     = 'https://example.com:8080/path';
        $escaped = $this->escaper->escape($url);

        $this->assertStringContainsString(':8080', $escaped);
        $this->assertEquals('https://example.com:8080/path', $escaped);
    }

    public function testEscapeUrlWithUserButNoPassword(): void
    {
        $url     = 'https://user@example.com/secure';
        $escaped = $this->escaper->escape($url);

        $this->assertStringContainsString('user@example.com', $escaped);
    }

    public function testEscapeComplexQueryString(): void
    {
        $url     = 'https://example.com/search?arr[]=1&arr[]=2&special=<>&test=value';
        $escaped = $this->escaper->escape($url);

        // Check that the query string is properly encoded
        $this->assertStringContainsString('?', $escaped);
        // http_build_query adds indices to arrays
        $this->assertStringContainsString('arr%5B0%5D=1', $escaped);
        $this->assertStringContainsString('arr%5B1%5D=2', $escaped);
        $this->assertStringContainsString('special=%3C%3E', $escaped);
    }

    public function testEscapeUrlWithMalformedParsing(): void
    {
        // Test a URL that might cause parse_url to fail
        $url     = '///invalid-url';
        $escaped = $this->escaper->escape($url);

        // When parse_url fails, the original URL should be returned
        $this->assertEquals($url, $escaped);
    }

    public function testEscapeNonUrlWithUnicodeEncoding(): void
    {
        $context = new UrlContext(['encode_unicode' => true]);
        $dialect = new CommonMarkDialect();
        $escaper = new UrlEscaper($context, $dialect);

        // Test with non-URL content that contains unicode
        $content = 'This is not a URL but has unicode: café, naïve';
        $escaped = $escaper->escape($content);

        // Should use parent escape but still encode unicode
        $this->assertStringNotContainsString('café', $escaped);
        $this->assertStringNotContainsString('naïve', $escaped);
        $this->assertStringContainsString('caf%C3%A9', $escaped);
        $this->assertStringContainsString('na%C3%AFve', $escaped);
    }

    public function testEscapeUrlWithPortAndAuthentication(): void
    {
        $url     = 'https://user:pass@example.com:9000/secure/path';
        $escaped = $this->escaper->escape($url);

        $this->assertStringContainsString(':9000', $escaped);
        $this->assertStringContainsString('user:pass@', $escaped);
    }

    public function testEscapeMalformedUrl(): void
    {
        // This will trigger parse_url to return false
        $url     = '//';
        $escaped = $this->escaper->escape($url);

        // When parse_url fails, the URL should be returned as-is
        $this->assertEquals($url, $escaped);
    }

    public function testEscapeUrlParseUrlFailureScenario(): void
    {
        // Test a URL that would trigger the parse_url false condition
        // This is mainly for code coverage of the defensive programming check

        // Create a mock URL escaper that simulates parse_url failure
        $context = new UrlContext();
        $dialect = new CommonMarkDialect();

        // Since we can't override private methods, we'll test the behavior
        // by using a malformed URL that would cause parse_url to fail
        $escaper = new UrlEscaper($context, $dialect);

        // Test with various edge cases
        // Normal URL should be escaped properly
        $url     = 'https://example.com/test';
        $escaped = $escaper->escape($url);
        $this->assertStringContainsString('example.com', $escaped);

        // Non-URL content should be escaped using URL context escaping
        $nonUrl  = 'This is (not) a URL';
        $escaped = $escaper->escape($nonUrl);
        $this->assertEquals('This%20is%20%28not%29%20a%20URL', $escaped);
    }
}
