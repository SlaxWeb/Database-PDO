<?php
namespace SlaxWeb\DatabasePDO\Command\Migration;

use SlaxWeb\Bootstrap\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use SlaxWeb\DatabasePDO\Exception\MigrationException;
use SlaxWeb\DatabasePDO\Exception\MigrationExistsException;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;

/**
 * Execute Migration Command
 *
 * Executes the specified migrations or all un-executed migrations. The command
 * is also used to revert the migrations.
 *
 * @package   SlaxWeb\DatabasePDO
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.6
 */
class Create extends Command
{
    /**
     * Application instance
     *
     * @var \SlaxWeb\Bootstrap\Appliation
     */
    protected $app = null;

    /**
     * Console intput
     *
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    protected $input = null;

    /**
     * Console output
     *
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected $output = null;

    /**
     * Initialize command
     *
     * Init the command and set required services to protected properties.
     *
     * @param \SlaxWeb\Bootstrap\Application $app Application instance
     * @return void
     */
    public function init(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Configure command
     *
     * Configure the command name and arguments.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName("migration:exec")
            ->setDescription(
                "Executes or reverts a specific migration or all previously non-executed migrations"
            )->addArgument(
                "names",
                InputArgument::IS_ARRAY,
                "List of migration names separated by a space. If ommited, all migrations "
                . "will be executed. Required if performing a revert."
            )->addOption(
                "revert",
                "r",
                InputOption::VALUE_NONE,
                "When enabled the specified migrations will be reverted."
            )->addOption(
                "force",
                "f",
                InputOption::VALUE_NONE,
                "Force migration execution. If a migration has already been executed "
                . "it will be executed again"
            );
    }

    /**
     * Execute command
     *
     * Add a migration to the list with the Migration Manager.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input Console input
     * @param \Symfony\Component\Console\Output\OutputInterface $output Console output
     * @return void
     */
    protected function execute(Input $input, Output $output)
    {
        $this->input = $input;
        $this->output = $output;

        if ($this->output->isDebug()) {
            ini_set("display_errors", 1);
            error_reporting(E_ALL);
        }

        $names = $this->input->getArgument("names");
        $revert = $this->input->getOption("revert");
        $force = $this->input->getOption("force");
        $method = $revert === true ? "revert" : "run";

        if ($revert === true && empty($names)) {
            $this->output->writeln("<error>Migration names may not be ommited when reverting!");
            return;
        }

        $failed = $this->app["migration.service"]->{$method}($names, $force);

        if (empty($failed) === false) {
            $this->output->writeln("<info>The following migrations failed to execute:</>");
            foreach ($failed as $migration) {
                $this->output->writeln("* {$migration}");
            }
        }

        $this->output->writeln("<info>Migrations successfully executed.</>");
    }
}
