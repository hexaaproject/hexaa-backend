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

class ExpireConsentsHook extends ExpireHook
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
    }

    public function runHook()
    {
        $now = new \DateTime('now');
        $otherDay = new \DateTime('yesterday');
        date_timezone_set($now, new \DateTimeZone("UTC"));
        $principals = $this->em->createQueryBuilder()
            ->select("c.principal")
            ->from("HexaaStorageBundle:Consent", 'c')
            ->where("c.expiration between :now and :yesterday")
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

        $this->sendNoticeMails($tos, false);

        $tos = array();
        $now->modify("-14 days");
        $otherDay->modify("-14 days");

        $noticePrincipals = $this->em->createQueryBuilder()
            ->select("c.principal")
            ->from("HexaaStorageBundle:Consent", 'c')
            ->where("c.expiration between :now and :yesterday")
            ->setParameters(array(
                ":now"       => $now,
                ":yesterday" => $otherDay
            ))
            ->getQuery()
            ->getResult();

        /* @var $principal \Hexaa\StorageBundle\Entity\Principal */
        foreach ($noticePrincipals as $principal) {
            if (!in_array($principal, $principals, true)) {
                if ($principal->getDisplayName() != null) {
                    $tos[] = array($principal->getDisplayName() => $principal->getEmail());
                } else {
                    $tos[] = $principal->getEmail();
                }
            }
        }

        $this->sendNoticeMails($tos, true);

    }

    private function sendNoticeMails($tos, $notice = false)
    {
        foreach ($tos as $to) {
            $message = \Swift_Message::newInstance()
                ->setSubject('[hexaa] Consent renewal')
                ->setFrom($this->fromAddress)
                ->setTo($to)
                ->setBody(
                    $this->renderView(
                        'HexaaApiBundle:Default:expiredConsent.txt.twig', array(
                            'url'    => $this->hexaaUiUrl . "/index.html#/profile/consents",
                            'notice' => $notice
                        )
                    ), "text/plain"
                );
            $this->mailer->send($message);
            $this->maillog->info("[ExpireConsentsHook] E-mail sent to " . var_export($to, true));
        }
    }
}