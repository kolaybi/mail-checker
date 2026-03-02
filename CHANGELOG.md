# Changelog

All notable changes to `kolaybi/mail-checker` will be documented in this file.

## [v2.0.0](https://github.com/kolaybi/mail-checker/commits/v2.0.0) (2026-03-03)

### Breaking
- Config key changed from `mail-checker` to `kolaybi.mail-checker`
- Config file publishes to `config/kolaybi/mail-checker.php`
- Renamed `MailableExternalMailProviderException` to `EmailableExternalMailProviderException`
- Removed `--domain-type` option from `mail-checker:cache-clear` command
- `LocalDomainService::clearCache()` no longer accepts a `$listType` parameter
- `CacheService::flush()` no longer accepts a `$cacheKey` parameter

### Fixed
- Providers now respect priority order from config (was silently ignored)
- Bouncer and Emailable providers using wrong case for `undeliverable` status (all emails were passing)
- Bouncer provider reading wrong response key (`state` instead of `status`)
- Mailgun provider now uses HTTP Basic Auth instead of query param for API key
- AbstractApi `auto_correct` param sent as proper falsy value
- Subdomain emails now correctly match domain lists (e.g. `mail.spam.com` matches `spam.com`)
- `clearAllCaches()` was always returning true due to null services
- `CacheService::flush()` was broken (using `getMultiple([])` which returns empty)
- `fail_if_no_providers` check was unreachable when providers were configured but all failed
- Cache invalidation now properly clears per-email results
- `getValidationResult()` exception handling aligned with `isValid()`
- Nullable type for `$listType` property in `UpdateDomains` command
- Default values added to `CacheService` constructor to prevent TypeError on missing config

### Added
- Dedicated `mail-checker` cache store to isolate flush from application cache
- Test suite with Pest and Orchestra Testbench (137 tests, 100% coverage)
- Success output to `mail-checker:cache-clear` command
- Null API key filtering — providers without configured keys are skipped

### Changed
- `--type` option is now required in `mail-checker:update-domains` command (was defaulting to `whitelist`)
- `fail_if_no_providers` defaults to `false` in config and service logic
- Disposable domains are now sorted and pretty-printed on save
- Removed dead `provides()` method from ServiceProvider
- Removed `Isolatable` from `UpdateDisposableDomains`
- Removed redundant per-email cache layer in `LocalDomainService`
- Replaced dead rawgit.com URL with raw.githubusercontent.com
- Updated `get_class($this)` to `$this::class` in ProviderTrait
- Removed dead code in `UpdateDomains` (redundant regex check, impossible `json_encode` guard)

## [v1.5.2](https://github.com/kolaybi/mail-checker/commits/v1.5.2) (2025-08-05)

### Changed
- Refactored email validation to use instance methods and improve service initialization

## [v1.5.1](https://github.com/kolaybi/mail-checker/commits/v1.5.1) (2025-08-05)

### Fixed
- Fixed cache invalidation after domain updates in UpdateDomains and UpdateDisposableDomains commands
- Improved type handling in ClearMailCache command

### Added
- Added explicit PHP 8.4 requirement in composer.json

## [v1.5.0](https://github.com/kolaybi/mail-checker/commits/v1.5.0) (2025-07-28)

### Added
- Added `fail_if_no_providers` configuration option for external mail validation

## [v1.4.0](https://github.com/kolaybi/mail-checker/commits/v1.4.0) (2025-07-28)

### Added
- Added domain validation methods for whitelist, blacklist, disposable, and format checks
- Added inverse email validation methods for whitelist, blacklist, disposable, and format checks

### Improved
- Documented advanced email validation methods in README.md
- Refactored email validation methods for better maintainability

## [v1.3.0](https://github.com/kolaybi/mail-checker/commits/v1.3.0) (2025-07-28)

### Added
- Added multiple new external mail validation providers:
  - Emailable
  - NeverBounce
  - Hunter
  - Bouncer
  - Kickbox
- Implemented centralized global timeout setting for all external mail checker services

### Improved
- Enhanced error handling and logging for external mail deliverability checks
- Added null check before using the cache service to prevent potential issues

## [v1.2.0](https://github.com/kolaybi/mail-checker/commits/v1.2.0) (2025-07-23)

### Added
- Enhanced MailChecker with detailed validation, batch processing, and cache management methods
- Added singleton pattern for services to improve resource usage
- Added timeout support for email providers
- Added improved error handling with `InaccessibleMailException` for missing mail deliverability

### Changed
- Refactored ExternalMailService for better maintainability
- Enhanced provider retry mechanism for more reliable service
- Improved error handling across all validation services

## [v1.1.0](https://github.com/kolaybi/mail-checker/commits/v1.1.0) (2025-07-22)

### Added
- Implemented caching mechanism for both local and external validation services
- Added new `CacheService` for optimized performance
- Introduced `mail-checker:cache-clear` command to manage cache
- Added service type enums for better code organization
- Improved domain management with new `UpdateDomains` command for both blacklist and whitelist operations

### Changed
- Refactored `ServiceProvider` to implement `DeferrableProvider` for better performance
- Registered `MailChecker` as a singleton for efficient dependency injection
- Renamed commands to follow consistent naming convention (e.g., `mail-checker:update-disposable-domains`)
- Improved domain validation logic in command handling
- Streamlined external mail validation service code

### Configuration
- Added cache configuration options with TTL settings
- Added cache store selection for both local and external services
- Cache can be selectively enabled/disabled per service type

## [v1.0.0](https://github.com/kolaybi/mail-checker/commits/v1.0.0) (Unreleased)

### Added
- Initial release with core email validation functionality
- Local domain validation (whitelist, blacklist, disposable domains)
- External service providers integration (AbstractAPI, MailboxLayer, Mailgun)
- Laravel service provider for seamless integration
- Configuration file with environment variable support
- Command line interface for domain list management
- Comprehensive exception handling for different validation scenarios

### Configuration
- Local domain lists with configurable storage paths
- External provider priority configuration
- Support for multiple external validation services

## v0.0.0 (Unreleased)
- initial

### Notes

This is the initial release of the KolayBi Mail Checker package, providing robust email validation for better email delivery in Laravel applications.
