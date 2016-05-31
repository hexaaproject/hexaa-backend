<?php
/**
 * Created by PhpStorm.
 * User: baloo
 * Date: 2/3/15
 * Time: 3:36 PM
 */

namespace Hexaa\ApiBundle\Hook\ExpireHook;


use Doctrine\ORM\EntityManager;
use Monolog\Logger;

abstract class ExpireHook
{
    protected $em;
    protected $modlog;
    protected $errorlog;

    public function __construct(EntityManager $entityManager, Logger $modlog, Logger $errorlog)
    {
        $this->em = $entityManager;
        $this->errorlog = $errorlog;
        $this->modlog = $modlog;
    }

    public abstract function runHook();

}