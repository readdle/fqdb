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