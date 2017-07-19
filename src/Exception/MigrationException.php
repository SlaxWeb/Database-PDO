<?php
/**
 * Migration Exception
 *
 * Thrown if an exception occurs with the exception, either when creating it, executing
 * it, reverting it, or removing it.
 *
 * @package   SlaxWeb\DatabasePDO
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.6
 */
namespace SlaxWeb\DatabasePDO\Exception;

class MigrationException extends \Exception
{
}
