<?php
/**
 * PDO Database Library
 *
 * PDO Database Library for SlaxWeb Framework provides connection to a RDB with
 * the help of the PHP Data Objects, or PDO.
 *
 * @package   SlaxWeb\DatabasePDO
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.4
 */
namespace SlaxWeb\DatabasePDO;

use PDO;
use Closure;
use PDOStatement;
use SlaxWeb\Database\Error;
use SlaxWeb\DatabasePDO\Query\Builder;
use SlaxWeb\DatabasePDO\Query\Where\Predicate;
use SlaxWeb\Database\Exception\NoErrorException;
use SlaxWeb\Database\Interfaces\Result as ResultInterface;

class Library implements \SlaxWeb\Database\Interfaces\Library
{
    /**
     * SQL Object Delimiter
     *
     * Defaults to "\"" for all major RDBs, except for MYSQL.
     *
     * @var string
     */
    protected $delim = "\"";

    /**
     * PDO instance
     *
     * @var \PDO
     */
    protected $pdo = null;

    /**
     * PDO Lazy Load Closure
     *
     * @var \Closure
     */
    protected $pdoLoader = null;

    /**
     * Last Executed Statement
     *
     * @var \PDOStatement
     */
    protected $stmnt = null;

    /**
     * Database Error Object
     *
     * @var \SlaxWeb\Database\Error
     */
    protected $error = null;

    /**
     * Class constrcutor
     *
     * Initiates the class and assigns the dependencies to local properties for
     * later use.
     *
     * @param \Closure $pdoLoader PDO lazy load Closure
     */
    public function __construct(Closure $pdoLoader)
    {
        $this->pdoLoader = $pdoLoader;
    }

    /**
     * Execute Query
     *
     * Executes the received query and binds the received parameters into the query
     * to decrease the chance of an SQL injection. Returns bool(true) if query was
     * successfuly executed, and bool(false) if it was not. If the query yielded
     * a result set, a Result object will be populated.
     *
     * @param string $query The Query to be executed
     * @param array $data Data to be bound into the Query, default []
     * @return bool
     */
    public function execute(string $query, array $data = []): bool
    {
        if (($this->stmnt = $this->getPdo()->prepare($query)) === false) {
            $this->setError($query);
            return false;
        }
        if ($this->stmnt->execute(array_values($data)) === false) {
            $this->setError($query, $this->stmnt->errorInfo());
            return false;
        }
        return true;
    }

    /**
     * Fetch Results
     *
     * It fetches the results from the last executed statement, creates the Result
     * object and returns it. If an statement has not yet been executed or did not
     * yield a valid result set, an exception is thrown.
     *
     * @return \SlaxWeb\DatabasePDO\Result
     */
    public function fetch(): ResultInterface
    {
        if (!($this->stmnt instanceof PDOStatement)
            || is_array(($result = $this->stmnt->fetchAll(PDO::FETCH_OBJ))) === false) {
            return new Result([]);
        }

        return new Result($result);
    }

    /**
     * Get last error
     *
     * Retrieves the error of the last executed query. If there was no error, an
     * exception must be thrown.
     *
     * @return \SlaxWeb\Database\Error
     *
     * @exceptions \SlaxWeb\Database\Exception\NoErrorException
     */
    public function lastError(): Error
    {
        if ($this->error === null) {
            throw new NoErrorException;
        }

        return $this->error;
    }

    /**
     * Set error
     *
     * Sets the error based on PDOs error info.
     *
     * @param string $query Query that caused the error. Default ""
     * @param array $errInfo Error info array, if left empty PDO Error Info array is obtained
     * @return void
     */
    protected function setError(string $query = "", array $errInfo = [])
    {
        if (empty($errInfo)) {
            $errInfo = $this->getPdo()->errorInfo();
        }
        $this->error = new Error($errInfo[2], $query);
    }


    /**
     * Get PDO
     *
     * Gets the PDO from the closure or from the internal property.
     *
     * @return \PDO
     */
    protected function getPdo(): PDO
    {
        if ($this->pdo === null) {
            $this->pdo = ($this->pdoLoader)();
            if ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === "mysql") {
                $this->delim = "`";
            }
        }
        return $this->pdo;
    }
}
