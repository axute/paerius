Paerius
=======

Bundles your vendor directory into a single PHP Archive (PHAR).

## Requirements

`php.ini` edit phar settings:
    
    ; http://php.net/phar.readonly
    phar.readonly = Off
    ; http://php.net/phar.require-hash
    phar.require_hash = Off


 
## Installation

Add Pmt to your project using Composer:

    composer require axute/paerius

## Files that will be deleted

See [Paerius.php](src/Paerius.php) at class fields for files that will be deleted (in vendor path).



## Usage

Run the binary to create/update your PHAR file:

    php vendor/bin/paerius

### Compression

Files in phar archive will be tried to compress.

PHP Extension is required (optionaly).


force special compression (bz2/gz/none)

default is bzip2 (if installed, fallback gzip if installed, fallback none)

bzip2 < gzip < none

    php vendor/bin/paerius bz

    php vendor/bin/paerius gz

    php vendor/bin/paerius none

    
A new file named `vendor.phar` will be added to your working directory (project root). 
Update your bootstrap to include `./vendor.phar` instead of `vendor/autoload.php` and you're good to go.

    $autoload = require_once __DIR__ . DIRECTORY_SEPARATOR . 'vendor.phar';
    $autoload->addPsr4('[YOUR NAMESPACE]',__DIR__.DIRECTORY_SEPARATOR.'src');
    
### Automate

To generate `vendor.phar` automaticly, add the following lines to **your** composer.json

    "scripts": {
        "post-install-cmd": "vendor/bin/paerius",
        "post-update-cmd": "vendor/bin/paerius"
    }
