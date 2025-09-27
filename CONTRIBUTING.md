# Contributing to BitrixProLib

Thank you for your interest in contributing to BitrixProLib! This document provides guidelines and information for contributors.

## Code of Conduct

This project and everyone participating in it is governed by our Code of Conduct. By participating, you are expected to uphold this code.

## How to Contribute

### Reporting Bugs

Before creating bug reports, please check existing issues to avoid duplicates. When you create a bug report, please include as many details as possible:

- Use the bug report template
- Describe the exact steps to reproduce the problem
- Provide code examples
- Include your environment details (PHP version, OS, etc.)
- Add relevant log entries

### Suggesting Enhancements

Enhancement suggestions are tracked as GitHub issues. When creating an enhancement suggestion, please include:

- Use the feature request template
- Provide a clear and descriptive title
- Describe the current behavior and explain which behavior you expected
- Explain why this enhancement would be useful
- List some other applications where this enhancement exists

### Pull Requests

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Development Setup

### Prerequisites

- PHP 8.1 or higher
- Composer
- Git

### Installation

1. Clone your fork:
```bash
git clone https://github.com/your-username/bitrixprolib.git
cd bitrixprolib
```

2. Install dependencies:
```bash
composer install
```

3. Run tests to ensure everything works:
```bash
composer test
```

## Coding Standards

### PHP Standards

- Follow PSR-12 coding standard
- Use PHP 8.1+ features (typed properties, match expressions, etc.)
- Write comprehensive PHPDoc comments
- Use strict types declaration

### Code Quality

- All code must pass PHPStan level 8
- All code must pass PHP CodeSniffer
- Maintain 80%+ test coverage
- Write unit tests for new features

### Running Quality Checks

```bash
# Run all quality checks
composer quality

# Run individual checks
composer cs          # Code style
composer phpstan     # Static analysis
composer test        # Unit tests
```

## Testing

### Writing Tests

- Write tests for all new functionality
- Use descriptive test method names
- Follow the AAA pattern (Arrange, Act, Assert)
- Mock external dependencies
- Test both success and failure scenarios

### Running Tests

```bash
# Run all tests
composer test

# Run specific test suites
composer test:bitrix
composer test:trustme
composer test:mystore
composer test:utils

# Run with coverage
composer test:coverage
```

## Documentation

### Code Documentation

- Write comprehensive PHPDoc comments for all public methods
- Include parameter types and return types
- Provide usage examples in complex methods
- Document exceptions that may be thrown

### README Updates

- Update README.md for new features
- Add examples for new functionality
- Update installation instructions if needed
- Keep the changelog up to date

## Commit Messages

Use clear and descriptive commit messages:

```
feat: add new TrustMe document status method
fix: resolve memory leak in logger
docs: update API documentation
test: add unit tests for data validator
refactor: improve error handling in BitrixApi
```

### Commit Types

- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes (formatting, etc.)
- `refactor`: Code refactoring
- `test`: Adding or updating tests
- `chore`: Maintenance tasks

## Release Process

1. Update version numbers in `composer.json`
2. Update `CHANGELOG.md`
3. Create a release tag
4. Update documentation

## Getting Help

- Check existing issues and discussions
- Join our community discussions
- Contact maintainers for urgent issues

## License

By contributing to BitrixProLib, you agree that your contributions will be licensed under the MIT License.
