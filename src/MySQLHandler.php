<?php

namespace Islambey\Amp\Log;

use Amp\Mysql\Connection;
use Amp\Mysql\Statement;
use Amp\Sql\QueryError;
use InvalidArgumentException;
use Monolog\Handler\AbstractProcessingHandler;
use Psr\Log\LogLevel;

class MySQLHandler extends AbstractProcessingHandler
{
    private Connection $connection;
    private string $table;

    public function __construct(
        Connection $connection,
        string $table = "logs",
        string $level = LogLevel::DEBUG,
        bool $bubble = true
    ) {
        parent::__construct($level, $bubble);

        $this->connection = $connection;

        if (!preg_match("/^[a-z0-9_]+$/i", $table)) {
            throw new InvalidArgumentException("Invalid table name ($table)");
        }

        $this->table = $table;
    }

    protected function write(array $record): void
    {
        $sql = "INSERT INTO {$this->table} (channel, message, context, level, datetime) VALUES (?, ?, ?, ?, ?)";

        $this->connection
            ->prepare($sql)
            ->onResolve(function (?QueryError $error, ?Statement $statement) use ($record) {
                if (null !== $error) {
                    throw $error;
                }
                /** @var \Monolog\DateTimeImmutable $dt */
                $dt = $record["datetime"];
                $context = $record["context"] === [] ? null : json_encode($record["context"], JSON_THROW_ON_ERROR);

                $statement->execute([
                    $record["channel"],
                    $record["message"],
                    $context,
                    $record["level"],
                    $dt->format("Y-m-d H:i:s"),
                ])->onResolve(function (?QueryError $error) {
                    if (null !== $error) {
                        throw $error;
                    }
                });
            });
    }
}
