<?php

declare(strict_types=1);

namespace Markdown\Escape\Tests\Feature;

use Markdown\Escape\MarkdownTemplate;
use Markdown\Escape\Tests\TestCase;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

class RealWorldTemplatingTest extends TestCase
{
    /**
     * @var vfsStreamDirectory
     */
    private $root;

    /**
     * @var string
     */
    private $rootPath;

    protected function setUp(): void
    {
        $this->root     = vfsStream::setup('project');
        $this->rootPath = vfsStream::url('project');
    }

    public function testGenerateReadmeForPhpPackage(): void
    {
        // Create templates directory
        vfsStream::newDirectory('templates')->at($this->root);

        // Create README template
        vfsStream::newFile('templates/readme.php')
            ->at($this->root)
            ->setContent(
                <<<'PHP'
# <?= $md->escapeContent($package['name']) ?>

<?php if (!empty($badges)): ?>
<?php foreach ($badges as $badge): ?>
[![<?= $md->escapeContent($badge['alt']) ?>](<?= $md->escapeUrl($badge['image']) ?>)](<?= $md->escapeUrl($badge['link']) ?>)
<?php endforeach; ?>

<?php endif; ?>
<?= $md->escapeContent($package['description']) ?>

## Installation

Install the latest version with:

```bash
<?= $md->escapeWithinCodeBlock($installation['command']) ?>

```

<?php if (!empty($installation['requirements'])): ?>
### Requirements

<?php foreach ($installation['requirements'] as $req): ?>
- <?= $md->escapeContent($req) ?>

<?php endforeach; ?>

<?php endif; ?>
## Basic Usage

```php
<?= $md->escapeWithinCodeBlock($usage['code']) ?>

```

<?php if (!empty($features)): ?>
## Features

<?php foreach ($features as $feature): ?>
### <?= $md->escapeContent($feature['title']) ?>

<?= $md->escapeContent($feature['description']) ?>

<?php if (!empty($feature['example'])): ?>
```php
<?= $md->escapeWithinCodeBlock($feature['example']) ?>

```

<?php endif; ?>
<?php endforeach; ?>

<?php endif; ?>
## Documentation

<?php foreach ($docs as $doc): ?>
- [<?= $md->escapeContent($doc['title']) ?>](<?= $md->escapeUrl($doc['url']) ?>)

<?php endforeach; ?>

## Contributing

<?= $md->escapeContent($contributing['message']) ?>

## License

<?= $md->escapeContent($license['text']) ?>
PHP
            );

        $template = MarkdownTemplate::gfm();
        $template->addPath($this->rootPath . '/templates');

        $result = $template->render('readme', [
            'package' => [
                'name'        => 'My/Package - A **Great** Library',
                'description' => 'This package provides *excellent* functionality for [various] use cases.',
            ],
            'badges' => [
                [
                    'alt'   => 'Build Status',
                    'image' => 'https://img.shields.io/travis/user/repo.svg?style=flat-square',
                    'link'  => 'https://travis-ci.org/user/repo',
                ],
                [
                    'alt'   => 'Coverage Status',
                    'image' => 'https://img.shields.io/codecov/c/github/user/repo.svg?style=flat-square',
                    'link'  => 'https://codecov.io/gh/user/repo',
                ],
            ],
            'installation' => [
                'command'      => 'composer require my/package:^2.0',
                'requirements' => [
                    'PHP >= 7.4',
                    'ext-mbstring * extension',
                    'Some [other] requirement',
                ],
            ],
            'usage' => [
                'code' => '<?php
use My\Package\Client;

$client = new Client();
$result = $client->process("**markdown** text");
echo $result; // Escaped output',
            ],
            'features' => [
                [
                    'title'       => 'Context-Aware Escaping',
                    'description' => 'Escapes content based on *context* (URLs, code blocks, etc.)',
                    'example'     => '// URL escaping
$url = "http://example.com/path(with)parens";
$escaped = $escaper->escapeUrl($url);',
                ],
                [
                    'title'       => 'Multiple Dialect Support',
                    'description' => 'Supports CommonMark and GitHub Flavored Markdown',
                    'example'     => null,
                ],
            ],
            'docs' => [
                ['title' => 'API Documentation', 'url' => './docs/api.md'],
                ['title' => 'User Guide', 'url' => './docs/guide.md'],
                ['title' => 'Examples', 'url' => './examples/'],
            ],
            'contributing' => [
                'message' => 'Contributions are **welcome**! Please read [CONTRIBUTING.md](CONTRIBUTING.md) for details.',
            ],
            'license' => [
                'text' => 'This library is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.',
            ],
        ]);

        // Verify proper escaping and structure
        $this->assertStringContainsString('# My/Package - A \\*\\*Great\\*\\* Library', $result);
        $this->assertStringContainsString('provides \\*excellent\\* functionality for \\[various\\] use cases', $result);
        $this->assertStringContainsString('composer require my/package:^2.0', $result);
        $this->assertStringContainsString('PHP >= 7.4', $result);
        $this->assertStringContainsString('ext-mbstring \\* extension', $result);
        $this->assertStringContainsString('$result = $client->process("**markdown** text");', $result);
        $this->assertStringContainsString('Contributions are \\*\\*welcome\\*\\*!', $result);
    }

