<?php
/**
 * Base migration abstract class
 *
 * All migration classes must extend from the base migration class, as it provides
 * essential construction logic for the migrations to be processable. The base class
 * is defined abstract and requires the child class to implement the "up()" and
 * "down()" methods.
 *
 * @package   SlaxWeb\DatabasePDO
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.6
 */
namespace SlaxWeb\DatabasePDO\Migration;

use PDOException;
use Psr\Log\LoggerInterface as Logger;
use SlaxWeb\Database\Interfaces\Library as DB;
use SlaxWeb\Database\Query\Builder as QueryBuilder;
use SlaxWeb\DatabasePDO\Exception\MigrationException;

abstract class BaseMigration
{
    /**
     * Migration execution mode constants
     */
    const BRING_UP = "up";
    const TEAR_DOWN = "down";

    /**
     * Query builder
     *
     * @var \SlaxWeb\Database\Query\Builder
     */
    protected $builder = null;

    /**
     * Database Library Instance
     *
     * @var \SlaxWeb\Database\Interface\Library
     */
    protected $db = null;

    /**
     * Logger instance
     *
     * @var \Psr\Log\LoggerInterface
     */

    /**
     * Class constructor
     *
     * Copy received dependencies to internal properties for later use.
     *
     * @param \SlaxWeb\Database\Query\Builder $queryBuilder Query Builder
     * @param \SlaxWeb\Database\Interface\Library $db Database Library Instance
     * @param \Psr\Log\LoggerInterface $logger Logger instance
     */
    public function __construct(QueryBuilder $queryBuilder, DB $db, Logger $logger)
    {
        $this->builder = $queryBuilder;
        $this->db = $db;
        $this->logger = $logger;

        $this->logger->info("Initialized database migration class", ["class" => get_class($this)]);
    }

    /**
     * Magic call
     *
     * Forward calls to the query builder if method exists, and automatically execute
     * query when string is returned from the Query Builder. Returns whatever the
     * Query Builder or the Database Library return. If a call to an unkown or non-accessible
     * method in the Query Builder is made a generic "Exception" is thrown.
     *
     * @param string $name Name of the method
     * @param array $params Parameters for the method
     * @return mixed
     *
     * @throws \Exception
     */
    public function __call($name, array $params)
    {
        if (method_exists($this->builder->{$name}) === false) {
            $this->logger->error(
                "Method is not known for this migration or is not accessible",
                ["method" => $name]
            );
            throw new \Exception("Method '{$name}' is not known or not accessible");
        }

        $return = $this->builder->{$name}(...$params);
        if (is_string($return)) {
            $this->logger->debug(
                "Builder returned a string, treating it as a string and executing "
                . "it against the database",
                ["query" => $return]
            );
            // treat returned string as SQL statement and execute it
            $return = $this->db->execute($return, $this->builder->getParams());
            $this->builder->reset();

            // if called method is "select" then execute fetch and return results.
            if ($return === true && $name === "select") {
                $return = $this->db->fetch();
            }
        }

        return $return;
    }

    /**
     * Execute migration
     *
     * Start the migration by beginning a transaction if the driver supports it,
     * and depending on the result of the 'up/down' methods either commit it, or
     * roll it back.
     *
     * The input of the method determines if the 'up' or 'down' methods are to be
     * called. The method takes the class constants 'BRING_UP' or 'TEAR_DOWN' as
     * input, with 'BRING_UP' as default. If the input is not one of the two, an
     * exception is thrown.
     *
     * @param string $mode Execution mode
     * @return bool
     *
     * @throws \SlaxWeb\DatabasePDO\Exception\MigrationException
     */
    public function execute(string $mode = self::BRING_UP): bool
    {
        if (in_array($mode, [self::BRING_UP, self::TEAR_DOWN]) === false) {
            throw new MigrationException(
                "Unable to execute migration, an unknown mode was requested"
            );
        }

        try {
            $trans = $this->db->beginTransaction();
        } catch (PDOException $e) {
            $trans = false;
        }

        $result = $this->{$mode}();

        if ($trans === true) {
            if ($result === true) {
                $this->db->commit();
            } else {
                $this->db->rollBack();
            }
        }

        return $result;
    }

    /**
     * Run migration
     *
     * Executed when migrations are ran against the database. A transaction is automatically
     * started for you, and you do not need to start it. To commit the transaction
     * return a bool(true), or bool(false) to rollback the transaction.
     *
     * @return bool
     */
    abstract protected function up(): bool;

    /**
     * Reverse migration
     *
     * Executed when migrations are being rolled back or removed. Logic in this
     * method should reverse all actions taken in the "up" method. A transaction
     * is automatically started for you, and you do not need to start it. To commit
     * the transaction return a bool(true), or bool(false) to rollback the transaction.
     *
     * @return bool
     */
    abstract protected function down(): bool;
}
