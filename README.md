# Laravel Vue

Laravel with Inertia Vue 3.
## Steps

### Requirement

#### Local Machine
- MySQL
- PHP
- NginX with PHP-FPM
- Composer

#### Development Tools
VS Code Extensions
- Vue Language Features
- PHP Intelephense
- Taiwind CSS Intellisense
- PostCSS Language Support

### Initiate Laravel with Livewire

```shell
# create laravel project
composer create-project laravel/laravel laravel

# install jetstream
composer require laravel/jetstream
# stack: Vue with Inertia
# options: api support, darkmode, ssr, pest
php artisan jetstream:install

# cleanup
pnpm i && pnpm build
php artisan migrate
```

### Develop Project

```shell
# env
cp env.example .env

# migrate db
php artisan migrate

# generate key
php artisan key:generate

# start develop
php artisan serve # keep open
pnpm dev # keep open
```

### Deploy Project

```shell
# build assets
pnpm run build
```
