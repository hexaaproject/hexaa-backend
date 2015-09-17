<?php
/**
 * Created by PhpStorm.
 * User: baloo
 * Date: 9/1/15
 * Time: 10:31 AM
 */

namespace Hexaa\StorageBundle\Command;


use Doctrine\ORM\EntityManager;
use Hexaa\StorageBundle\Entity\Hook;
use Hexaa\StorageBundle\Util\HookExtractor;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DispatchHookCommand extends ContainerAwareCommand
{
    protected $hookExtractor;
    protected $hookLog;
    protected $em;
    protected $loglbl = "[hexaa:hook:dispatch] ";

    public function __construct(EntityManager $em, HookExtractor $hookFactory, Logger $hookLog)
    {
        $this->hookExtractor = $hookFactory;
        $this->hookLog = $hookLog;
        $this->em = $em;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('hexaa:hook:dispatch')
            ->setDescription('Dispatch hooks for de/provisioning, DO NOT INVOKE MANUALLY!')
            ->addArgument(
                'value',
                InputArgument::REQUIRED,
                'Necessary values in JSON format'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $valueJson = $input->getArgument('value');
        $value = json_decode($valueJson, true);
        if ($value === null) {
            $output->writeln("<error>Invalid parameters, value: " . var_export($value, true) . "</error>");
            $this->hookLog->error($this->loglbl . "Called with invalid parameters, value: " . var_export($valueJson, true));
            return;
        }
        $this->hookLog->info($this->loglbl . "Command started");
        $this->hookLog->debug($this->loglbl . "Parameter: " . $valueJson);
        $hooksToDispatch = array();
        foreach($value as $hookEntry) {
            $hooksToDispatch[] = $this->hookExtractor->extract($hookEntry);
        }

        foreach($hooksToDispatch as $hooksEntry) {
            foreach($hooksEntry as $hookEntry) {
                /* @var $hook Hook */
                $hook = $hookEntry['hook'];

                // Initializing curl
                $curl = curl_init($hook->getUrl());


                // Configuring curl options
                $curlOptions = array(
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER     => array('Content-type: application/json'),
                    CURLOPT_POSTFIELDS     => json_encode(array("action" => $hook->getType(), "data" => $hookEntry["content"]))
                );

                // Setting curl options
                curl_setopt_array($curl, $curlOptions);

                // Getting results
                $result = curl_exec($curl);

                if (curl_errno($curl)) {
                    $this->hookLog->info($this->loglbl . "Error response received for hook with url: " . $hook->getUrl()
                        . ". Errno: " . curl_errno($curl));

                    $hook->setLastCallMessage("ERROR, server reply: " . $result ? " empty" : $result);
                } else {
                    $this->hookLog->info($this->loglbl . "Response received for successful hook call with url: " . $hook->getUrl());

                    $hook->setLastCallMessage("SUCCESS, server reply: " . $result ? " empty" : $result);
                }

                $this->em->persist($hook);
            }
        }
        $this->em->flush();
    }


}