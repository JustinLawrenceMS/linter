
# plinter

plinter is a small CLI wrapper around `php -l` that recursively scans PHP files in one or more directories and reports syntax errors. The scanning logic is implemented in a callable `Plinter\\Runner` class so you can use the scanner programmatically in tests or other PHP code.

**Highlights**
- Recursive scanning with correct vendor exclusions
- Programmatic API via `Plinter\\Runner` (see `src/Runner.php`)
- CLI wrapper `linter.php` for interactive and simple use
- Pest test suite included for reliable, isolated tests

## Installation

Clone the repository and make the bundled `plinter` executable available.

```bash
# Clone the repository
git clone https://github.com/JustinLawrenceMS/php-linter.git ~/php-linter

# Make the bundled CLI executable and put it on your PATH, or create an alias
chmod +x ~/php-linter/plinter
# Option 1: add to PATH (recommended)
mkdir -p ~/.local/bin && ln -sf ~/php-linter/plinter ~/.local/bin/plinter

# Option 2: add an alias to your shell config
echo "alias plinter='~/php-linter/plinter'" >> ~/.zshrc
source ~/.zshrc
```

Usage examples:

```bash
# Scan current directory
plinter

# Scan a specific directory
plinter /path/to/project

# Scan multiple explicit paths (space-separated or comma-separated)
plinter /path/a /path/b
plinter "/path/a,/path/b"

# Skip vendor directories
plinter --skip-vendor /path/to/project

# Verbose output
plinter --verbose --skip-vendor .
```

Note: The canonical CLI entrypoint is the `plinter` executable in the project root. Legacy `linter.php` wrappers were removed to avoid duplicate/conflicting files. The core scanning code lives in `src/Runner.php` and is PSR-4 autoloadable under the `Plinter\\` namespace.

## Programmatic usage

If you prefer to integrate plinter into PHP code or run it from tests, use the `Plinter\\Runner` class directly (no subprocess required):

```php
require 'vendor/autoload.php';

$runner = new Plinter\\Runner();
$result = $runner->scanDirectory('/path/to/project', /* $skipVendor = */ true);

if (!$result['success']) {
	foreach ($result['errors'] as $err) {
		echo $err['file'] . ": " . $err['message'] . "\\n";
	}
}
```

The `scanDirectory` and `scanMultiple` methods return an array with these keys:

- `success` (bool) — whether all scanned files passed
- `errors` (array) — list of [ 'file' => relative-path, 'message' => text ]
- `permissionDenied` (array) — paths that could not be read (with exception messages)
- `filesChecked` (int) — number of PHP files checked
- `filesSkipped` (int) — number of files skipped (e.g. in vendor)
- `vendorDirsSkipped` (array) — list of unique vendor dirs that were skipped

## Tests

This repo includes a Pest test suite that exercises the scanner using isolated temporary directories (no live repo scanning). To run the tests:

1. Install dev dependencies:

```bash
composer install
```

2. Run the test suite:

```bash
composer test
# or
vendor/bin/pest
```

## Output example

The CLI prints progress per-file and a summary. Example:

```
Starting PHP lint scan...
------------------------
Checking src/Controller/UserController.php... OK
Checking src/Model/User.php... OK
Checking tests/UserTest.php... ERROR

Files checked: 3
Files skipped (in vendor directories): 42

------------------------
Errors were found:

File: tests/UserTest.php
Error: syntax error, unexpected ';', expecting '{'
------------------------

Permission Denied (skipped):
- /path/to/protected/folder (failed to open dir: Permission denied)
```

## Requirements

- PHP 7.4+ recommended (uses SPL iterators and namespaces)

## Credits

Developed with assistance from AI pair-programming tools and authored in Visual Studio Code.

