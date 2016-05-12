# Laravel PHP Framework

[![Build Status](https://travis-ci.org/laravel/framework.svg)](https://travis-ci.org/laravel/framework)
[![Total Downloads](https://poser.pugx.org/laravel/framework/d/total.svg)](https://packagist.org/packages/laravel/framework)
[![Latest Stable Version](https://poser.pugx.org/laravel/framework/v/stable.svg)](https://packagist.org/packages/laravel/framework)
[![Latest Unstable Version](https://poser.pugx.org/laravel/framework/v/unstable.svg)](https://packagist.org/packages/laravel/framework)
[![License](https://poser.pugx.org/laravel/framework/license.svg)](https://packagist.org/packages/laravel/framework)

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable, creative experience to be truly fulfilling. Laravel attempts to take the pain out of development by easing common tasks used in the majority of web projects, such as authentication, routing, sessions, queueing, and caching.

Laravel is accessible, yet powerful, providing tools needed for large, robust applications. A superb inversion of control container, expressive migration system, and tightly integrated unit testing support give you the tools you need to build any application with which you are tasked.

## Instructions

Make sure you have all of the requirements shown in the [Laravel install page](https://www.laravel.com/docs/5.2/installation).

Create a file named .env and add the following variables:

```
APP_KEY=

DB_CONNECTION=(database type: e.g. mysql or sqlite)
DB_DATABASE=(database name or path)
DB_HOST=(the host ip or hostname, not needed with sqlite)
DB_PORT=(the port the server is hosted on, not needed with sqlite)
DB_USERNAME=(the username to use, not needed with sqlite)
DB_PASSWORD=(the password to use, not needed with sqlite)
```

Navigate to the directory in a terminal and run
```
$composer install
$php artisan key:generate
$php artisan migrate
$php artisan db:seed
$php artisan serve
```

You can now connect to the site at [http://localhost:8000](http://localhost:8000)

## Official Documentation

Documentation for the framework can be found on the [Laravel website](http://laravel.com/docs).

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](http://laravel.com/docs/contributions).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell at taylor@laravel.com. All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).
