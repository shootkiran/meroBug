<p align="center">
    <a href="https://www.merobug.com" target="_blank"><img width="130" src="https://www.merobug.com/images/merobug-logo-small.png"></a>
</p>

# MeroBug
Laravel 6.x/7.x/8.x/9.x package for logging errors to database

## Installation on laravel
You can install the package through Composer.
```bash
composer require shootkiran/merobug
```

Then publish the config and migration file of the package using the vendor publish command.
```bash
php artisan vendor:publish --provider="MeroBug\ServiceProvider"
```
And adjust config file (`config/merobug.php`) with your desired settings.

Note: by default only production environments will report errors. To modify this edit your MeroBug configuration.

## Reporting unhandled exceptions
You can use MeroBug as a log-channel by adding the following config to the `channels` section in `config/logging.php`:
```php
'channels' => [
    // ...
    'merobug' => [
        'driver' => 'merobug',
    ],
],
```
After that you can add it to the stack section:
```php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['single', 'merobug'],
    ],
    //...
],
```

PS: If you're using lumen, it could be that you don't have the `logging.php` file. So, you can use default logging file from
framework core and make changes above.
```bash
php -r "file_exists('config/') || mkdir('config/'); copy('vendor/laravel/lumen-framework/config/logging.php', 'config/logging.php');"
```

## License
The MeroBug package is open source software licensed under the [license MIT](http://opensource.org/licenses/MIT)
