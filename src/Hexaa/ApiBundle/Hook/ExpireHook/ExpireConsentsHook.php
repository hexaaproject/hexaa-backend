<?php
/**
 * Created by PhpStorm.
 * User: baloo
 * Date: 2/4/15
 * Time: 11:29 AM
 */

namespace Hexaa\ApiBundle\Hook\ExpireHook;


use Doctrine\ORM\EntityManager;
use Monolog\Logger;

class ExpireConsentsHook extends ExpireHook {
    protected $maillog;
    protected $hexaaUiUrl;
    protected $mailer;

    public function __construct(EntityManager $entityManager, Logger $modlog, Logger $errorlog, Logger $maillog, \Swift_Mailer $mailer, $hexaaUiUrl){
        $this->maillog = $maillog;
        $this->hexaaUiUrl = $hexaaUiUrl;
        $this->mailer = $mailer;
    }

    public function runHook() {
        $now = new \DateTime('now');
        date_timezone_set($now, new \DateTimeZone("UTC"));
        $principals = $this->em->createQueryBuilder()
            ->select("c.principal")
            ->from("HexaaStorageBundle:Consent", 'c')
            ->where("c.expiration <= :now")
            ->setParameters(array(":now" => $now))
            ->getQuery()
            ->getResult()
            ;

        /* @var $principal \Hexaa\StorageBundle\Entity\Principal */
        foreach($principals as $principal){
            $message = \Swift_Message::newInstance()
                ->setSubject('[hexaa] Consent renewal')
                // TODO: test needed, might have to define a parameter to get some value here
                // ->setFrom('hexaa@' . $baseUrl)
                ->setBody(
                    $this->renderView(
                        'HexaaApiBundle:Default:expiredConsent.txt.twig', array(
                            'url' => $this->hexaaUiUrl . "/index.html#/profile/consents"
                        )
                    ), "text/plain"
                );
            if ($principal->getDisplayName() != "") {
                $message->setTo(array($principal->getEmail() => $principal->getDisplayName()));
            } else {
                $message->setTo($principal->getEmail());
            }
            $this->mailer->send($message);
            $this->maillog->info("[ExpireConsentsHook] E-mail sent to " . $principal->getEmail());
        }

    }
}