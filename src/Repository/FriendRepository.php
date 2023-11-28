<?php

namespace App\Repository;

use App\Entity\Friend;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;

use function PHPUnit\Framework\isNull;

/**
 * @method Friend|null find($id, $lockMode = null, $lockVersion = null)
 * @method Friend|null findOneBy(array $criteria, array $orderBy = null)
 * @method Friend[]    findAll()
 * @method Friend[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FriendRepository extends ServiceEntityRepository
{
    public const APPROVE_STATUS_APPROVED = 'approved';
    public const APPROVE_STATUS_PENDING = 'pending';
    public const APPROVE_STATUS_BLOCKED = 'blocked';

    private UserRepository $userRepository;
    private EntityManager $entityManager;

    public function __construct(ManagerRegistry $registry, UserRepository $userRepository)
    {
        parent::__construct($registry, Friend::class);
        $this->entityManager = $this->getEntityManager();
        $this->userRepository = $userRepository;
    }

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOneByUserPhone($ownerPhone, $friendPhone, $approveStatus = null): ?Friend
    {
        $q = $this->createQueryBuilder('f')
            ->leftJoin('f.friendUser', 'fu')
            ->leftJoin('f.ownerUser', 'ou')
            ->andWhere(
                '(fu.phone = :ownerPhone 
                AND ou.phone = :friendPhone) 
                OR (ou.phone = :ownerPhone 
                AND fu.phone = :friendPhone)'
            )
            ->setParameter('ownerPhone', $ownerPhone)
            ->setParameter('friendPhone', $friendPhone);

        if (null !== $approveStatus) {
            $q->andWhere('f.approveStatus = :approveStatus')
                ->setParameter('approveStatus', $approveStatus);
        }

        return $q->addSelect(['fu', 'ou'])
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByOwnUserPhone($ownerPhone, $friendPhone): ?Friend
    {
        $q = $this->createQueryBuilder('f')
            ->leftJoin('f.friendUser', 'fu')
            ->leftJoin('f.ownerUser', 'ou')
            ->andWhere('(fu.phone = :ownerPhone 
                AND ou.phone = :friendPhone) 
                OR ((ou.phone = :ownerPhone 
                AND fu.phone = :friendPhone) 
                AND (f.approveStatus = :approveStatus) 
                AND (f.isBlockedByFriend = 0))')
            ->setParameter('ownerPhone', $ownerPhone)
            ->setParameter('friendPhone', $friendPhone)
            ->setParameter('approveStatus', self::APPROVE_STATUS_BLOCKED);

        return $q->addSelect(['fu', 'ou'])
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByOwnUserPhoneAndApproveStatus($ownerPhone, $friendPhone, $approveStatus = null): ?Friend
    {
        $q = $this->createQueryBuilder('f')
            ->leftJoin('f.friendUser', 'fu')
            ->leftJoin('f.ownerUser', 'ou')
            ->andWhere('(fu.phone = :ownerPhone AND ou.phone = :friendPhone)')
            ->setParameter('ownerPhone', $ownerPhone)
            ->setParameter('friendPhone', $friendPhone);

        if (null !== $approveStatus) {
            $q->andWhere('f.approveStatus = :approveStatus')
                ->setParameter('approveStatus', $approveStatus);
        }

        return $q->addSelect(['fu', 'ou'])
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function addFriendWithStatusPendingByPhoneNumbers($ownerPhone, $friendPhone)
    {
        $ownerUser = $this->userRepository->findOneBy([
            'phone' => $ownerPhone,
        ]);

        $friendUser = $this->userRepository->findOneBy([
            'phone' => $friendPhone,
        ]);

        if (empty($ownerUser) || empty($friendUser)) {
            return;
        }

        $friend = new Friend();

        $friend->setOwnerUser($ownerUser);
        $friend->setFriendUser($friendUser);
        $friend->setApproveStatus(self::APPROVE_STATUS_PENDING);

        $this->entityManager->persist($friend);
        $this->entityManager->flush();
    }

    public function setApprovedFriendByPhoneNumber($friend, $phone)
    {
        if (empty($friend)) {
            return false;
        }
        /** @var Friend $friend */
        if ($friend->getOwnerUser()->getPhone() == $phone) {
            $friend->setIsBlockedByOwner(false);
        } elseif ($friend->getFriendUser()->getPhone() == $phone) {
            $friend->setIsBlockedByFriend(false);
        }

        if (
            (is_null($friend->getIsBlockedByOwner()) || !$friend->getIsBlockedByOwner()) &&
            (is_null($friend->getIsBlockedByFriend()) || !$friend->getIsBlockedByFriend())
        ) {
            $friend->setApproveStatus(FriendRepository::APPROVE_STATUS_APPROVED);
        }

        dump(is_null($friend->getIsBlockedByOwner()));

        $this->entityManager->persist($friend);
        $this->entityManager->flush();

        return true;
    }

    public function setBlockedFriendByPhoneNumber($friend, $phone)
    {
        /** @var Friend $friend */
        if ($friend->getOwnerUser()->getPhone() == $phone) {
            $friend->setIsBlockedByOwner(true);
        } elseif ($friend->getFriendUser()->getPhone() == $phone) {
            $friend->setIsBlockedByFriend(true);
        }

        $friend->setApproveStatus(FriendRepository::APPROVE_STATUS_BLOCKED);

        $this->entityManager->persist($friend);
        $this->entityManager->flush();
    }

    public function findFriendsWithLastMessageByUserPhone($userPhone): ?array
    {
        return $this->createQueryBuilder('f')
            ->leftJoin('f.friendUser', 'fu')
            ->leftJoin('f.ownerUser', 'ou')
            ->andWhere('(fu.phone = :userPhone OR ou.phone = :userPhone)')
            ->setParameter('userPhone', $userPhone)
            ->andWhere('f.approveStatus = :approveStatus')
            ->setParameter('approveStatus', self::APPROVE_STATUS_APPROVED)
            ->andWhere('f.lastMessage IS NOT NULL')
            ->addSelect(['fu', 'ou'])
            ->getQuery()
            ->getResult();
    }

    public function getFriends(string $userPhone, int $page): ?array
    {
        return $this->createQueryBuilder('f')
            ->leftJoin('f.friendUser', 'fu')
            ->leftJoin('f.ownerUser', 'ou')
            ->andWhere('(fu.phone = :userPhone OR ou.phone = :userPhone)')
            ->setParameter('userPhone', $userPhone)
            ->addSelect(['fu', 'ou'])
            ->addOrderBy('f.id', 'DESC')
            ->setFirstResult($page * 10 - 10)
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    public function getAllFriends(string $userPhone): ?array
    {
        return $this->createQueryBuilder('f')
            ->leftJoin('f.friendUser', 'fu')
            ->leftJoin('f.ownerUser', 'ou')
            ->andWhere('(fu.phone = :userPhone OR ou.phone = :userPhone)')
            ->setParameter('userPhone', $userPhone)
            ->addSelect(['fu', 'ou'])
            ->addOrderBy('f.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
