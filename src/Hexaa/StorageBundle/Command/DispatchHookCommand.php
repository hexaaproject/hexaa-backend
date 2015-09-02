<?php
/**
 * Created by PhpStorm.
 * User: baloo
 * Date: 9/1/15
 * Time: 10:31 AM
 */

namespace Hexaa\StorageBundle\Command;


use Hexaa\StorageBundle\Util\HookExtractor;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DispatchHookCommand extends ContainerAwareCommand {
    protected $hookExtractor;
    protected $hookLog;

    public function __construct(HookExtractor $hookFactory, Logger $hookLog) {
        $this->hookExtractor = $hookFactory;
        $this->hookLog = $hookLog;

        parent::__construct();
    }

    protected function configure() {
        $this
            ->setName('hexaa:dispatch:hook')
            ->setDescription('Dispatch hooks for de/provisioning, DO NOT INVOKE MANUALLY!')
            ->addArgument(
                'value',
                InputArgument::REQUIRED,
                'Necessary values in JSON format'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $valueJson = $input->getArgument('value');
        $value = json_decode($valueJson, true);
        if ($value === null) {
            $output->writeln("<error>Invalid parameters</error>");
        }
        $hooksToDispatch = array();
        foreach($value as $hook) {
            $hooksToDispatch[] = $this->hookExtractor->extract($hook);
        }

        // ToDo: make the call
    }

}