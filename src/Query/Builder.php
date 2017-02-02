<?php
/**
 * Query Builder
 *
 * The Query Builder is used to do exactly what its name suggests. Build SQL queries
 * for execution.
 *
 * @package   SlaxWeb\DatabasePDO
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.4
 */
namespace SlaxWeb\DatabasePDO\Query;

use SlaxWeb\DatabasePDO\Query\Where\Group;
use SlaxWeb\DatabasePDO\Query\Where\Predicate;

class Builder
{
    /**
     * Order by directions
     */
    const ORDER_ASC = "ASC";
    const ORDER_DESC = "DESC";

    /**
     * Table
     *
     * @var string
     */
    protected $table = "";

    /**
     * Parameters
     *
     * @var array
     */
    protected $params = [];

    /**
     * SQL Object Delimiter
     *
     * @var string
     */
    protected $delim = "";

    /**
     * Where Predicate Group object
     *
     * @var \SlaxWeb\DatabasePDO\Query\Where\Group
     */
    protected $predicates = null;

    /**
     * Join list
     *
     * @var array
     */
    protected $joins = [];

    /**
     * Group columns
     *
     * @var array
     */
    protected $groupCols = [];

    /**
     * Order by columns
     *
     * @var array
     */
    protected $orderCols = [];

    /**
     * Limit
     *
     * @var int
     */
    protected $limit = 0;

    /**
     * Offset
     *
     * @var int
     */
    protected $offset = 0;

    /**
     * Class constructor
     *
     * Prepare the predictes list by instantiating the first predicate group object.
     *
     * @return void
     */
    public function __construct()
    {
        $this->predicates = new Group;
    }

    /**
     * Set DB Object Delimiter
     *
     * Sets the Database Object Delimiter character that will be used for creating
     * the query.
     *
     * @param string $delim Delimiter character
     * @return self
     */
    public function setDelim(string $delim): self
    {
        $this->delim = $delim;
        $this->predicates->setDelim($delim);
        return $this;
    }

    /**
     * Set table
     *
     * Sets the table name for the query. Before setting it wraps it in the delimiters.
     *
     * @param string $table Name of the table
     * @return self
     */
    public function table(string $table): self
    {
        $this->table = $this->delim . $table . $this->delim;
        $this->predicates->table($this->table);
        return $this;
    }

    /**
     * Reset the builder
     *
     * Restes the builder, by re-initializing the main predicate group, clearing
     * out parameters, and join definitions.
     *
     * @return self
     */
    public function reset(): self
    {
        $this->predicates = new Group;
        $this->predicates->table($this->table);
        $this->predicates->setDelim($this->delim);
        $this->params = [];
        $this->joins = [];
        return $this;
    }

    /**
     * Get Bind Parameters
     *
     * Returns the parameters prepared for binding into the prepared statement.
     *
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Get INSERT query
     *
     * Create the insert query based on the array of data. It prepares the query
     * for parameter binding, and stores the parameter values in the local parameters
     * property which can be retrieved with 'getParams' method call.
     *
     * @param array $data Data to be inserted
     * @return string
     */
    public function insert(array $data): string
    {
        $this->params = array_values($data);
        return "INSERT INTO {$this->table} ({$this->delim}"
            . implode("{$this->delim},{$this->delim}", array_keys($data))
            . "{$this->delim}) VALUES ("
            . rtrim(str_repeat("?,", count($data)), ",")
            . ")";
    }

    /**
     * Get SELECT query
     *
     * Construct the query with all the information gathered and return it. The
     * Method retrieves a column list as an input parameter. All columns are wrapped
     * in the delimiters to prevent collision with reserved keywords. If the array
     * item is another array, it needs to hold the "func" and "col" keys at least,
     * defining the SQL DML function, as well as the column name. A third item with
     * the key name "as" can be added, and this name will be used in the "AS" statement
     * in the SQL DML for that column.
     *
     * @param array $cols Column definitions
     * @return string
     */
    public function select(array $cols): string
    {
        $query = "SELECT " . $this->buildColList($cols, $this->table);

        // create join statements
        $joinStmnt = "";
        foreach ($this->joins as $join) {
            // build the join statement
            $joinStmnt .= "{$join["type"]} {$join["table"]}";
            if ($join["type"] !== "CROSS JOIN") {
                if (empty($join["cond"])) {
                    throw new \SlaxWeb\DatabasePDO\Exception\NoJoinConditionException(
                        "A JOIN without a condition is not possible, unless it is a CROSS JOIN."
                    );
                }
                $joinStmnt .= " ON (1=1";
                foreach ($join["cond"] as $cond) {
                    $joinStmnt .= " {$cond["lOpr"]} {$this->table}.{$this->delim}{$cond["primKey"]}{$this->delim} "
                        . "{$cond["cOpr"]} {$join["table"]}.{$this->delim}{$cond["forKey"]}{$this->delim}";
                }
                $joinStmnt .= ") ";
            }

            // add joined columns to select column list
            $query .= $this->buildColList($join["colList"], $join["table"]);
        }
        $query = rtrim($query, ",");
        $query .= " FROM {$this->table} {$joinStmnt}WHERE 1=1" . $this->predicates->convert();
        $this->params = $this->predicates->getParams();

        if ($this->groupCols !== []) {
            $query .= " GROUP BY " . implode(",", $this->groupCols);
        }

        if ($this->orderCols !== []) {
            $query .= " ORDER BY " . implode(",", $this->orderCols);
        }

        if ($this->limit > 0) {
            $query .= " LIMIT {$this->limit}" . ($this->offset > 0 ? " OFFSET {$this->offset}" : "");
        }

        return $query;
    }

