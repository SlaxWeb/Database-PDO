<?php
namespace SlaxWeb\DatabasePDO\Command\Migration;

use SlaxWeb\Bootstrap\Application;
use SlaxWeb\Slaxer\AbstractCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;

/**
 * List Migrations Command
 *
 * Migrations command to list already added and executed migrations.
 *
 * @package   SlaxWeb\DatabasePDO
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.6
 */
class ListMigrations extends AbstractCommand
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
        $this->setName("migration:list")
            ->setDescription("List added migrations");
    }

    /**
     * Execute command
     *
     * Read the migrations from the migration manager and list them to the console.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input Console input
     * @param \Symfony\Component\Console\Output\OutputInterface $output Console output
     * @return void
     */
    protected function execute(Input $input, Output $output)
    {
        $this->input = $input;
        $this->output = $output;

        $this->output->writeln("<comment>Created migrations:</>");

        foreach ($this->app["migration.service"]->get() as $name => $migration) {
            $this->output->write("{$name}: ");
            if ($migration["executed"] > -1) {
                $this->output->write("<fg=green>EXECUTED</>");
                $this->output->write(
                    " ({$migration["executed"]})",
                    Output::VERBOSITY_VERBOSE
                );
                $this->output->writeln("");
            } else {
                $this->output->writeln("<fg=red>NOT EXECUTED</>");
            }
        }
    }
}
