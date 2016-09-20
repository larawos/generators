# Larawos's generators for laravel 5.3

If you are using laravel to develop large projects, you will find that you spend a lot of time to write some extra but necessary classes and methods.

This package will allow you to develop more easily and quickly, only this.

L5 includes a bunch of generators out of the box, so this package only needs to add a few things, like:

- `larawos:module`

*With one or two more to come.*

## Usage

### Step 1: Install Through Composer

```
composer require larawos/generators --dev
```

### Step 2: Add the Service Provider

You'll only want to use these generators for local development, so you don't want to update the production  `providers` array in `config/app.php`. Instead, add the provider in `app/Providers/AppServiceProvider.php`, like so:

```php
public function register()
{
    if ($this->app->environment() == 'local') {
        $this->app->register('Larawos\Generators\GeneratorsServiceProvider');
    }
}
```


### Step 3: Run Artisan!

You're all set. Run `php artisan` from the console, and you'll see the new commands in the `larawos:*` namespace section.

## Examples

- [Modules](#modules)

### Modules

```
php artisan larawos:module User
```

```
COLUMN_NAME:COLUMN_TYPE
```

Using the except or only when you need...

```
--except="store_request"
```

```
--only="model,repository,controller"
```

...this will give you:

- `model`
- `attribute and relationship trait for model`
- `repository and contract for model.`
- `controller.`
- `service for controller.`
- `a lot of common request.`
- `persenter for view.`
- `optimized bind and route for module.`