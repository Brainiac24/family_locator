<?php

namespace App\Entity;

use App\Repository\FriendRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass=FriendRepository::class)
 */
class Friend
{
    use TimestampableEntity;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="ownerUserFriend")
     * @ORM\JoinColumn(nullable=false)
     */
    private $ownerUser;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="friendUserFriend")
     * @ORM\JoinColumn(nullable=false)
     */
    private $friendUser;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $approveStatus;

    /**
     * @ORM\OneToMany(targetEntity=Message::class, mappedBy="conversation")
     */
    private $messages;

    /**
     * @ORM\ManyToOne(targetEntity=Message::class)
     */
    private $lastMessage;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isBlockedByOwner;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isBlockedByFriend;

    public function __construct()
    {
        $this->messages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOwnerUser(): ?User
    {
        return $this->ownerUser;
    }

    public function setOwnerUser(?User $ownerUser): self
    {
        $this->ownerUser = $ownerUser;

        return $this;
    }

    public function getFriendUser(): ?User
    {
        return $this->friendUser;
    }

    public function setFriendUser(?User $friendUser): self
    {
        $this->friendUser = $friendUser;

        return $this;
    }

    public function getApproveStatus(): ?string
    {
        return $this->approveStatus;
    }

    public function setApproveStatus(string $approveStatus): self
    {
        $this->approveStatus = $approveStatus;

        return $this;
    }

    /**
     * @return Collection|Message[]
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): self
    {
        if (!$this->messages->contains($message)) {
            $this->messages[] = $message;
            $message->setConversation($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): self
    {
        if ($this->messages->removeElement($message)) {
            // set the owning side to null (unless already changed)
            if ($message->getConversation() === $this) {
                $message->setConversation(null);
            }
        }

        return $this;
    }

    public function getLastMessage(): ?Message
    {
        return $this->lastMessage;
    }

    public function setLastMessage(?Message $lastMessage): self
    {
        $this->lastMessage = $lastMessage;

        return $this;
    }

    public function getIsBlockedByOwner(): ?bool
    {
        return $this->isBlockedByOwner;
    }

    public function setIsBlockedByOwner(?bool $isBlockedByOwner): self
    {
        $this->isBlockedByOwner = $isBlockedByOwner;

        return $this;
    }

    public function getIsBlockedByFriend(): ?bool
    {
        return $this->isBlockedByFriend;
    }

    public function setIsBlockedByFriend(?bool $isBlockedByFriend): self
    {
        $this->isBlockedByFriend = $isBlockedByFriend;

        return $this;
    }
}
