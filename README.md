# Triples

[![packagist](https://img.shields.io/packagist/v/deemru/triples.svg)](https://packagist.org/packages/deemru/triples) [![php-v](https://img.shields.io/packagist/php-v/deemru/triples.svg)](https://packagist.org/packages/deemru/triples)   [![GitHub](https://img.shields.io/github/actions/workflow/status/deemru/Triples/php.yml?label=github%20actions)](https://github.com/deemru/Triples/actions/workflows/php.yml) [![codacy](https://img.shields.io/codacy/grade/94562bc5ffab447a9a8f0045502c24a6.svg?label=codacy)](https://app.codacy.com/gh/deemru/Triples/files) [![license](https://img.shields.io/packagist/l/deemru/triples.svg)](https://packagist.org/packages/deemru/triples)

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
