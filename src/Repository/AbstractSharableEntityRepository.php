<?php

namespace App\Repository;

use App\Entity\AbstractSharableEntity;
use App\Entity\Board;
use App\Entity\IssueShareHistory;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

abstract class AbstractSharableEntityRepository extends ServiceEntityRepository {
  protected $registry;
  protected $manager;

  /**
   * AbstractSharableEntityRepository constructor.
   *
   * @param RegistryInterface $registry - {inherit}
   * @param string         $class - class of concrete entity
   *
   */
  public function __construct(RegistryInterface $registry, $class) {
    parent::__construct($registry, $class);
    $this->registry = $registry;
    $this->manager = $registry->getManager();
  }

  /** Generates links for new Entity.
   *
   * @param AbstractSharableEntity $entity
   */
  public function generateShareLink($entity) {
    while(1) { // try generating random strings
      try{
        $entity->generateLinks();
        return;
      }
      catch (UniqueConstraintViolationException $e) { //random string was not unique! (probably never gonna happen)
        continue;
      }
    }
  }

  /** Checks if $user has admin rights to edit $entity.
   *
   * @param User $user - currently logged user
   * @param string $roleClass - ::class of __Role Entity
   * @param AbstractSharableEntity $entity - Entity to check this rights against (Board|Issue)
   *
   * @return bool - true if user is admin, otherwise false
   */
  public function checkAdminRights($user, $roleClass, $entity) {
    $roleRepository = $this->manager->getRepository($roleClass);
    try {
      $role = $roleRepository->checkUsersRights($entity->getId(), $user);
    }
    catch (AuthenticationException $e) { // user not found at all
      return false;
    }
    if($role === Board::ROLE_ADMIN)
      return true;
    return false;  // user does not have high enough rights
  }

  /** Changes sharing rights of the $entity to the new value.
   * If the current user has admin rights, changes the rights all users with the link have for this $entity.
   *
   * @param User $user - currently logged user
   * @param      $newRight - constant from Board entity with new rights to users with link to $entity
   * @param string $roleClass - ::class of __Role Entity
   * @param string $historyClass - ::class of __ShareHistory Entity
   * @param AbstractSharableEntity $entity - Entity to change (Board|Issue)
   *
   * @return bool|string $newRight - currently set rights for the $entity, or false if user has no rights
   */
  protected function changeShareRights($user, $newRight, $roleClass, $historyClass, $entity) {
    $roleRepository = $this->manager->getRepository($roleClass);
    try {
      $role = $roleRepository->checkUsersRights($entity->getId(), $user);
    }
    catch (AuthenticationException $e) { // user not found at all
      return false;
    }
    if($role != Board::ROLE_ADMIN)
      return false; // user does not have high enough rights

    $entity->setShareRights($newRight);
    $this->manager->persist($entity);
    $this->manager->flush();

    // get all users that gained rights via this link and give them new rights for $entity
    $historyId = $historyClass === IssueShareHistory::class ? 'boardHistory' : 'id';
    $qb = $this->createQueryBuilder('e')
      ->select('DISTINCT (h.'.$historyId.') as id')
      ->join($historyClass, 'h')
      ->andWhere('h.entity = e.id')
      ->andWhere('h.oldRole IS NULL')
      ->andWhere('e.id = :id')
      ->setParameter('id', $entity->getId())
      ->getQuery();
    $history = $qb->execute();
    foreach($history as $item) {
      $role = $roleRepository->findOneBy(["boardHistory" => $item['id']]);
      if($role->getRights() !== Board::ROLE_ADMIN) { // dont change admins rights
        $role->setRole($newRight);
        $this->manager->persist($role);
      }
    }

    $this->manager->flush();
    return $newRight;
  }

  /** Changes if the Board share link is active or not.
   * If the link is active (isAllowed is true), any user with its link will automatically gain
   * sharing rights associated with this board for all its issue.
   *
   * @param User $user - currently logged user
   * @param boolean $isAllowed - true to allow sharing via link, false to disable it
   * @param string $roleClass - ::class of __Role Entity
   * @param string $historyClass - ::class of __ShareHistory Entity
   * @param AbstractSharableEntity $entity - Entity to change
   *
   * @return boolean - the current state of $entity sharing
   */
  protected function changeShareEnabled($user, $isAllowed, $roleClass, $historyClass, $entity) {
    $roleRepository = $this->manager->getRepository($roleClass);
    try {
      $role = $roleRepository->checkUsersRights($entity->getId(), $user);
    }
    catch (AuthenticationException $e) { // user not found at all
      return !$isAllowed;
    }
    if($role != Board::ROLE_ADMIN)
      return !$isAllowed; // dont allow the change, return previous value

    $entity->setShareEnabled($isAllowed);
    $this->manager->persist($entity);

    // get all users that gained access via the link and were not manually edited (oldRole is null)
    $qb = $this->createQueryBuilder('e')
      ->select('DISTINCT h.id')
      ->join($historyClass, 'h')
      ->andWhere('h.entity = e.id')
      ->andWhere('h.oldRole IS NULL')
      ->andWhere('e.id = :id')
      ->setParameter('id', $entity->getId())
      ->getQuery();
    $history = $qb->execute();
    foreach($history as $item) {
      $role = $roleRepository->findOneBy(["boardHistory" => $item['id']]);
      $role->setShareEnabled($isAllowed);
      $this->manager->persist($role);
    }

    $this->manager->flush();
    return $isAllowed;
  }
}