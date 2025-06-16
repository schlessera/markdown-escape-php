<?php

/**
 * Example 02: Custom Templates
 * 
 * This example shows how to create and use custom templates from files.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Markdown\Escape\MarkdownTemplate;

// Create template files first
$templatesDir = __DIR__ . '/templates';
if (!is_dir($templatesDir)) {
    mkdir($templatesDir, 0755, true);
}

// Create a custom template file
file_put_contents($templatesDir . '/user-profile.php', <<<'PHP'
# <?= $md->escapeContent($user['name']) ?>

<?php if (isset($user['bio'])): ?>
> <?= $md->escapeContent($user['bio']) ?>

<?php endif; ?>
## Contact Information

- **Email**: [<?= $md->escapeContent($user['email']) ?>](mailto:<?= $md->escapeUrl($user['email']) ?>)
<?php if (isset($user['website'])): ?>
- **Website**: [<?= $md->escapeContent($user['website']) ?>](<?= $md->escapeUrl($user['website']) ?>)
<?php endif; ?>
<?php if (isset($user['github'])): ?>
- **GitHub**: [@<?= $md->escapeContent($user['github']) ?>](https://github.com/<?= $md->escapeUrl($user['github']) ?>)
<?php endif; ?>

<?php if (!empty($user['skills'])): ?>
## Skills

<?php foreach ($user['skills'] as $category => $skills): ?>
### <?= $md->escapeContent($category) ?>

<?php foreach ($skills as $skill): ?>
- <?= $md->escapeContent($skill) ?>
<?php endforeach; ?>

<?php endforeach; ?>
<?php endif; ?>

<?php if (!empty($user['projects'])): ?>
## Recent Projects

<?php foreach ($user['projects'] as $project): ?>
### [<?= $md->escapeContent($project['name']) ?>](<?= $md->escapeUrl($project['url']) ?>)

<?= $md->escapeContent($project['description']) ?>

**Technologies**: <?= implode(', ', array_map(function($tech) use ($md) {
    return '`' . $md->escapeInlineCode($tech) . '`';
}, $project['technologies'])) ?>

<?php endforeach; ?>
<?php endif; ?>
PHP
);

// Create a template instance with GitHub Flavored Markdown
$template = MarkdownTemplate::gfm();

// Add the custom templates directory
$template->addPath($templatesDir);

// Example 1: Render user profile
echo "=== Example 1: User Profile Template ===\n\n";

$result = $template->render('user-profile', [
    'user' => [
        'name' => 'Jane **Developer** Smith',
        'email' => 'jane@example.com',
        'bio' => 'Full-stack developer with a passion for *clean code* and [open source].',
        'website' => 'https://janesmith.dev',
        'github' => 'janesmith',
        'skills' => [
            'Languages' => ['PHP 8+', 'JavaScript/TypeScript', 'Python'],
            'Frameworks' => ['Laravel', 'Symfony', 'React', 'Vue.js'],
            'Tools' => ['Docker', 'Git', 'CI/CD', 'PHPStan'],
        ],
        'projects' => [
            [
                'name' => 'Markdown **Escape** Library',
                'url' => 'https://github.com/janesmith/markdown-escape',
                'description' => 'A library for safely escaping content in markdown documents.',
                'technologies' => ['PHP', 'PHPUnit', 'Composer'],
            ],
            [
                'name' => 'API [Documentation] Generator',
                'url' => 'https://github.com/janesmith/api-doc-gen',
                'description' => 'Automatically generates *beautiful* API documentation from code.',
                'technologies' => ['TypeScript', 'Node.js', 'Express'],
            ],
        ],
    ],
]);

echo $result . "\n\n";

// Create another custom template for release notes
file_put_contents($templatesDir . '/release-notes.php', <<<'PHP'
# Release Notes - <?= $md->escapeContent($version) ?>

Released on <?= $date ?>

<?php if (isset($highlights)): ?>
## 🎉 Highlights

<?php foreach ($highlights as $highlight): ?>
- <?= $md->escapeContent($highlight) ?>
<?php endforeach; ?>

<?php endif; ?>
<?php foreach ($sections as $section): ?>
## <?= $section['emoji'] ?? '📝' ?> <?= $md->escapeContent($section['title']) ?>

<?php foreach ($section['items'] as $item): ?>
- <?= $md->escapeContent($item['description']) ?><?php if (isset($item['issue'])): ?> (#<?= $item['issue'] ?>)<?php endif; ?>
<?php if (isset($item['details'])): ?>
  - <?= $md->escapeContent($item['details']) ?>
<?php endif; ?>
<?php endforeach; ?>

<?php endforeach; ?>
<?php if (!empty($contributors)): ?>
## 👥 Contributors

Thanks to all contributors who made this release possible:

<?php foreach ($contributors as $contributor): ?>
- [@<?= $md->escapeContent($contributor) ?>](https://github.com/<?= $md->escapeUrl($contributor) ?>)
<?php endforeach; ?>
<?php endif; ?>

---

For full details, see the [changelog](<?= $md->escapeUrl($changelog_url) ?>).
PHP
);

// Example 2: Render release notes
echo "=== Example 2: Release Notes Template ===\n\n";

$result = $template->render('release-notes', [
    'version' => 'v2.0.0',
    'date' => date('F j, Y'),
    'highlights' => [
        'New **templating** system with PHP short tags',
        'Support for *custom* escapers',
        'Improved performance by [30%]',
    ],
    'sections' => [
        [
            'emoji' => '✨',
            'title' => 'New Features',
            'items' => [
                [
                    'description' => 'Added `MarkdownTemplate` facade for easy template rendering',
                    'issue' => 123,
                ],
                [
                    'description' => 'Implemented file-based and array-based template loaders',
                    'issue' => 124,
                    'details' => 'Supports template inheritance and overriding',
                ],
            ],
        ],
        [
            'emoji' => '🐛',
            'title' => 'Bug Fixes',
            'items' => [
                [
                    'description' => 'Fixed escaping of **nested** markdown in URLs',
                    'issue' => 125,
                ],
                [
                    'description' => 'Resolved issue with [special] characters in code blocks',
                    'issue' => 126,
                ],
            ],
        ],
        [
            'emoji' => '📚',
            'title' => 'Documentation',
            'items' => [
                [
                    'description' => 'Added comprehensive templating guide',
                ],
                [
                    'description' => 'Updated examples with template usage',
                ],
            ],
        ],
    ],
    'contributors' => ['janesmith', 'johndoe', 'alice-dev'],
    'changelog_url' => 'https://github.com/user/repo/blob/main/CHANGELOG.md',
]);

echo $result . "\n";

// Clean up
echo "\nNote: Custom template files were created in: " . $templatesDir . "\n";