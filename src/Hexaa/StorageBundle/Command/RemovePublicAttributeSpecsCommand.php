<?php
namespace Hexaa\StorageBundle\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RemovePublicAttributeSpecsCommand extends ContainerAwareCommand {
    protected $em;

    public function __construct(EntityManager $entityManager){
        $this->em = $entityManager;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('hexaa:remove_public_attribute_specs')
            ->setDescription('Remove (or convert) public attributeSpec <=> Service associations')
            ->addOption(
                'convert-to-private',
                'c',
                InputOption::VALUE_NONE,
                'If set, the script will attempt to change to private before removal'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $publicServiceAttributeSpecs = $this->em->getRepository('HexaaStorageBundle:ServiceAttributeSpec')->findBy(array("isPublic" => true));
        foreach($publicServiceAttributeSpecs as $publicServiceAttributeSpec) {
            if ($input->getOption('convert-to-private')){
                $publicServiceAttributeSpec->setIsPublic(false);
                $this->em->persist($publicServiceAttributeSpec);
            } else {
                $this->em->remove($publicServiceAttributeSpec);
            }
        }
        $this->em->flush();

    }
}