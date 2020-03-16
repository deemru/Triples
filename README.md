# Triples

[![packagist](https://img.shields.io/packagist/v/deemru/triples.svg)](https://packagist.org/packages/deemru/triples) [![php-v](https://img.shields.io/packagist/php-v/deemru/triples.svg)](https://packagist.org/packages/deemru/triples)  [![travis](https://img.shields.io/travis/deemru/Triples.svg?label=travis)](https://travis-ci.org/deemru/Triples) [![codacy](https://img.shields.io/codacy/grade/1b5145f44cdd47bb8117c6d08b013ff0.svg?label=codacy)](https://app.codacy.com/project/deemru/Triples/dashboard) [![license](https://img.shields.io/packagist/l/deemru/triples.svg)](https://packagist.org/packages/deemru/triples)

[Triples](https://github.com/deemru/Triples) implements a simple flexible layer for [SQLite](https://en.wikipedia.org/wiki/SQLite) storage.

- Speed
- Merge through cache
- Custom queries

## Usage

```php
$dbpath = __DIR__ . '/triples.sqlite';
$triples = new Triples( $dbpath, 'triples', true, [ 'INTEGER PRIMARY KEY', 'TEXT UNIQUE', 'INTEGER' ], [ 0, 0, 1 ] );

$r0 = 1;
$r1 = 'Hello, World!';
$r2 = crc32( $r1 );
$rec = [ $r0, $r1, $r2 ];
$recs = [ $rec ];
$triples->merge( $recs );

if( $triples->getUno( 2, $r2 )[0] != $r0 ||
    $triples->getUno( 1, $r1 )[2] != $r2 ||
    $triples->getUno( 0, $r0 )[1] !== $r1 )
    exit( 1 );
```

## Requirements

- [PHP](http://php.net) >= 5.4
- [SQLite (PDO)](http://php.net/manual/en/ref.pdo-sqlite.php)

## Installation

Require through Composer:

```json
{
    "require": {
        "deemru/triples": "1.0.*"
    }
}
```
