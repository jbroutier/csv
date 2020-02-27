# CSV

A simple and memory efficient CSV import/export library for PHP >= 7.1

## Reading data

### CSV file with headers

```php
<?php

use JBroutier\Csv\Reader;

$reader = new Reader('file.csv');
$reader->setHeader(true);
$reader->read(function($row, $rownum) {
    // Field data can be accessed using column names
    // Eg. ['first_name' => 'John', 'last_name' => 'Doe', 'age' => '29']
});
```

### CSV file without headers

```php
<?php

use JBroutier\Csv\Reader;

$reader = new Reader('file.csv');
$reader->setHeader(false);
$reader->read(function($row, $rownum) {
    // Field data can be accessed using numeric indexes
    // Eg. [0 => 'John', 1 => 'Doe', 2 => '29']
});
```

## Writing data

### CSV file with headers

```php
<?php

use JBroutier\Csv\Writer;

// Some code returning an iterable.
$users = ...

$writer = new Writer('file.csv');
$writer->setHeader(['first_name', 'last_name', 'age']);
$writer->write($users, function($user, $rownum) {
    return [
        $user->getFirstName(),
        $user->getLastName(),
        $user->getAge(),
    ];
});
```

### CSV file without headers

```php
<?php

use JBroutier\Csv\Writer;

// Some code returning an iterable.
$users = ...

$writer = new Writer('file.csv');
$writer->setHeader(false);
$writer->write($users, function($user, $rownum) {
    return [
        $user->getFirstName(),
        $user->getLastName(),
        $user->getAge(),
    ];
});
```
