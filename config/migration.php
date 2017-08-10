<?php
/**
 * Migration Config
 *
 * Database PDO sub-component migration configuration.
 *
 * @package   SlaxWeb\DatabasePDO
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2017 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.6
 */
/*
 * Migration repository location
 *
 * Default: app/Migrations/
 */
$configuration["repository"] = __DIR__ . "/../Migrations/";

/*
 * Migration class namespace
 *
 * Default: \App\Migration\
 */
$configuration["namespace"] = "\\App\\Migration\\";