    /**
     * Update
     *
     * Create the update statement with the where predicates. As input it takes an
     * array of columns as array item keys and their new values as the array item
     * values.
     *
     * @param array $cols Array of column names and their new values
     * @return string
     */
    public function update(array $cols): string
    {
        $query = "UPDATE {$this->table} SET "
            . implode(",", array_map(function ($value, $column) {
                $col = "{$this->table}.{$this->delim}{$column}{$this->delim} = ";
                if (is_array($value) && isset($value["func"])) {
                    return $col . $value["func"];
                }
                $this->params[] = $value;
                return "{$col}?";
            }, $cols, array_keys($cols)));

        $query .= " WHERE 1=1" . $this->predicates->convert();
        $this->params = array_merge($this->params, $this->predicates->getParams());
        return $query;
    }

    /**
     * Delete
     *
     * Create delete statement with the where predicates.
     *
     * @return string
     */
    public function delete(): string
    {
        $query = "DELETE FROM {$this->table} WHERE 1=1" . $this->predicates->convert();
        $this->params = $this->predicates->getParams();
        return $query;
    }

    /**
     * Add Where Predicate
     *
     * Adds a SQL DML WHERE predicate to the group of predicates. If the group does
     * not yet exist it will create one.
     *
     * @param string $column Column name
     * @param mixed $value Value of the predicate
     * @param string $lOpr Logical operator, default Predicate::OPR_EQUAL
     * @param string $cOpr Comparisson operator, default string("AND")
     * @return self
     */
    public function where(string $column, $value, string $lOpr = Predicate::OPR_EQUAL, string $cOpr = "AND"): self
    {
        $this->predicates->where($column, $value, $lOpr, $cOpr);
        return $this;
    }

    /**
     * Or Where predicate
     *
     * Alias for 'where' method call with OR logical operator.
     *
     * @param string $column Column name
     * @param mixed $value Value of the predicate
     * @param string $opr Logical operator
     * @return self
     */
    public function orWhere(string $column, $value, string $opr = Predicate::OPR_EQUAL): self
    {
        return $this->where($column, $value, $opr, "OR");
    }

    /**
     * Add Where Predicate Group
     *
     * Adds a group of predicates to the list. The closure received as input must
     * receive the builder instance for building groups.
     *
     * @param closure $predicates Grouped predicates definition closure
     * @param string $cOpr Comparisson operator, default string("AND")
     * @return self
     */
    public function groupWhere(\Closure $predicates, string $cOpr = "AND"): self
    {
        $this->predicates->groupWhere($predicates, $cOpr);
        return $this;
    }

    /**
     * Or Where Predicate Group
     *
     * Alias for 'whereGroup' method call with OR logical operator.
     *
     * @param closure $predicates Grouped predicates definition closure
     * @return self
     */
    public function orGroupWhere(\Closure $predicates): self
    {
        return $this->groupWhere($predicates, "OR");
    }

    /**
     * Where Nested Select
     *
     * Add a nested select as a value to the where predicate.
     *
     * @param string $column Column name
     * @param closure $nested Nested builder
     * @param string $lOpr Logical operator, default Predicate::OPR_IN
     * @param string $cOpr Comparisson operator, default string("AND")
     * @return self
     */
    public function nestedWhere(
        string $column,
        \Closure $nested,
        string $lOpr = Predicate::OPR_IN,
        string $cOpr = "AND"
    ): self {
        $this->predicates->nestedWhere($column, $nested, $lOpr, $cOpr);
        return $this;
    }

    /**
     * Or Where Nested Select
     *
     * Alias for 'nestedWhere' method call with OR logical operator.
     *
     * @param string $column Column name
     * @param closure $nested Nested builder
     * @param string $lOpr Logical operator, default Predicate::OPR_IN
     * @return self
     */
    public function orNestedWhere(
        string $column,
        \Closure $nested,
        string $lOpr = Predicate::OPR_IN
    ): self {
        $this->predicates->nestedWhere($column, $nested, $lOpr, "OR");
        return $this;
    }

    /**
     * Add table to join
     *
     * Adds a new table to join with the main table to the list of joins. If only
     * a table is added without a condition with the 'joinCond', an exception will
     * be thrown when an attempt to create a query is made.
     *
     * @param string $table Table to join to
     * @param string $type Join type, default "INNER JOIN"
     * @return self
     */
    public function join(string $table, string $type = "INNER JOIN"): self
    {
        $this->joins[] = [
            "table"     =>  $this->delim . $table . $this->delim,
            "type"      =>  $type,
            "cond"      =>  [],
            "colList"   =>  []
        ];
        return $this;
    }

