<?php declare(strict_types=1);

namespace Readdle\Database;

use Readdle\Database\Connector\ConnectorInterface;

interface FQDBInterface
{
    public function execute(string $sql, array $params = [], string $prefix = ""): int;
    public function quote(string $string, int $mode): string;
    public function beginTransaction(): void;
    public function commitTransaction(): void;
    public function rollbackTransaction(): void;
    public function getPdo(): \PDO;
    public function setWarningHandler(?callable $func = null): void;
    public function getWarningHandler(): ?callable;
    public function setWarningReporting(bool $bool = true): void;
    public function getWarningReporting(): bool;
    public function setErrorHandler(?callable $func = null): void;
    public function getErrorHandler(): ?callable;
    public static function registerConnector(ConnectorInterface $connector): void;
}
