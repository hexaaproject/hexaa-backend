<?php
namespace Hexaa\StorageBundle\Command;

use Hexaa\ApiBundle\Hook\ExpireHook\ExpireLinkerTokensHook;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExpireCommand extends ContainerAwareCommand
{
    protected $expireLinkerTokenHook;

    function __construct(ExpireLinkerTokensHook $expireLinkerTokenHook)
    {
        $this->expireLinkerTokenHook = $expireLinkerTokenHook;

        parent::__construct();
    }


    protected function configure()
    {
        $this
            ->setName('hexaa:expire')
            ->setDescription('Check and/or remove expired entities in HEXAA')
            ->addArgument(
                'entity',
                InputArgument::REQUIRED | InputArgument::IS_ARRAY,
                'What do you want to check (separate multiple entities with space)?'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $errorList = array();
        $invalidArg = false;

        $entities = $input->getArgument('entity');
        $validEntities = array("all", "consent", "principal", "linker_token");
        if (count($entities)==1 && $entities[0] == "all"){
            $text = 'all';

            $output->writeln($text);
        } else {
            foreach ($entities as $entity){
                if ($entity == 'all'){
                    $errorList[] = "'all' can not be used in conjunction with other entities";
                } elseif (!in_array($entity, $validEntities)){
                    $errorList[] = "Invalid entity specified: " . $entity;
                    $invalidArg = true;
                }
            }
        }
        foreach ($errorList as $error) {
            $output->writeln("<error>" . $error . "</error>");
        }

        if ($invalidArg){
            $output->writeln("<error>Valid entities are: \n 'all'\n 'consent'\n 'principal'\n 'linker_token'</error>");
        }

        if ((count($errorList) == 0) && !$invalidArg){
            foreach($entities as $entity){
                switch($entity){
                    case "linker_token":
                        $this->expireLinkerTokenHook->runHook();
                }
            }
        }
    }
}