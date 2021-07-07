<?php declare(strict_types=1);

namespace Readdle\Database;

final class FQDBException extends \RuntimeException
{
    private array $context;
    
    public function __construct(string $message, array $context = [], ?\Throwable $previous = null)
    {
        if ("" === $message && null !== $previous) {
            $message = $previous->getMessage();
        }
        parent::__construct($message, 0, $previous);
        $this->context = $context;
    }
    
    public function context(): array
    {
        return $this->context;
    }
    
    public static function deprecatedApi(): self
    {
        return new self("FQDB: Deprecated Functionality");
    }
    
    public static function pdo(\PDOException $exception, array $context = []): self
    {
        return new self("PDO: {$exception->getMessage()}", $context, $exception);
    }
    
    public static function unableToQuote(string $str): self
    {
        return new self("FQDB: Unable to quote the string", ["str" => $str]);
    }
    
    public static function assertion(string $message): self
    {
        return new self("FQDB: {$message}");
    }
    
    public static function badPlaceholders(string $query, array $params, array $placeholders): self
    {
        return new self("FQDB: Placeholders set improperly", ["query" => $query, "params" => $params, "placeholders" => $placeholders]);
    }
    
    public static function queryDontStart(string $query, string $start): self
    {
        return new self("FQDB: Query should start with {$start}", ["query" => $query]);
    }
}
