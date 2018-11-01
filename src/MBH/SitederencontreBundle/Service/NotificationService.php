<?php

namespace MBH\SitederencontreBundle\Service;

use Doctrine\ORM\EntityManager;

class NotificationService
{
    private $em;
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function hasNotifications($user)
    {
        $message = $this->em->getRepository('MBHSitederencontreBundle:Message')->findBy(array('senderId' => $user->getId()));
    var_dump($message);
    }
}
