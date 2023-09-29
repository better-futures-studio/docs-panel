# A Filament Panel for your applications public documentation.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/abdelelrafa/docs-panel.svg?style=flat-square)](https://packagist.org/packages/abdelelrafa/docs-panel)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/abdelelrafa/docs-panel/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/abdelelrafa/docs-panel/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/abdelelrafa/docs-panel/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/abdelelrafa/docs-panel/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/abdelelrafa/docs-panel.svg?style=flat-square)](https://packagist.org/packages/abdelelrafa/docs-panel)



This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Installation

You can install the package via composer:

```bash
composer require abdelelrafa/docs-panel
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="docs-panel-views"
```

## Usage

```php
$docsPanel = new AbdelElrafa\DocsPanel();
echo $docsPanel->echoPhrase('Hello, AbdelElrafa!');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Abdel Elrafa](https://github.com/AbdelElrafa)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
