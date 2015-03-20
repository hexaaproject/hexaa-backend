<?php
namespace Hexaa\StorageBundle\Command;

use Hexaa\ApiBundle\Hook\ExpireHook\ExpireConsentsHook;
use Hexaa\ApiBundle\Hook\ExpireHook\ExpireLinkerTokensHook;
use Hexaa\ApiBundle\Hook\ExpireHook\ExpirePrincipalsHook;
use Hexaa\ApiBundle\Hook\ExpireHook\ReviewAttributesHook;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExpireCommand extends ContainerAwareCommand
{
    protected $expireLinkerTokenHook;
    protected $expirePrincipalsHook;
    protected $expireConsentsHook;
    protected $reviewAttributesHook;

    function __construct(ExpireLinkerTokensHook $expireLinkerTokenHook, ExpirePrincipalsHook $expirePrincipalsHook,
                         ExpireConsentsHook $expireConsentsHook, ReviewAttributesHook $reviewAttributesHook)
    {
        $this->expireLinkerTokenHook = $expireLinkerTokenHook;
        $this->expirePrincipalsHook = $expirePrincipalsHook;
        $this->expireConsentsHook = $expireConsentsHook;
        $this->reviewAttributesHook = $reviewAttributesHook;

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
        $validEntities = array("all", "consent", "principal", "linker_token", "attribute_value");
        if (!(count($entities)==1 && $entities[0] == "all")){
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
                        break;
                    case "principal":
                        $this->expirePrincipalsHook->runHook();
                        break;
                    case "consent":
                        $this->expireConsentsHook->runHook();
                        break;
                    case "attribute_value":
                        $this->reviewAttributesHook->runHook();
                        break;
                    case "all":
                        $this->expireLinkerTokenHook->runHook();
                        $this->expirePrincipalsHook->runHook();
                        $this->expireConsentsHook->runHook();
                        $this->reviewAttributesHook->runHook();
                        break;
                }
            }
        }
    }
}