    public function testGenerateApiDocumentation(): void
    {
        // Create templates directory
        vfsStream::newDirectory('templates')->at($this->root);

        // Create API documentation template
        vfsStream::newFile('templates/api-docs.php')
            ->at($this->root)
            ->setContent(
                <<<'PHP'
# <?= $md->escapeContent($api['title']) ?>

<?= $md->escapeContent($api['description']) ?>

Base URL: <?= $md->escapeInlineCode($api['base_url']) ?>

## Authentication

<?= $md->escapeContent($auth['description']) ?>

```http
<?= $md->escapeWithinCodeBlock($auth['example']) ?>

```

## Endpoints

<?php foreach ($endpoints as $endpoint): ?>
### <?= $endpoint['method'] ?> <?= $md->escapeInlineCode($endpoint['path']) ?>

<?= $md->escapeContent($endpoint['description']) ?>

<?php if (!empty($endpoint['parameters'])): ?>
#### Parameters

| Name | Type | Required | Description |
| --- | --- | --- | --- |
<?php foreach ($endpoint['parameters'] as $param): ?>
| <?= $md->escapeInlineCode($param['name']) ?> | <?= $md->escapeContent($param['type']) ?> | <?= $param['required'] ? 'Yes' : 'No' ?> | <?= $md->escapeContent($param['description']) ?> |
<?php endforeach; ?>

<?php endif; ?>
<?php if (!empty($endpoint['request_body'])): ?>
#### Request Body

```json
<?= $md->escapeWithinCodeBlock($endpoint['request_body']) ?>

```

<?php endif; ?>
<?php if (!empty($endpoint['responses'])): ?>
#### Responses

<?php foreach ($endpoint['responses'] as $response): ?>
**<?= $response['status'] ?>** - <?= $md->escapeContent($response['description']) ?>

```json
<?= $md->escapeWithinCodeBlock($response['example']) ?>

```

<?php endforeach; ?>
<?php endif; ?>

---

<?php endforeach; ?>

## Error Codes

| Code | Description |
| --- | --- |
<?php foreach ($errors as $error): ?>
| <?= $md->escapeInlineCode($error['code']) ?> | <?= $md->escapeContent($error['description']) ?> |
<?php endforeach; ?>

## Rate Limiting

<?= $md->escapeContent($rate_limiting['description']) ?>

Example response headers:
```http
<?= $md->escapeWithinCodeBlock($rate_limiting['headers']) ?>

```
PHP
            );

        $template = MarkdownTemplate::gfm();
        $template->addPath($this->rootPath . '/templates');

        $result = $template->render('api-docs', [
            'api' => [
                'title'       => 'REST API v2.0 - **Production** Ready',
                'description' => 'Our API provides *secure* access to [all] resources.',
                'base_url'    => 'https://api.example.com/v2',
            ],
            'auth' => [
                'description' => 'Use Bearer token authentication. Tokens can be obtained via the `/auth/token` endpoint.',
                'example'     => 'Authorization: Bearer <your-token-here>',
            ],
            'endpoints' => [
                [
                    'method'      => 'GET',
                    'path'        => '/users/{id}',
                    'description' => 'Retrieve a user by their ID. Returns `404` if not found.',
                    'parameters'  => [
                        ['name' => 'id', 'type' => 'integer', 'required' => true, 'description' => 'User ID'],
                        ['name' => 'include[]', 'type' => 'array', 'required' => false, 'description' => 'Related resources to include (posts, comments)'],
                    ],
                    'request_body' => null,
                    'responses'    => [
                        [
                            'status'      => '200 OK',
                            'description' => 'User found',
                            'example'     => '{
  "id": 123,
  "name": "John **Doe**",
  "email": "john@example.com",
  "created_at": "2024-01-01T00:00:00Z"
}',
                        ],
                        [
                            'status'      => '404 Not Found',
                            'description' => 'User not found',
                            'example'     => '{
  "error": "User not found",
  "code": "USER_NOT_FOUND"
}',
                        ],
                    ],
                ],
                [
                    'method'       => 'POST',
                    'path'         => '/users',
                    'description'  => 'Create a new user. Email must be *unique*.',
                    'parameters'   => [],
                    'request_body' => '{
  "name": "Jane [Admin] Doe",
  "email": "jane@example.com",
  "password": "secure*password123"
}',
                    'responses' => [
                        [
                            'status'      => '201 Created',
                            'description' => 'User created successfully',
                            'example'     => '{
  "id": 124,
  "name": "Jane [Admin] Doe",
  "email": "jane@example.com"
}',
                        ],
                    ],
                ],
            ],
            'errors' => [
                ['code' => 'AUTH_FAILED', 'description' => 'Authentication failed - check your token'],
                ['code' => 'RATE_LIMITED', 'description' => 'Too many requests - see `X-RateLimit-*` headers'],
                ['code' => 'VALIDATION_ERROR', 'description' => 'Request validation failed - check error details'],
            ],
            'rate_limiting' => [
                'description' => 'API requests are limited to **1000** per hour per token.',
                'headers'     => 'X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 999
X-RateLimit-Reset: 1640995200',
            ],
        ]);

        // Verify the API documentation is properly generated
        $this->assertStringContainsString('# REST API v2.0 - \\*\\*Production\\*\\* Ready', $result);
        $this->assertStringContainsString('provides \\*secure\\* access to \\[all\\] resources', $result);
        $this->assertStringContainsString('Base URL: `https://api.example.com/v2`', $result);
        $this->assertStringContainsString('### GET `/users/{id}`', $result);
        $this->assertStringContainsString('Returns \\`404\\` if not found', $result);
        $this->assertStringContainsString('| `include[]` | array | No |', $result);
        $this->assertStringContainsString('"name": "John **Doe**"', $result);
        $this->assertStringContainsString('Email must be \\*unique\\*', $result);
        $this->assertStringContainsString('"password": "secure*password123"', $result);
        $this->assertStringContainsString('limited to \\*\\*1000\\*\\* per hour', $result);
    }

    public function testGenerateChangelogFromData(): void
    {
        // Create templates directory
        vfsStream::newDirectory('templates')->at($this->root);

        // Create changelog template
        vfsStream::newFile('templates/changelog.php')
            ->at($this->root)
            ->setContent(
                <<<'PHP'
# Changelog

All notable changes to `<?= $md->escapeContent($project_name) ?>` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

<?php foreach ($releases as $release): ?>
## [<?= $md->escapeContent($release['version']) ?>] - <?= $release['date'] ?>

<?php if (!empty($release['summary'])): ?>
<?= $md->escapeContent($release['summary']) ?>

<?php endif; ?>
<?php foreach ($release['changes'] as $type => $changes): ?>
<?php if (!empty($changes)): ?>
### <?= ucfirst($type) ?>

<?php foreach ($changes as $change): ?>
- <?= $md->escapeContent($change['description']) ?><?php if (isset($change['pr'])): ?> ([#<?= $change['pr'] ?>](<?= $md->escapeUrl($change['pr_url']) ?>))<?php endif; ?><?php if (isset($change['breaking']) && $change['breaking']): ?> **BREAKING**<?php endif; ?>

<?php endforeach; ?>

<?php endif; ?>
<?php endforeach; ?>
<?php endforeach; ?>

[Unreleased]: <?= $md->escapeUrl($unreleased_url) ?>

<?php foreach ($compare_urls as $compare): ?>
[<?= $md->escapeContent($compare['version']) ?>]: <?= $md->escapeUrl($compare['url']) ?>

<?php endforeach; ?>
PHP
            );

        $template = MarkdownTemplate::commonMark();
        $template->addPath($this->rootPath . '/templates');

        $result = $template->render('changelog', [
            'project_name' => 'my/package',
            'releases'     => [
                [
                    'version' => 'v2.0.0',
                    'date'    => '2024-01-15',
                    'summary' => 'Major release with **breaking changes** and new features!',
                    'changes' => [
                        'added' => [
                            ['description' => 'New templating system with PHP short tags', 'pr' => 123, 'pr_url' => 'https://github.com/user/repo/pull/123'],
                            ['description' => 'Support for custom escapers in templates', 'pr' => 124, 'pr_url' => 'https://github.com/user/repo/pull/124'],
                        ],
                        'changed' => [
                            ['description' => 'Refactored `escape()` method signature', 'breaking' => true, 'pr' => 125, 'pr_url' => 'https://github.com/user/repo/pull/125'],
                        ],
                        'fixed' => [
                            ['description' => 'Fixed escaping of URLs with parentheses (like Wikipedia links)', 'pr' => 126, 'pr_url' => 'https://github.com/user/repo/pull/126'],
                        ],
                        'deprecated' => [],
                        'removed'    => [
                            ['description' => 'Removed legacy `escapeText()` method - use `escapeContent()` instead', 'breaking' => true],
                        ],
                    ],
                ],
                [
                    'version' => 'v1.2.3',
                    'date'    => '2023-12-01',
                    'summary' => null,
                    'changes' => [
                        'fixed' => [
                            ['description' => 'Fixed edge case with nested **markdown** in [links]'],
                            ['description' => 'Improved performance by ~30% for large documents'],
                        ],
                    ],
                ],
            ],
            'unreleased_url' => 'https://github.com/user/repo/compare/v2.0.0...HEAD',
            'compare_urls'   => [
                ['version' => 'v2.0.0', 'url' => 'https://github.com/user/repo/compare/v1.2.3...v2.0.0'],
                ['version' => 'v1.2.3', 'url' => 'https://github.com/user/repo/compare/v1.2.2...v1.2.3'],
            ],
        ]);

        // Verify changelog structure
        $this->assertStringContainsString('# Changelog', $result);
        $this->assertStringContainsString('All notable changes to `my/package`', $result);
        $this->assertStringContainsString('## [v2.0.0] - 2024-01-15', $result);
        $this->assertStringContainsString('Major release with \\*\\*breaking changes\\*\\* and new features!', $result);
        $this->assertStringContainsString('### Added', $result);
        $this->assertStringContainsString('- New templating system with PHP short tags ([#123]', $result);
        $this->assertStringContainsString('### Changed', $result);
        $this->assertStringContainsString('Refactored \\`escape\\(\\)\\` method signature', $result);
        $this->assertStringContainsString('**BREAKING**', $result);
        $this->assertStringContainsString('### Fixed', $result);
        $this->assertStringContainsString('Fixed escaping of URLs with parentheses \\(like Wikipedia links\\)', $result);
        $this->assertStringContainsString('Fixed edge case with nested \\*\\*markdown\\*\\* in \\[links\\]', $result);
        $this->assertStringContainsString('[v2.0.0]: https://github.com/user/repo/compare/v1.2.3...v2.0.0', $result);
    }

    public function testGenerateTechnicalReport(): void
    {
        // Create report template using built-in templates
        $template = MarkdownTemplate::gfm();

        $reportData = [
            'title'       => 'Performance Analysis - Q4 2023',
            'description' => 'Analysis of system performance with **critical** findings.',
            'sections'    => [
                [
                    'title'   => 'Executive Summary',
                    'content' => 'System performance has *improved* by 45% with [new optimizations].',
                ],
                [
                    'title'   => 'Metrics Overview',
                    'content' => 'Key metrics showing response times and throughput:',
                ],
            ],
        ];

        // First section with description
        $part1 = $template->render('document', $reportData);

        // Metrics table
        $metricsTable = $template->render('table', [
            'headers' => ['Metric', 'Before', 'After', 'Improvement'],
            'rows'    => [
                ['Response Time (p50)', '120ms', '65ms', '**45.8%**'],
                ['Response Time (p99)', '850ms', '320ms', '**62.4%**'],
                ['Throughput', '1,200 req/s', '2,100 req/s', '**75%**'],
                ['Error Rate', '0.12%', '0.03%', '**75%** reduction'],
            ],
        ]);

        // Code examples
        $codeExample = $template->render('code-example', [
            'title'       => 'Optimization Example',
            'description' => 'Key optimization that improved performance:',
            'language'    => 'php',
            'code'        => '// Before: O(n²) complexity
foreach ($items as $item) {
    foreach ($relatedItems as $related) {
        if ($item->matches($related)) {
            $results[] = $item;
        }
    }
}

// After: O(n) complexity with indexing
$index = $relatedItems->buildIndex();
foreach ($items as $item) {
    if ($index->contains($item->getKey())) {
        $results[] = $item;
    }
}',
            'output' => 'Processing time reduced from 850ms to 45ms for 10,000 items',
        ]);

        // Recommendations list
        $recommendations = $template->render('list', [
            'items' => [
                'Implement caching for frequently accessed data',
                ['text' => 'Database optimizations:', 'subItems' => [
                    'Add indexes on `user_id` and `created_at` columns',
                    'Implement query result caching',
                    'Use read replicas for analytics queries',
                ]],
                'Upgrade to PHP 8.2 for additional **30%** performance gain',
                'Monitor memory usage during peak hours',
            ],
        ]);

        // Links
        $links = $template->render('link-list', [
            'links' => [
                ['text' => 'Full Performance Data', 'url' => 'https://metrics.example.com/dashboard?id=perf-2023-q4'],
                ['text' => 'Implementation Guide', 'url' => './docs/performance-guide.md', 'description' => 'Step-by-step optimization guide'],
                ['text' => 'Monitoring Dashboard', 'url' => 'https://monitor.example.com/', 'description' => 'Real-time metrics'],
            ],
        ]);

        // Combine all parts
        $fullReport = $part1 . "\n" . $metricsTable . "\n" . $codeExample . "\n## Recommendations\n\n" . $recommendations . "\n## Resources\n\n" . $links;

        // Verify the complete report
        $this->assertStringContainsString('# Performance Analysis - Q4 2023', $fullReport);
        $this->assertStringContainsString('Analysis of system performance with \\*\\*critical\\*\\* findings', $fullReport);
        $this->assertStringContainsString('System performance has \\*improved\\* by 45% with \\[new optimizations\\]', $fullReport);
        $this->assertStringContainsString('| Response Time \\(p50\\) | 120ms | 65ms | \\*\\*45.8%\\*\\* |', $fullReport);
        $this->assertStringContainsString('// Before: O(n²) complexity', $fullReport);
        $this->assertStringContainsString('Upgrade to PHP 8.2 for additional \\*\\*30%\\*\\* performance gain', $fullReport);
        $this->assertStringContainsString('[Full Performance Data](https://metrics.example.com/dashboard?id=perf-2023-q4)', $fullReport);
    }
}
