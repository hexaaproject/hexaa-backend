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
use Symfony\Component\Console\Exception\InvalidArgumentException;
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
        $this->loglbl = "[hexaa:hook:dispatch] [pid=".getmypid()."] ";

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
            'Cache ID of data'
          );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $value = $input->getArgument('value');
        } catch (InvalidArgumentException $e) {
            $this->hookLog->critical($this->loglbl.'Value argument not found, terminating.');

            return 1;
        }
        $this->hookLog->info($this->loglbl.'Command started');
        $this->hookLog->debug($this->loglbl."Parameter: ".$value);
        $hooksToDispatch = $this->hookExtractor->extractAll($value);

        if ($hooksToDispatch === null) {
            $this->hookLog->error($this->loglbl."Called with invalid parameters, value: ".$value." had no cache hit.");
            $output->writeln("<error>Invalid parameter, value: ".$value.PHP_EOL."No cache hit.</error>");

            return 1;
        }

        $this->hookLog->debug($this->loglbl.'Extracted all hooks.');

        if (count($hooksToDispatch) == 0) {
            $this->hookLog->info($this->loglbl.'Found no hooks to dispatch.');
        }

        foreach ($hooksToDispatch as $hooksEntry) {
            foreach ($hooksEntry as $hookEntry) {
                /* @var $hook Hook */
                $hook = $hookEntry['hook'];

                if (count($hookEntry['content']) < 1) {
                    $this->hookLog->info(
                      $this->loglbl."Hook content empty, not sending! (URL: ".$hook->getUrl()
                      .", type: ".$hook->getType()
                    );
                    continue;
                }

                // Initializing curl
                $curl = curl_init($hook->getUrl());


                // Configuring curl options
                $curlOptions = array(
                  CURLOPT_RETURNTRANSFER => true,
                  CURLOPT_HTTPHEADER     => array('Content-type: application/json'),
                  CURLOPT_TIMEOUT        => 30,
                  CURLOPT_POSTFIELDS     => json_encode(
                    array(
                      "action" => $hook->getType(),
                      "data"   => (array)$hookEntry["content"],
                      "key"    => $hook->getService()->getHookKey(),
                    )
                  ),
                );

                // Setting curl options
                curl_setopt_array($curl, $curlOptions);

                $this->hookLog->info($this->loglbl."Issuing POST request to ".$hook->getUrl());

                // Getting results
                $result = curl_exec($curl);

                if (curl_errno($curl)) {
                    $this->hookLog->warn(
                      $this->loglbl."Error response received for hook with url: ".$hook->getUrl()
                      .". Errno: ".curl_errno($curl)
                    );

                    $hook->setLastCallMessage("ERROR, server reply: ".$result ? " empty" : $result);
                } else {
                    if (intval(curl_getinfo($curl, CURLINFO_HTTP_CODE)) !== 200) {
                        $this->hookLog->warn(
                          $this->loglbl."Error response received for hook with url: ".$hook->getUrl()
                          .". HTTP statuscode: ".intval(curl_getinfo($curl, CURLINFO_HTTP_CODE))
                        );

                        $hook->setLastCallMessage(
                          "ERROR, HTTP status code: ".intval(curl_getinfo($curl, CURLINFO_HTTP_CODE))
                          ."server reply: ".$result ? " empty" : $result
                        );
                    } else {
                        $this->hookLog->info(
                          $this->loglbl."Response received for successful hook call with url: ".$hook->getUrl()
                        );

                        $hook->setLastCallMessage("SUCCESS, server reply: ".$result ? " empty" : $result);
                    }
                }

                $this->em->persist($hook);
            }
        }
        $this->em->flush();

        return 0;
    }


}
