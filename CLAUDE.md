# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Laravel package for multi-channel messaging (SMS via AfrikSMS, WhatsApp via Twilio). Built on Spatie's laravel-package-tools.

## Development Commands

```bash
# Run all tests and checks
composer test

# Individual test commands
composer test:unit          # Run Pest tests
composer test:types         # Run PHPStan static analysis
composer test:lint          # Check code style with Pint
composer test:typos         # Check for typos with Peck
composer test:refactor      # Dry-run Rector refactoring
composer test:type-coverage # Check type coverage (100% required)

# Auto-fix code
composer fix                # Run PHPStan, Rector, then Pint
composer lint               # Fix code style with Pint
composer refactor           # Apply Rector refactoring
composer analyse            # Run PHPStan analysis
```

Run a single test:
```bash
./vendor/bin/pest --filter "test name"
./vendor/bin/pest tests/ExampleTest.php
```

## Architecture

- Uses Spatie's `PackageServiceProvider` pattern - see `src/MessagingServiceProvider.php`
- Facade available at `Ratoufa\Messaging\Facades\Messaging`
- Config published to `config/messaging.php`
- Tests use Orchestra Testbench with Pest

## Code Standards

- PHP 8.4+ required
- All files must use `declare(strict_types=1)`
- All classes should be `final` by default
- PHPStan level 5 with Octane and model property checks enabled
- No debugging functions allowed (`dd`, `dump`, `ray`) - enforced by arch tests
- 100% type coverage required
