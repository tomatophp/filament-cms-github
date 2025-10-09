![Screenshot](https://raw.githubusercontent.com/tomatophp/filament-cms-github/master/art/screenshot.jpg)

# Filament cms github

[![Latest Stable Version](https://poser.pugx.org/tomatophp/filament-cms-github/version.svg)](https://packagist.org/packages/tomatophp/filament-cms-github)
[![License](https://poser.pugx.org/tomatophp/filament-cms-github/license.svg)](https://packagist.org/packages/tomatophp/filament-cms-github)
[![Downloads](https://poser.pugx.org/tomatophp/filament-cms-github/d/total.svg)](https://packagist.org/packages/tomatophp/filament-cms-github)

Github integration to import your repo docs README files as a docs on markdown format to TomatoPHP CMS

## Installation

```bash
composer require tomatophp/filament-cms-github
```
after install your package please run this command

```bash
php artisan filament-cms-github:install
```

finally register the plugin on `/app/Providers/Filament/AdminPanelProvider.php`

```php
->plugin(\TomatoPHP\FilamentCmsGithub\FilamentCmsGithubPlugin::make())
```


## Publish Assets

you can publish config file by use this command

```bash
php artisan vendor:publish --tag="filament-cms-github-config"
```

you can publish views file by use this command

```bash
php artisan vendor:publish --tag="filament-cms-github-views"
```

you can publish languages file by use this command

```bash
php artisan vendor:publish --tag="filament-cms-github-lang"
```

you can publish migrations file by use this command

```bash
php artisan vendor:publish --tag="filament-cms-github-migrations"
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security

Please see [SECURITY](SECURITY.md) for more information about security.

## Credits

- [Fady Mondy](mailto:info@3x1.io)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
