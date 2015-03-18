<?php
/**
 * Created by PhpStorm.
 * User: baloo
 * Date: 2/4/15
 * Time: 10:37 AM
 */

namespace Hexaa\ApiBundle\Hook\ExpireHook;


use Doctrine\ORM\EntityManager;
use Hexaa\StorageBundle\Entity\Principal;
use Monolog\Logger;

class ExpirePrincipalsHook extends ExpireHook {
    protected $principalExpirationLimit;
    protected $maillog;
    protected $hexaaUiUrl;
    protected $mailer;
    protected $fromAddress;

    public function __construct(EntityManager $entityManager, Logger $modlog, Logger $errorlog,
                                $principalExpirationLimit, Logger $maillog, \Swift_Mailer $mailer, $hexaaUiUrl, $fromAddress) {
        $this->principalExpirationLimit = $principalExpirationLimit;
        $this->maillog = $maillog;
        $this->hexaaUiUrl = $hexaaUiUrl;
        $this->mailer = $mailer;
        $this->fromAddress = $fromAddress;

        parent::__construct($entityManager, $modlog, $errorlog);
    }

    public function runHook()
    {

        if ($this->principalExpirationLimit > 0 ){
            $lastLoginAllowedDate1 = new \DateTime('now');
            $lastLoginAllowedDate2 = new \DateTime('now');
            date_timezone_set($lastLoginAllowedDate1, new \DateTimeZone("UTC"));

            if ($this->principalExpirationLimit == 1){
                $lastLoginAllowedDate1->modify("-".$this->principalExpirationLimit." day");
            } else {
                $lastLoginAllowedDate1->modify("-".$this->principalExpirationLimit." days");
            }
            $lastLoginAllowedDate2->modify("-". (1+$this->principalExpirationLimit) . " days");

            $principals = $this->em->createQueryBuilder()
                ->select("p")
                ->from('HexaaStorageBundle:Principal', 'p')
                ->leftJoin('p.token', 'token')
                ->where("token.updatedAt between :date1 AND :date2")
                ->setParameters(array(
                    ":date1" => $lastLoginAllowedDate1,
                    ":date2" => $lastLoginAllowedDate2,
                ))
                ->getQuery()
                ->getResult()
                ;

            $fedids = array();
            foreach($principals as $principal) {
                $fedids[] = $principal->getFedid();
                $this->em->remove($principal);
            }

            $this->modlog->info("[ExpirePrincipalsHook] Removed the following principals because they have not logged in for a long time: " . implode(" ", $fedids));

            $this->em->flush();

            // give a two week notice
            if ($this->principalExpirationLimit>=14) {
                $noticeDate1 = new \DateTime('now');
                $noticeDate2 = new \DateTime('now');
                date_timezone_set($noticeDate1, new \DateTimeZone("UTC"));
                if ($this->principalExpirationLimit == 14) {
                    $noticeDate1->modify("-1 day");
                } else {
                    $noticeDate1->modify("-" . $this->principalExpirationLimit-14 . " days");
                }
                $noticeDate2->modify("-" . $this->principalExpirationLimit-13 . " days");

                $noticePrincipals = $this->em->createQueryBuilder()
                    ->select("p")
                    ->from('HexaaStorageBundle:Principal', 'p')
                    ->leftJoin('p.token', 'token')
                    ->where("token.updatedAt between :date1 AND :date2")
                    ->setParameters(array(
                        ":date1" => $noticeDate1,
                        ":date2" => $noticeDate2,
                    ))
                    ->getQuery()
                    ->getResult()
                ;

                $tos = array();
                /* @var $principal Principal */
                foreach($noticePrincipals as $principal) {
                    if (!in_array($principal, $principals, true)) {
                        if ($principal->getDisplayName() != null) {
                            $tos[] = array($principal->getDisplayName() => $principal->getEmail());
                        } else {
                            $tos[] = $principal->getEmail();
                        }
                    }
                }
                $this->sendNoticeMails($tos);

            }
        }
    }

    private function sendNoticeMails($tos){
        foreach($tos as $to){
            $message = \Swift_Message::newInstance()
                ->setSubject('[hexaa] IMPORTANT notice about inactivity')
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