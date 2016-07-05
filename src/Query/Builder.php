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
     * Table
     *
     * @var string
     */
    protected $_table = "";

    /**
     * Parameters
     *
     * @var array
     */
    protected $_params = [];

    /**
     * SQL Object Delimiter
     *
     * @var string
     */
    protected $_delim = "";

    /**
     * Where Predicate Group object
     *
     * @var \SlaxWeb\DatabasePDO\Query\Where\Group
     */
    protected $_predicates = null;

    /**
     * Class constructor
     *
     * Prepare the predictes list by instantiating the first predicate group object.
     *
     * @return void
     */
    public function __construct()
    {
        $this->_predicates = new Group;
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
        $this->_delim = $delim;
        $this->_predicates->setDelim($delim);
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
        $this->_table = $this->_delim . $table . $this->_delim;
        $this->_predicates->table($table);
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
        return $this->_params;
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
     *
     * @todo: build complicated select statements when additional where predicates
     *        are defined, joins, group bys, etc.
     */
    public function select(array $cols): string
    {
        $query = "SELECT ";
        foreach ($cols as $name) {
            // create "table"."column"
            if (is_array($name)) {
                $query .= strtoupper($name["func"] ?? "");
                $col = $this->_table . "." . $this->_delim . $name["col"] . $this->_delim;
                $query .= "({$col})";
                if (isset($name["as"])) {
                    $query .= " AS {$name["as"]},";
                }
            } else {
                $name = $this->_table . "." . $this->_delim . $name . $this->_delim;
                $query .= "{$name},";
            }
        }
        $query = rtrim($query, ",");
        $query .= " FROM {$this->_table} WHERE 1=1" . $this->_predicates->convert();
        $this->_params = $this->_predicates->getParams();

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
        $this->_predicates->where($column, $value, $lOpr, $cOpr);
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
        $this->_predicates->groupWhere($predicates, $cOpr);
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
        $this->_predicates->nestedWhere($column, $nested, $lOpr, $cOpr);
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
        $this->_predicates->nestedWhere($column, $nested, $lOpr, "OR");
        return $this;
    }
}
