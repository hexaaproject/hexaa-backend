<?php
namespace Hexaa\StorageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Hexaa\StorageBundle\Entity\HexaaRepository")
 */
class Hexaa
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;
    // Dummy class for repo
    
    
} 
