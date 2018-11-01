<?php

namespace MBH\SitederencontreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mgilet\NotificationBundle\Annotation\Notifiable;
use Mgilet\NotificationBundle\NotifiableInterface;

/**
 * Messages
 *
 * @ORM\Table(name="messages")
 * @ORM\Entity(repositoryClass="MBH\SitederencontreBundle\Repository\MessagesRepository")
 *@Notifiable(name="messages")
 */
class Messages implements NotifiableInterface
{
    /**
    * @ORM\ManyToOne(targetEntity="MBH\SitederencontreBundle\Entity\Members")
    * @ORM\JoinColumn(nullable=false)
    */
    private $members;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text")
     */
    private $content;


    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set content.
     *
     * @param string $content
     *
     * @return Messages
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set members.
     *
     * @param \MBH\SitederencontreBundle\Entity\Members $members
     *
     * @return Messages
     */
    public function setMembers(\MBH\SitederencontreBundle\Entity\Members $members)
    {
        $this->members = $members;

        return $this;
    }

    /**
     * Get members.
     *
     * @return \MBH\SitederencontreBundle\Entity\Members
     */
    public function getMembers()
    {
        return $this->members;
    }
}
