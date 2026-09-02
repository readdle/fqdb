# v4.3.0

- **Opt-in lazy connection**: pass `true` as the new `$lazyConnection` argument of the
  `FQDBExecutor` constructor (or use `FQDBProvider::lazyDbWithDSN()`) and the connection is
  established on the first actual use instead of in the constructor, so an instance created for
  a code path that never queries costs nothing. **Default behaviour is unchanged**: without the
  flag the constructor connects, exactly as before.
  - On such an instance `getPdo()` establishes the connection (and can therefore throw
    `FQDBException`), and an unreachable server surfaces on the first query rather than on
    construction. Added `FQDBExecutor::isConnected()`.
- The SQL dialect used by `quote()` with `QUOTE_IDENTIFIER` is derived from the DSN, so it is
  correct on a deferred connection too (it is still refined from `PDO::ATTR_DRIVER_NAME` once
  connected).
- `FQDBExecutor::registerConnector()` and `Resolver::registerConnector()` accept an optional
  `int $priority`; connectors with a higher priority are resolved first, ties keep registration
  order. The built-in `DSNConnector` has priority 0, so a custom connector can now take over
  from it without relying on an empty DSN.

# v4.2.0

- PHP8.1: fixed compatibility issues with \JsonSerializable interface 

# v4.1.0

- weakened dependency "symfony/event-dispatcher" in favor of "symfony/event-dispatcher-contracts".

**POTENTIAL BC**: If you're using "symfony/event-dispatcher" as a dispatcher, please install it manually `composer require symfony/event-dispatcher` 

# v4.0.4

- no behavior change, except better compatibility with PHP 8.1

# v4.0.3

- [bugfix] Event dispatcher should be nullable 

# v4.0.2

- [bugfix] DSN Connector should not be resolved if empty dsn string provided

# v4.0.1

- Allowed `symfony/event-dispatcher:^4.3|5.*`

# v4.0.0

- Minimum PHP version 7.4
- `Readdle\Database\Connector\DSNConnector` is final now. Implement own connector based on `ConnectorInterface` if needed
- `Readdle\Database\Connector\Resolver` is final now.
- `symfony/event-dispatcher` version `>=5.0 <6.0`
- `trigger_error` behavior replaced with direct exception throwing in error cases. (however, `trigger_error` still used if query produces warnings and warning reporting is enabled)
- Added types (e.g. `Readdle\Database\FQDBExecutor::setWarningHandler` and `Readdle\Database\FQDBExecutor::setErrorHandler` accepts `callable|null`, etc.)