<<<<<<< HEAD
Diem is a content management framework (CMF) for PHP projects.

As a framework,
---------------
Diem is flexible. There is no preinstalled stuff ( blog, comments... ) : the project starts empty.
The developer creates its own modules by declaring them in configuration files.
Then Diem generates a code which is 100% specific to the site's needs.

As a CMS,
---------
Diem packages many site-independant features in a clean user interface.
Most of the common problems a web developer has to face up are made easy.

Diem is, and will always be, entirely free and published under the MIT license.

Documentation
-------------

Please see to the online documentation : 

[Entry-level documentation](http://diem-project.org/)

[High-level documentation](http://bugs.diem-project.org/projects/diem-project/wiki) **work in progress**
=======
# PHP User Agent

Browser detection in PHP5.
Uses a simple and fast algorithm to recognize major browsers.

## Overview

    $userAgent = new phpUserAgent();

    $userAgent->getBrowserName()      // firefox
    $userAgent->getBrowserVersion()   // 3.6
    $userAgent->getOperatingSystem()  // linux
    $userAgent->getEngine()           // gecko

### Why you should use it

PHP provides a native function to detect user browser: [get_browser()](http://us2.php.net/manual/en/function.get-browser.php).
get_browser() requires the "browscap.ini" file which is 300KB+.
Loading and processing this file impact script performance.
And sometimes, the production server just doesn't provide browscap.ini.

Although get_browser() surely provides excellent detection results, in most
cases a much simpler method can be just as effective.
php-user-agent has the advantage of being compact and easy to extend.
It is performant as well, since it doesn't do any iteration or recursion.

## Usage

    // include the class
    require_once '/path/to/php-user-agent/phpUserAgent.php';

    // Create a user agent
    $userAgent = new phpUserAgent();

    // Interrogate the user agent
    $userAgent->getBrowserName()      // firefox
    $userAgent->getBrowserVersion()   // 3.6
    $userAgent->getOperatingSystem()  // linux
    $userAgent->getEngine()           // gecko

## Advanced

### Custom user agent string

When you create a phpUserAgent object, the current user agent string is used.
You can specify another user agent string:

    // use another user agent string
    $userAgent = new phpUserAgent('msnbot/2.0b (+http://search.msn.com/msnbot.htm)');
    $userAgent->getBrowserName() // msnbot

    // use current user agent string
    $userAgent = new phpUserAgent($_SERVER['HTTP_USER_AGENT');
    // this is equivalent to:
    $userAgent = new phpUserAgent();

### Custom parser class

By default, phpUserAgentStringParser is used to analyse the user agent string.
You can replace the parser instance and customize it to match your needs:

    // create a custom user agent string parser
    class myUserAgentStringParser extends phpUserAgentStringParser
    {
      // override methods
    }

    // inject the custom parser when creating a user agent:
    $userAgent = new phpUserAgent(null, new myUserAgentStringParser());

## Run tests

You can run the unit tests on your server:

    php prove.php

## Contribute

If you found a browser of operating system this library fails to recognize,
feel free to submit an issue. Please provide the user agent string.
And well, if you also want to provide the patch, it's even better.
>>>>>>> 1381e1674c57e4f574efc2c2bfa29db76eb56134
