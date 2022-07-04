# Goodlord PHP Interview Test

# Requirements

- PHP 8.0
- Composer 2

# Running the command

After running `composer install`:

- `bin/console affordability-check ./files/bank_statement.csv ./files/properties.csv`

# Running the tests

- `php ./vendor/bin/phpunit`


# Running the web server

The simplest route, given that this is a barebones Symfony application -- and if you're on a Mac, is to install [Laravel Valet](https://laravel.com/docs/9.x/valet) and stick this repo into a parked directory. The homepage is the entry point to this app, a quite literal single page application :)
