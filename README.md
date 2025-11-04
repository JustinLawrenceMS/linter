# PHP Recursive Linter

A simple yet powerful PHP script that recursively checks PHP files for syntax errors using `php -l`. It can scan entire project directories while providing detailed feedback and supporting vendor directory exclusions.

## Features

- Recursively scans directories for PHP files
- Skip vendor directories (optional)
- Interactive mode for directory selection
- Detailed error reporting
- Shows summary of scanned and skipped files
- Lists all vendor directories that were skipped
- Provides relative file paths for better readability

## Usage

### Basic Usage

```bash
# Scan current directory
php linter.php

# Scan specific directory
php linter.php /path/to/directory

# Scan with vendor directories skipped
php linter.php --skip-vendor /path/to/directory
```

### Interactive Mode

When run without arguments, the script enters interactive mode:

1. Shows current directory
2. Asks if you want to skip vendor directories
3. Asks for confirmation before scanning

### Output Example

```
Starting PHP lint scan...
------------------------
Checking src/Controller/UserController.php... OK
Checking src/Model/User.php... OK
Checking tests/UserTest.php... ERROR

Files checked: 3
Files skipped (in vendor directories): 42

Skipped vendor directories:
- vendor
- modules/custom/vendor

------------------------
Errors were found:

File: tests/UserTest.php
Error: syntax error, unexpected ';', expecting '{'
------------------------
```

## Error Handling

- Shows syntax errors with file paths and specific error messages
- Uses relative paths for better readability
- Clearly separates each error with dividers
- Returns exit code 0 for success, 1 for errors

## Requirements

- PHP 5.4 or higher
- Read permissions for directories to be scanned

## Credits

This project was developed with the assistance of:
- GitHub Copilot in Visual Studio Code
- Visual Studio Code's PHP development tools

Created in VS Code with AI pair programming.