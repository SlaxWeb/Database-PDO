<?php
/**
 * Migration Exists Exception
 *
 * Thrown specifically at migration creation, when an migration with the same name
 * already exists.
 *
 * @package   SlaxWeb\DatabasePDO
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.6
 */
namespace SlaxWeb\DatabasePDO\Exception;

class MigrationExistsException extends \Exception
{
}
