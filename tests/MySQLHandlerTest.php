<?php
/** @noinspection ClassMockingCorrectnessInspection */

namespace Test;

use Amp\Failure;
use Amp\Mysql\Connection;
use Amp\Mysql\Statement;
use Amp\PHPUnit\AsyncTestCase;
use Amp\Promise;
use Amp\Sql\QueryError;
use Amp\Success;
use DG\BypassFinals;
use InvalidArgumentException;
use Islambey\Amp\Log\MySQLHandler;
use Monolog\Logger;

class MySQLHandlerTest extends AsyncTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        BypassFinals::enable();
    }

    /** @test */
    public function it_logs_message(): void
    {
        $message = null;
        $channel = null;
        $level = null;
        $context = null;

        $statement = $this->getMockBuilder(Statement::class)
            ->getMock();
        $statement->expects(self::once())
            ->method("execute")
            ->withAnyParameters()
            ->willReturnCallback(function ($args) use (&$message, &$channel, &$level, &$context) {
                [$channel, $message, $context, $level,] = $args;

                return new Success();
            });

        $connection = $this->getMockConnection(new Success($statement));
        $handler = new MySQLHandler($connection);

        $testChannel = "default";
        $logger = new Logger($testChannel);
        $logger->pushHandler($handler);

        $testMessage = "foo bar";
        $logger->info($testMessage);

        self::assertSame($testMessage, $message);
        self::assertSame($testChannel, $channel);
        self::assertSame(Logger::INFO, $level);
        self::assertNull($context);
    }

    /**
     * @test
     * @dataProvider invalidTableNameProvider
     * @param string $tableName
     */
    public function it_throws_exception_when_table_name_is_invalid(string $tableName): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectDeprecationMessage("Invalid table name ($tableName)");

        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();

        new MySQLHandler($connection, $tableName);
    }

    /**
     * @test
     */
    public function it_throws_query_error_on_prepared_statement(): void
    {
        $exceptionMessage = "an error occurred";
        $this->expectException(QueryError::class);
        $this->expectExceptionMessage($exceptionMessage);

        $connection = $this->getMockConnection(new Failure(new QueryError($exceptionMessage)));

        $handler = new MySQLHandler($connection);

        $testChannel = "default";
        $logger = new Logger($testChannel);
        $logger->pushHandler($handler);

        $logger->info("foo bar");
    }

    /**
     * @test
     */
    public function it_throws_query_error_on_execute(): void
    {
        $exceptionMessage = "an error occurred";
        $this->expectException(QueryError::class);
        $this->expectExceptionMessage($exceptionMessage);

        $statement = $this->getMockBuilder(Statement::class)
            ->getMock();

        $statement->expects(self::once())
            ->method("execute")
            ->withAnyParameters()
            ->willReturn(new Failure(new QueryError($exceptionMessage)));

        $connection = $this->getMockConnection(new Success($statement));

        $handler = new MySQLHandler($connection);

        $testChannel = "default";
        $logger = new Logger($testChannel);
        $logger->pushHandler($handler);

        $logger->info("foo bar");

    }

    public function invalidTableNameProvider(): array
    {
        return [
            ["'foo'"],
            ["foo bar"],
            ["foo-bar"],
            ["foo?bar"],
            ["foo!bar"],
            ["@foo"],
            ["fooü"],
        ];
    }

    private function getMockConnection(Promise $returnValue): Connection
    {
        $double = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $double->expects(self::once())
            ->method("prepare")
            ->withAnyParameters()
            ->willReturn($returnValue);

        return $double;
    }
}
