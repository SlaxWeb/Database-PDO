<?php
namespace SlaxWeb\DatabasePDO\Command\Migration;

use SlaxWeb\Bootstrap\Application;
use SlaxWeb\Slaxer\AbstractCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use SlaxWeb\DatabasePDO\Exception\MigrationException;
use SlaxWeb\DatabasePDO\Exception\MigrationExistsException;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;

/**
 * Remove Migration Command
 *
 * Reverts the migration and removes it from the migration repository.
 *
 * @package   SlaxWeb\DatabasePDO
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.6
 */
class Remove extends AbstractCommand
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
        $this->setName("migration:remove")
            ->setDescription(
                "Reverts the migration and removes it from the migration repository"
            )->addArgument(
                "name",
                InputArgument::IS_ARRAY,
                "Name of the migration to revert and remove"
            )->addOption(
                "no-revert",
                "r",
                InputOption::VALUE_NONE,
                "Do not revert the migration but just remove it"
            )->addOption(
                "force",
                "f",
                InputOption::VALUE_NONE,
                "Removes the migration even if the revert failed"
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

        $name = $this->input->getArgument("name");
        $noRevert = $this->input->getOption("no-revert");
        $force = $this->input->getOption("force");

        if ($this->app["migration.service"]->remove($name, !$noRevert) === false
            && $noRevert === false
            && $force
        ) {
            $this->app["migration.service"]->remove($name);
        }

        $this->output->writeln("<info>Migration successfully removed.</>");
    }
}
