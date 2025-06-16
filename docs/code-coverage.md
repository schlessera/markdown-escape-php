# Code Coverage

This library aims for high code coverage to ensure reliability and maintainability.

## Coverage Goals

- **Minimum Coverage**: 80%
- **Target Coverage**: 90%+
- **Current Coverage**: Run `composer test:coverage` to generate report

## Running Coverage Tests

### Prerequisites

You need a code coverage driver installed. Check requirements:

```bash
php bin/check-coverage-requirements.php
```

If no driver is available, install one:

#### Option 1: PCOV (Recommended for CI/testing)

```bash
pecl install pcov
echo "extension=pcov.so" >> php.ini
```

#### Option 2: Xdebug (For development/debugging)

```bash
pecl install xdebug
echo "zend_extension=xdebug.so" >> php.ini
```

### Generate Coverage Reports

```bash
# HTML report with visual charts
composer test:coverage

# XML report for CI/CD tools
composer test:coverage:clover

# Generate coverage badge
composer coverage:badge
```

## Coverage Reports

### HTML Report

The HTML report provides:

- Line-by-line coverage visualization
- Method and class coverage metrics
- Interactive navigation through source code
- Coverage trends and statistics

View the report:

```bash
open coverage/html/index.html
```

### Clover XML Report

Used by CI/CD tools and coverage services like:

- Codecov
- Coveralls
- SonarQube
- Scrutinizer

### Text Report

Quick summary displayed in terminal showing:

- Lines of code covered/total
- Methods covered/total
- Classes covered/total
- Overall percentage

## Excluded from Coverage

The following are excluded from coverage reports:

- Exception classes (simple data containers)
- Test files
- Development scripts

## Continuous Integration

Coverage is automatically generated and reported on:

- Every push to main branch
- Every pull request
- Results posted to Codecov

## Improving Coverage

To improve test coverage:

1. Run coverage report
2. Identify uncovered lines in HTML report
3. Add tests for uncovered scenarios
4. Focus on:
   - Edge cases
   - Error conditions
   - Different input types
   - All code paths

## Coverage Badges

The project displays coverage badges in README showing:

- Current coverage percentage
- Color-coded status (red < 50%, yellow < 80%, green >= 80%)