    /**
     * Add join condition
     *
     * Adds a JOIN condition to the last join added. If no join was yet added, an
     * exception is raised.
     *
     * @param string $primKey Key of the main table for the condition
     * @param string $forKey Key of the joining table
     * @param string $cOpr Comparison operator for the two keys
     * @param string $lOpr Logical operator for multiple JOIN conditions
     * @return self
     *
     * @exceptions \SlaxWeb\DatabasePDO\Exception\NoJoinTableException
     */
    public function joinCond(string $primKey, string $forKey, string $cOpr = Predicate::OPR_EQUAL, $lOpr = "AND"): self
    {
        end($this->joins);
        if (($key = key($this->joins)) === null) {
            throw new \SlaxWeb\DatabasePDO\Exception\NoJoinTableException(
                "Attempt to add a JOIN condition was made, when no table was yet added to join with"
            );
        }
        $this->joins[$key]["cond"][] = [
            "primKey"   =>  $primKey,
            "forKey"    =>  $forKey,
            "cOpr"      =>  $cOpr,
            "lOpr"      =>  $lOpr
        ];
        reset($this->joins);
        return $this;
    }

    /**
     * Add OR join condition
     *
     * Alias for the 'joinCond' with the "OR" logical operator.
     *
     * @param string $primKey Key of the main table for the condition
     * @param string $forKey Key of the joining table
     * @param string $cOpr Comparison operator for the two keys
     * @param string $lOpr Logical operator for multiple JOIN conditions
     * @return self
     */
    public function orJoinCond(string $primKey, string $forKey, string $cOpr = Predicate::OPR_EQUAL): self
    {
        return $this->joinCond($primKey, $forKey, $cOpr, "OR");
    }

    /**
     * Join Columns
     *
     * Add columns to include in the select column list. If no table for joining
     * was yet added, an exception is raised. Same rules apply to the column list
     * as in the 'select' method.
     *
     * @param array $cols Column list
     * @return self
     *
     * @exceptions \SlaxWeb\DatabasePDO\Exception\NoJoinTableException
     */
    public function joinCols(array $cols): self
    {
        end($this->joins);
        if (($key = key($this->joins)) === null) {
            throw new \SlaxWeb\DatabasePDO\Exception\NoJoinTableException(
                "Attempt to add joined table columns was made, when no table was yet added to join with"
            );
        }
        $this->joins[$key]["colList"] = array_merge($this->joins[$key]["colList"], $cols);
        reset($this->joins);
        return $this;
    }

    /**
     * Group by
     *
     * Add a column to the group by list.
     *
     * @param string $col Column name to be added to the group by list.
     * @return self
     */
    public function groupBy(string $col): self
    {
        $this->groupCols[] = $this->table . "." . $this->delim . $col . $this->delim;
        return $this;
    }

    /**
     * Order by
     *
     * Add a column to the order by list.
     *
     * @param string $col Column name to be added to the group by list
     * @param string $direction Direction of order, default self::ORDER_ASC
     * @param string $func SQL function to use ontop of the column, default string("")
     * @return self
     */
    public function orderBy(string $col, string $direction = self::ORDER_ASC, string $func = ""): self
    {
        $orderData = "{$this->table}.{$this->delim}{$col}{$this->delim}";
        if ($func !== "") {
            $orderData = "{$func}({$orderData})";
        }
        $this->orderCols[] = "{$orderData} {$direction}";
        return $this;
    }

    /**
     * Limit
     *
     * Limit number of rows to be returned from the database. Second parameter will
     * also add an offset to the statement.
     *
     * @param int $limit Number of rows to limit the result set to
     * @param int $offset Number of rows for the result to be offset from, default int(0)
     * @return self
     */
    public function limit(int $limit, int $offset = 0): self
    {
        $this->limit = $limit;
        $this->offset = $offset;
        return $this;
    }

    /**
     * Build column list
     *
     * Builds a column list from the input column list and the table name. It automatically
     * prepends columns with the supplied table name and wraps the columns in the
     * database object delimiters.
     *
     * @param array $cols Array of columns to be added to the list
     * @param string $table Table name used to prepend the columns with
     * @return string
     */
    protected function buildColList(array $cols, string $table): string
    {
        $colList = "";
        foreach ($cols as $name) {
            // create "table"."column"
            if (is_array($name)) {
                $colList .= strtoupper($name["func"] ?? "");
                $col = $table . "." . $this->delim . $name["col"] . $this->delim;
                $colList .= "({$col})";
                if (isset($name["as"])) {
                    $colList .= " AS {$name["as"]},";
                }
            } else {
                $name = $table . "." . $this->delim . $name . $this->delim;
                $colList .= "{$name},";
            }
        }
        return $colList;
    }
}
