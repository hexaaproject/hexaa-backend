<?php

namespace Hexaa\ApiBundle\Hook\ExpireHook;


use Doctrine\ORM\EntityManager;
use Monolog\Logger;

class ReviewAttributesHook extends ExpireHook
{
    protected $maillog;
    protected $hexaaUiUrl;
    protected $mailer;
    protected $fromAddress;

    public function __construct(
        EntityManager $entityManager,
        Logger $modlog,
        Logger $errorlog,
        Logger $maillog,
        \Swift_Mailer $mailer,
        $hexaaUiUrl,
        $fromAddress
    ) {
        $this->maillog = $maillog;
        $this->hexaaUiUrl = $hexaaUiUrl;
        $this->mailer = $mailer;
        $this->fromAddress = $fromAddress;

        parent::__construct($entityManager, $modlog, $errorlog);
    }

    public function runHook()
    {
        $now = new \DateTime('now');
        $now->modify("-6 months");
        $otherDay = new \DateTime('yesterday');
        $otherDay->modify("-6 months");
        date_timezone_set($now, new \DateTimeZone("UTC"));
        $principals = $this->em->createQueryBuilder()
            ->select("avp.principal")
            ->from("HexaaStorageBundle:AttributeValuePrincipal", 'avp')
            ->where("avp.updatedAt between :now and :yesterday")
            ->setParameters(array(
                ":now"       => $now,
                ":yesterday" => $otherDay
            ))
            ->getQuery()
            ->getResult();

        $tos = array();
        /* @var $principal \Hexaa\StorageBundle\Entity\Principal */
        foreach ($principals as $principal) {
            if ($principal->getDisplayName() != null) {
                $tos[] = array($principal->getDisplayName() => $principal->getEmail());
            } else {
                $tos[] = $principal->getEmail();
            }
        }

        $this->sendNoticeMails($tos);
    }

    private function sendNoticeMails($tos)
    {
        foreach ($tos as $to) {
            $message = \Swift_Message::newInstance()
                ->setSubject('[hexaa] Please review your attributes')
                ->setFrom($this->fromAddress)
                ->setTo($to)
                ->setBody(
                    $this->renderView(
                        'HexaaApiBundle:Default:expiredPrincipalNotice.txt.twig', array(
                            'url' => $this->hexaaUiUrl . "/index.html#/profile/consents"
                        )
                    ), "text/plain"
                );
            $this->mailer->send($message);
            $this->maillog->info("[ExpirePrincipalsHook] E-mail sent to " . var_export($to, true));
        }
    }
}