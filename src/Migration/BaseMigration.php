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

use Psr\Log\LoggerInterface as Logger;
use SlaxWeb\Database\Interfaces\Library as DB;
use SlaxWeb\Database\Query\Builder as QueryBuilder;

abstract class BaseMigration
{
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
    public function __call(string $name, array $params)
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
     * Run migration
     *
     * Executed when migrations are ran against the database.
     *
     * @return void
     */
    abstract protected function up();

    /**
     * Reverse migration
     *
     * Executed when migrations are being rolled back or removed. Logic in this
     * method should reverse all actions taken in the "up" method.
     *
     * @return void
     */
    abstract protected function down();
}
