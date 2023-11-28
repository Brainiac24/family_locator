<?php

namespace App\Entity;

use App\Repository\UserRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @ORM\Table(name="`user`")
 * @ORM\HasLifecycleCallbacks()
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     */
    private $username;

    /**
     * @ORM\Column(type="string", length=180, unique=false)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=180, unique=false)
     */
    private $avatar;

    /**
     * @ORM\Column(type="string", length=20, unique=true)
     */
    private $phone;

    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @ORM\Column(type="string", nullable = true)
     */
    private $password;

    /**
     * @ORM\Column(type="text", nullable = true)
     */
    private $token;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime", nullable = true)
     */
    protected $updated_at;

    /**
     * @ORM\OneToMany(targetEntity=UserCode::class, mappedBy="user")
     */
    private $userCode;

    /**
     * @ORM\OneToMany(targetEntity=Friend::class, mappedBy="ownerUser")
     */
    private $ownerUserFriend;

    /**
     * @ORM\OneToMany(targetEntity=Friend::class, mappedBy="friendUser")
     */
    private $friendUserFriend;

    /**
     * @ORM\OneToMany(targetEntity=Message::class, mappedBy="sender")
     */
    private $message;

    public function __construct()
    {
        $this->userCode = new ArrayCollection();
        $this->ownerUserFriend = new ArrayCollection();
        $this->friendUserFriend = new ArrayCollection();
        $this->message = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getUsername(): string
    {
        return (string) $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): void
    {
        $this->phone = $phone;
    }

    public function getUpdatedAt(): ?DateTime
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(DateTime $updated_at): void
    {
        $this->updated_at = $updated_at;
    }

    /**
     * Gets triggered every time on update.
     *
     * @ORM\PreUpdate
     */
    public function onPreUpdate()
    {
        $this->updated_at = new DateTime('now');
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    /**
     * @return mixed
     */
    public function getAvatar(): string
    {
        return $this->avatar;
    }

    /**
     * @param mixed $avatar
     */
    public function setAvatar($avatar): void
    {
        $this->avatar = $avatar;
    }

    /**
     * @return Collection|UserCode[]
     */
    public function getUserCode(): Collection
    {
        return $this->userCode;
    }

    public function addUserCode(UserCode $userCode): self
    {
        if (!$this->userCode->contains($userCode)) {
            $this->userCode[] = $userCode;
            $userCode->setUser($this);
        }

        return $this;
    }

    public function removeUserCode(UserCode $userCode): self
    {
        if ($this->userCode->removeElement($userCode)) {
            // set the owning side to null (unless already changed)
            if ($userCode->getUser() === $this) {
                $userCode->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Friend[]
     */
    public function getOwnerUserFriend(): Collection
    {
        return $this->ownerUserFriend;
    }

    public function addOwnerUserFriend(Friend $ownerUserFriend): self
    {
        if (!$this->ownerUserFriend->contains($ownerUserFriend)) {
            $this->ownerUserFriend[] = $ownerUserFriend;
            $ownerUserFriend->setOwnerUser($this);
        }

        return $this;
    }

    public function removeOwnerUserFriend(Friend $ownerUserFriend): self
    {
        if ($this->ownerUserFriend->removeElement($ownerUserFriend)) {
            // set the owning side to null (unless already changed)
            if ($ownerUserFriend->getOwnerUser() === $this) {
                $ownerUserFriend->setOwnerUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Friend[]
     */
    public function getFriendUserFriend(): Collection
    {
        return $this->friendUserFriend;
    }

    public function addFriendUserFriend(Friend $friendUserFriend): self
    {
        if (!$this->friendUserFriend->contains($friendUserFriend)) {
            $this->friendUserFriend[] = $friendUserFriend;
            $friendUserFriend->setFriendUser($this);
        }

        return $this;
    }

    public function removeFriendUserFriend(Friend $friendUserFriend): self
    {
        if ($this->friendUserFriend->removeElement($friendUserFriend)) {
            // set the owning side to null (unless already changed)
            if ($friendUserFriend->getFriendUser() === $this) {
                $friendUserFriend->setFriendUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Message[]
     */
    public function getMessage(): Collection
    {
        return $this->message;
    }

    public function addMessage(Message $message): self
    {
        if (!$this->message->contains($message)) {
            $this->message[] = $message;
            $message->setSender($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): self
    {
        if ($this->message->removeElement($message)) {
            // set the owning side to null (unless already changed)
            if ($message->getSender() === $this) {
                $message->setSender(null);
            }
        }

        return $this;
    }
}
