Soccer Calendar
===============

*By [endroid](http://endroid.nl/)*

[![Latest Stable Version](http://img.shields.io/packagist/v/endroid/soccer-calendar.svg)](https://packagist.org/packages/endroid/soccer-calendar)
[![Build Status](http://img.shields.io/travis/endroid/SoccerCalendar.svg)](http://travis-ci.org/endroid/SoccerCalendar)
[![Total Downloads](http://img.shields.io/packagist/dt/endroid/soccer-calendar.svg)](https://packagist.org/packages/endroid/soccer-calendar)
[![Monthly Downloads](http://img.shields.io/packagist/dm/endroid/soccer-calendar.svg)](https://packagist.org/packages/endroid/soccer-calendar)
[![License](http://img.shields.io/packagist/l/endroid/soccer-calendar.svg)](https://packagist.org/packages/endroid/soccer-calendar)

This library generates soccer calendars.

## Installation

Use [Composer](https://getcomposer.org/) to install the library.

``` bash
$ composer require endroid/soccer-calendar
```

## Symfony integration

Register the Symfony bundle in the kernel.

```php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = [
        // ...
        new Endroid\SoccerCalendar\Bundle\EndroidSoccerCalendarBundle(),
    ];
}
```

Add the following section to your routing.

``` yml
EndroidSoccerCalendarBundle:
    resource: "@EndroidSoccerCalendarBundle/Controller/"
    type:     annotation
    prefix:   /soccer-calendar
```

## Versioning

Version numbers follow the MAJOR.MINOR.PATCH scheme. Backwards compatibility
breaking changes will be kept to a minimum but be aware that these can occur.
Lock your dependencies for production and test your code when upgrading.

## License

This bundle is under the MIT license. For the full copyright and license
information please view the LICENSE file that was distributed with this source code.
