<?php

namespace MBH\SitederencontreBundle\Service;

use Doctrine\ORM\EntityManager;
use FOS\MessageBundle\Provider\ProviderInterface;
use MBH\SitederencontreBundle\Entity\Members;
use MBH\SitederencontreBundle\Entity\Message;
use MBH\SitederencontreBundle\Entity\Thread;

class NotificationService
{
    private $em;
    private $messageProvider;

    public function __construct(EntityManager $em, ProviderInterface $messageProvider)
    {
        $this->em = $em;
        $this->messageProvider = $messageProvider;
    }

    public function hasNotifications(Members $user)
    {

        $threads = $this->messageProvider->getInboxThreads();
        $messages = [];
        $userIds = [];
        /** @var Thread $thread */
        foreach ($threads as $thread) {
            /** @var Message $message */
            foreach ($thread->getMessages() as $message){
                if($message->getSender()->getId() !== $user->getId() && !isset($userIds[$message->getSender()->getId()])){
                    /** @var Members $member */
                    $member = $this->em->getRepository(Members::class)->find($message->getSender()->getId());
                    array_push($messages, array(
                        'body' => $message->getBody(),
                        'profileImage' => $member->getProfileImage(),
                        'pseudo' => $member->getPseudo()
                    ));
                    $userIds[$member->getId()] = array();
                }
            }
        }
        return $messages;
    }
}
