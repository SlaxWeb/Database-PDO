<?php
/**
 * Where Predicate Group
 *
 * The Where Predicate Group class groups multiple Predicate objects that allows
 * building more complicated where statements.
 *
 * @package   SlaxWeb\DatabasePDO
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.4
 */
namespace SlaxWeb\DatabasePDO\Query\Where;

use SlaxWeb\DatabasePDO\Query\Builder;

class Group
{
    /**
     * Table
     *
     * @var string
     */
    protected $_table = "";

    /**
     * Predicament list
     *
     * @var array
     */
    protected $_list = [];

    /**
     * Logical operator
     *
     * @var string
     */
    protected $_opr = "";

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
     * Class constructor
     *
     * Sets the logical operator that will be used to link this Predicate Group
     * in the SQL DML WHERE statement.
     *
     * @param string $opr Logical operator name, default string("AND")
     * @return void
     */
    public function __construct(string $opr = "AND")
    {
        $this->_opr = $opr;
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
        $this->_table = $table;
        return $this;
    }

    /**
     * Set DB Object Delimiter
     *
     * Sets the Database Object Delimiter character that will be used for
     * creating
     * the query.
     *
     * @param string $delim Delimiter character
     * @return self
     */
    public function setDelim(string $delim): self
    {
        $this->_delim = $delim;
        return $this;
    }

    /**
     * Convert Predicate Group to Statement
     *
     * Creates the SQL DML Where statement from the predicate list and returns it
     * to the caller.
     *
     * @return string
     */
    public function convert(): string
    {
        if (count($this->_list) < 1) {
            return "";
        }

        $where = " {$this->_opr} (";
        $first = array_shift($this->_list);
        $where .= $first["predicate"]->convert();
        $this->_params = array_merge($this->_params, $first["predicate"]->getParams());
        foreach ($this->_list as $predicate) {
            $where .= " {$predicate["opr"]} " . $predicate["predicate"]->convert();
            $this->_params = array_merge($this->_params, $predicate["predicate"]->getParams());
        }
        return "{$where})";
    }

    /**
     * Get parameters
     *
     * Returns the list of parameters for this predicate.
     *
     * @return array
     */
    public function getParams(): array
    {
        return $this->_params;
    }

    /**
     * Add predicament
     *
     * Creates a predicate object with the input parameters, and adds it to the
     * list of predicates. It returns an object of itself for method call linking.
     *
     * @param string $column Name of the column for the predicate
     * @param mixed $value Value for the predicate
     * @param stirng $lOpr Logical operator, default Predicate::OPR_EQUAL
     * @param string $cOpr Comparisson operator, default string("AND")
     * @return self
     */
    public function where(string $column, $value, string $lOpr = Predicate::OPR_EQUAL, string $cOpr = "AND"): self
    {
        $this->_list[] = [
            "opr"       =>  $cOpr,
            "predicate" =>  (new Predicate)
                ->setColumn($this->_table . "." . $this->_delim . $column . $this->_delim)
                ->setValue($value)
                ->setOperator($lOpr)
        ];
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
     * @param Closure $predicates Grouped predicates definition closure
     * @param string $cOpr Comparisson operator, default string("AND")
     * @return self
     */
    public function groupWhere(\Closure $predicates, string $cOpr = "AND"): self
    {
        $group = (new Group($cOpr))->setDelim($this->_delim)->table($this->_table);
        $predicates($group);
        $this->_list[] = [
            "opr"       =>  "",
            "predicate" =>  $group
        ];
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
     */
    public function nestedWhere(
        string $column,
        \Closure $nested,
        string $lOpr = Predicate::OPR_IN,
        string $cOpr = "AND"
    ): self {
        $builder = (new Builder)->setDelim($this->_delim);
        $this->_list[] = [
            "opr"       =>  $cOpr,
            "predicate" =>  (new Predicate)
                ->setColumn($this->_table . "." . $this->_delim . $column . $this->_delim)
                ->setValue($nested($builder), false, $builder->getParams())
                ->setOperator($lOpr)
        ];
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
        $this->nestedWhere($column, $nested, $lOpr, "OR");
        return $this;
    }
}
