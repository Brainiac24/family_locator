<?php

namespace App\Repository;

use App\Entity\Friend;
use App\Entity\Message;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Message|null find($id, $lockMode = null, $lockVersion = null)
 * @method Message|null findOneBy(array $criteria, array $orderBy = null)
 * @method Message[]    findAll()
 * @method Message[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MessageRepository extends ServiceEntityRepository
{
    public EntityManager $entityManager;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
        $this->entityManager = $this->getEntityManager();
    }

    public function addMessageByPhone(string $phoneNumber, Friend $friend, array $data)
    {
        $message = new Message();

        if ($friend->getOwnerUser()->getPhone() == $phoneNumber) {
            $message->setSender($friend->getOwnerUser());
        } elseif ($friend->getFriendUser()->getPhone() == $phoneNumber) {
            $message->setSender($friend->getFriendUser());
        }

        $message->setConversation($friend);
        $message->setContent($data['content']);
        $message->setType($data['type']);

        $this->entityManager->persist($message);

        $friend->setLastMessage($message);
        $this->entityManager->persist($friend);

        $this->entityManager->flush();
    }

    public function getMessageHistoryByUserPhone(int $userPhone, int $friendPhone, int $page): ?array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.conversation', 'mc')
            ->leftJoin('mc.friendUser', 'mcf')
            ->leftJoin('mc.ownerUser', 'mco')
            ->leftJoin('m.sender', 'ms')
            ->andWhere('(mcf.phone = :userPhone OR mco.phone = :userPhone)')
            ->setParameter('userPhone', $friendPhone)
            ->andWhere('mc.approveStatus = :approveStatus')
            ->setParameter('approveStatus', FriendRepository::APPROVE_STATUS_APPROVED)
            ->andWhere('ms.phone = :senderPhone')
            ->setParameter('senderPhone', $userPhone)
            ->addSelect(['mc', 'mcf', 'mco', 'ms'])
            ->addOrderBy('m.id', 'DESC')
            ->setFirstResult($page * 10 - 10)
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }
}
