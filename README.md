# dot-env-editor - A dead simple .env editor for PHP

A robust PHP package designed to simplify the management and manipulation of .env files within your projects. Effortlessly read, write, update, and delete environment variables with ease.

## Features

-   Effortlessly load and parse .env files
-   Get, set, and remove environment variables
-   Support for nesting env vars (e.g. DB_CONNECTION.host)
-   Ability to update existing vars or add new ones
-   Optionally keep backups of .env files before writing
-   Helper methods like `only()` to get subset of variables
-   Simple chaining methods for fluent interface
-   Handles formatting values like booleans and strings
-   Preserves spacing and comments when writing back to file
-   Compatible with various frameworks and environments, ensuring versatility in usage.
-   Built for PHP 8+ with strict typing

## Requirements

-   PHP 8.1 or higher

## Installation

Install via Composer:

```sh
composer require digimax/dot-env-editor
```

## Usage

```php
use Digimax\DotEnvEditor\DotEnvEditor;

$envPath = __DIR__ . '/.env';

$editor = new DotEnvEditor(
    $envPath,   // the path to the.env file
    true,       // whether to keep a backup of the .env file before writing
);

// or using the static method
$editor = DotEnvEditor::load($envPath, true);

// set backup directory
$editor->setBackupDir(__DIR__ . '/backups');

// Get all variables
var_dump($editor->all());

// Get a specific variable
echo $editor->get('AUTHOR_NAME');

// Set a variable
$editor->set('AUTHOR_NAME', 'Raziul Islam');

// set multiple variables
$editor->set([
    'AUTHOR_URL' => 'https://raziul.dev',
    'AUTHOR_COUNTRY' => 'Bangladesh',
]);

// Remove a variable
$editor->remove('AUTHOR_URL');

// write back to the file
$editor->write();
```

### You can use chaining methods for fluent interface 😘

```php
DotEnvEditor::load($envPath, true)
    ->setBackupDir(__DIR__ . '/backups')
    ->set([
        'AUTHOR_URL' => 'https://raziul.dev',
        'AUTHOR_COUNTRY' => 'Bangladesh',
    ])
    ->remove('AUTHOR_URL')
    ->write();
```

## Finding this package useful?

If this package has helped to simplify your workflow, consider giving it a ⭐️ on GitHub. Your support encourages further development and improvements! 💖

## Support

For support, please open an [issue on GitHb](https://github.com/Digimax-IT/dot-env-editor/issues) or submit a [pull request](https://github.com/Digimax-IT/dot-env-editor/pulls).

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
