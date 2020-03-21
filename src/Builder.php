<?php declare(strict_types=1);
/*
 * This file is part of FlexPHP.
 *
 * (c) Freddie Gar <freddie.gar@outlook.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace FlexPHP\Database;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Schema as DBALSchema;
use Doctrine\DBAL\Schema\SchemaConfig as DBALSchemaConfig;

final class Builder
{
    public const PLATFORM_MYSQL = 'MySQL';

    public const PLATFORM_SQLSRV = 'SQLSrv';

    /**
     * @var string
     */
    private $platform;

    /**
     * @var AbstractPlatform
     */
    private $DBALPlatform;

    /**
     * @var DBALSchema
     */
    private $DBALSchema;

    /**
     * @var array<int, string>
     */
    private $databases = [];

    /**
     * @var array<int, string>
     */
    private $users = [];

    /**
     * @var array<int, string>
     */
    private $tables = [];

    /**
     * @var array<string, string>
     */
    private $platformSupport = [
        self::PLATFORM_MYSQL => 'MySQL57',
        self::PLATFORM_SQLSRV => 'SQLServer2012',
    ];

    public function __construct(string $platform)
    {
        if (empty($this->platformSupport[$platform])) {
            throw new \InvalidArgumentException(\sprintf(
                'Platform %s not supported, try: %s',
                $platform,
                \implode(', ', \array_keys($this->platformSupport))
            ));
        }

        $fqdnPlatform = \sprintf('\Doctrine\DBAL\Platforms\%sPlatform', $this->platformSupport[$platform]);

        $this->platform = $platform;
        $this->DBALPlatform = new $fqdnPlatform();
    }

    public function createDatabase(string $name): void
    {
        $this->databases[] = $this->DBALPlatform->getCreateDatabaseSQL($name)
            . ' ' . $this->getCollateDatabase()
            . ';';
    }

    // public function createUser(UserInterface $user): void
    // {
    //     $this->users[] = $user->toSqlCreate();
    // }

    public function createTable(TableInterface $table): void
    {
        $DBALSchemaConfig = new DBALSchemaConfig();
        $DBALSchemaConfig->setDefaultTableOptions($table->getOptions());

        $this->DBALSchema = new DBALSchema([], [], $DBALSchemaConfig);

        $DBALTable = $this->DBALSchema->createTable($table->getName());

        foreach ($table->getColumns() as $column) {
            $DBALTable->addColumn($column->getName(), $column->getType(), $column->getOptions());
        }

        $this->tables[] = $this->DBALSchema->toSql($this->DBALPlatform)[0] . ';';
    }

    public function toSql(): string
    {
        $sql = '';
        $glue = "\n";

        if (\count($this->databases)) {
            $sql .= \implode($glue, $this->databases);
        }
        // if (\count($this->users)) {
        //     $sql .= \implode($glue, $this->users);
        // }

        if (\count($this->tables)) {
            $sql .= \implode($glue, $this->tables);
        }

        return $sql;
    }

    private function getCollateDatabase(): string
    {
        $collate = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci';

        if ($this->isSQLSrvPlatform()) {
            $collate = 'COLLATE latin1_general_100_ci_ai_sc';
        }

        return $collate;
    }

    // private function isMySQLPlatform(): bool
    // {
    //     return $this->platform === self::PLATFORM_MYSQL;
    // }

    private function isSQLSrvPlatform(): bool
    {
        return $this->platform === self::PLATFORM_SQLSRV;
    }
}
