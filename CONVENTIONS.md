# Conventions

- PHP 8.2+ (develop on 8.4/8.5). Strict types (`declare(strict_types=1)`).
- PSR-4 `MainMoney\\Aggregator\\` → `src/`.
- PHPUnit, PHPStan, PHP CS Fixer. No empty catch blocks.
- Currency: never mix amounts across currencies.
- Do not call the aggregator from this SDK with invented paths.
