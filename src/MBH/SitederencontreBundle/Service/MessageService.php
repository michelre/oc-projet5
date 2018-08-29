<?php

namespace MBH\SitederencontreBundle\Service;


use Doctrine\ORM\EntityManager;

class MessageService
{
    private $em;
    private $serializer;

    public function __construct(EntityManager $em, $serializer)
    {
        $this->em = $em;
        $this->serializer = $serializer;
    }

    public function getThreadIdBySenderAndRecepient($senderId, $recepientId)
    {
        $query = $this->em
            ->createQuery('SELECT t
FROM MBHSitederencontreBundle:Thread t, MBHSitederencontreBundle:ThreadMetadata tm, MBHSitederencontreBundle:Message m, MBHSitederencontreBundle:MessageMetadata mm
WHERE m.thread = tm.thread
AND tm.thread = t.id
AND m.id = mm.message
AND tm.participant=?1 and mm.participant=?2')
            ->setParameter('1', $senderId)
            ->setParameter('2', $recepientId);

        $res = $query->getArrayResult() ? $query->getArrayResult()[0] : null;
        if (isset($res)) {
            return $res['id'];
        }
        return null;
    }

}