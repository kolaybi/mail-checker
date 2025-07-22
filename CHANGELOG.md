# Changelog

All notable changes to `kolaybi/mail-checker` will be documented in this file.

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
