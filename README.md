# A magic memoization function

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/once.svg?style=flat-square)](https://packagist.org/packages/spatie/once)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/spatie/once/master.svg?style=flat-square)](https://travis-ci.org/spatie/once)
[![SensioLabsInsight](https://img.shields.io/sensiolabs/i/c77ffbf0-f192-4cf6-8062-e7cc6db29297.svg?style=flat-square)](https://insight.sensiolabs.com/projects/c77ffbf0-f192-4cf6-8062-e7cc6db29297)
[![Quality Score](https://img.shields.io/scrutinizer/g/spatie/once.svg?style=flat-square)](https://scrutinizer-ci.com/g/spatie/once)
[![StyleCI](https://styleci.io/repos/73020509/shield?branch=master)](https://styleci.io/repos/73020509)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/once.svg?style=flat-square)](https://packagist.org/packages/spatie/once)

This package contains a `once` function. You can pass a `callable` to it. Here's quick example:

```php
class MyClass
{
    function getNumber()
    {
        return once(function () {
            return rand(1, 10000);
        });
    }
}
```
 
No matter how many times you run `(new MyClass())->getNumber()` inside the same request  you'll always get the same number.

Spatie is a webdesign agency based in Antwerp, Belgium. You'll find an overview of all our open source projects [on our website](https://spatie.be/opensource).

## Postcardware

You're free to use this package (it's [MIT-licensed](LICENSE.md)), but if it makes it to your production environment you are required to send us a postcard from your hometown, mentioning which of our package(s) you are using.

Our address is: Spatie, Samberstraat 69D, 2060 Antwerp, Belgium.

The best postcards will get published on the open source page on our website.

## Installation

You can install the package via composer:

``` bash
composer require spatie/once
```

## Usage

The `once` function accepts a `callable`.

```php
class MyClass
{
    function getNumber()
    {
        return once(function () {
            return rand(1, 10000);
        });
    }
}
```

No matter how many times you run `(new MyClass())->getNumber()` you'll always get the same number.

The `once` function will only run once per combination of argument values the containing method receives.

```php
class MyClass
{
    public function getNumberForLetter($letter)
    {
        return once(function () use ($letter) {
            return $letter . rand(1, 10000000);
        });
    }
}
```

So calling `(new MyClass())->getNumberForLetter('A')` will always return the same result, but calling `(new MyClass())->getNumberForLetter('B')` will return something else.


## Behind the curtains

Let's go over the code of the `once` function to learn how all this magic works.

In short what the function will do is to execute the given callable and save the result in a an array in the `__memoized` property of the instance `once` was called in. When we detect that `once` has already run before, we're just going to return the value stored inside the `__memoized` array instead of executing the callable again.

The first thing it does it calling `debug_backtrace`. We'll use the output to determine in which function and class `once` is called and to get access to the `object` that function is running in. Yeah, we're already in voodoo-land. The output of the `debug_backtrace` is passed to a new instance of `Backtrace`. That class is just a simple wrapper so we can work more easily with the backtrace.

```php
$trace = debug_backtrace(
    DEBUG_BACKTRACE_PROVIDE_OBJECT, 2
)[1];

$backtrace = new Backtrace($trace);
```

Next, we're going to check if `once` was called from within an object. If it was called from a static method or outside a class, we just bail out.

```php
if (! $object = $backtrace->getObject()) {
   throw new Exception('Cannot use `once` outside a class');
}
```

Now that we're certain `once` is called within an instance of a class we're going to calculate a `hash` of the backtrace. This hash will be unique per function `once` was called in an the values of the arguments that function receives.

```php
$hash = $backtrace->getArgumentHash();
```

Finally we will check if there's already a value stored for the given hash. If not, then execute the given `$callback` and store the result in the `__memoized` array on the object. In the other case just return the value in the `__memoized` array (the `$callback` isn't executed). 

```php
if (! isset($object->__memoized[$backtrace->getFunctionName()][$hash])) {
   $result = call_user_func($callback, $backtrace->getArguments());
   $object->__memoized[$backtrace->getFunctionName()][$hash] = $result;
}
```

## Caveats

- you can only use the `once` function in non-static class methods
- if you need to serialize an object that uses `once` be sure to `unset` the `__memoized` property. A perfect place for that would be [the `__sleep` magic method](http://php.net/manual/en/oop4.magic-functions.php)

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email freek@spatie.be instead of using the issue tracker.

## Credits

- [Freek Van der Herten](https://github.com/freekmurze)
- [All Contributors](../../contributors)

Credit for the idea of the `once` function goes to [Taylor Otwell](https://twitter.com/taylorotwell/status/794622206567444481). The code for this package is based upon the code he was kind enough to share with us.

## About Spatie
Spatie is a webdesign agency based in Antwerp, Belgium. You'll find an overview of all our open source projects [on our website](https://spatie.be/opensource).

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
