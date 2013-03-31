#php-gedcom

[![Build Status](https://secure.travis-ci.org/mrkrstphr/php-gedcom.png?branch=master)](https://travis-ci.org/mrkrstphr/php-gedcom)

## Requirements

* php-gedcom 1.0.* requires PHP 5.3 (or later).

## Installation

There are two ways of installing php-gedcom.
### Composer

To install php-gedcom in your project using composer, simply add the following require line to your project's `composer.json` file:

    {
        "require": {
            "mrkrstphr/php-gedcom": "1.0.*"
        }
    }

### Download and __autoload

If you are not using composer, you can download an archive of the source from GitHub and extract it into your project. You'll need to setup an autoloader for the files, unless you go through the painstaking process if requiring all the needed files one-by-one. Something like the following should suffice:

    spl_autoload_register(function ($class) {
        $pathToPhpGedcom = __DIR__ . '/library/'; // TODO FIXME

        if (!substr(ltrim($class, '\\'), 0, 7) == 'PhpGedcom\\') {
            return;
        }

        $class = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
        if (file_exists($pathToPhpGedcom . $class)) {
            require_once($pathToPhpGedcom . $class);
        }
    });


### Usage

To parse a GEDCOM file and load it into a collection of PHP Objects, simply instantiate a new Parser object and pass it the file name to parse. The resulting Gedcom object will contain all the information stored within the supplied GEDCOM file:

    $parser = new \PhpGedcom\Parser();
    $gedcom = $parser->parse('tmp\gedcom.ged');

    foreach ($gedcom->getIndi() as $individual) {
        echo $individual->getId() . ': ' . current($individual->getName())->getSurn() .
            ', ' . current($indi->$individual())->getGivn();
    }
