<?php

namespace App\Repository;

use App\Entity\Tips;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

class UserRepository extends ServiceEntityRepository  implements UserLoaderInterface {
  private $registry;
  private $manager;
  /** @var User */
  private $user;

  public function __construct(RegistryInterface $registry) {
    parent::__construct($registry, User::class);
    $this->registry = $registry;
    $this->manager = $registry->getManager();
  }

  /** Loads user with unique user link.
   * @param $userLink - unique 20 characters user link
   *
   * @return User
   * */
  public function loadUserByUsername($userLink) {
    if(!isset($this->user) || $this->user->getUniqueLink() != $userLink)
      $this->user = $this->findOneBy(["link" => $userLink, "googleId" => null]);
    if($this->user instanceof User)
      return $this->user;
    else
      throw new UsernameNotFoundException(
        sprintf('Username "%s" does not exist.', $userLink)
      );
  }

  /** @return User $user */
  public function loadUserByGoogleId($googleId) {
    if(!isset($this->user) || $this->user->getGoogleId() != $googleId)
      $this->user = $this->findOneBy(["googleId" => $googleId]);
    if($this->user instanceof User)
      return $this->user;
    else
      throw new UnsupportedUserException(
        sprintf('User with google id "%s" does not exist.', $googleId)
      );
  }

  /** Returns User based on part of his username and part of his email.
   * Used for example when inviting user to app by email.
   *
   * @param $partOfName - part of his username
   * @param $partOfEmail - first part of email (part before @)
   * @return User $user
   */
  public function findUserByNameAndEmail($partOfName, $partOfEmail) {
    $qb = $this->createQueryBuilder('u')
      ->andWhere('u.isActive = 1')
      ->andWhere('u.email IS NOT NULL or u.anonymousEmail IS NOT NULL')
      ->andWhere('u.email LIKE :mail or u.anonymousEmail LIKE :mail')
      ->andWhere('u.username LIKE :name')
      ->setParameter('name', '%'.$partOfName.'%')
      ->setParameter('mail', $partOfEmail.'%')
      ->getQuery();
    $users = $qb->execute();
    if(sizeof($users) != 1) return null;
    else return $users[0];
  }

  /** Returns User based only on his (unique) email.
   *
   * @param $email
   * @return User
   */
  public function findUserByEmail($email) {
    /** @var User $user */
    $user = $this->findOneBy(["email" => $email]);
    return $user;
  }

  /** Returns user which has exactly same name as $username.
   *
   * @param string $username
   * @return User - with exactly same name, no more, no less
   */
  public function findUserByName($username) {
    /** @var User $user */
    $user = $this->findOneBy(["name" => $username]);
    return $user;
  }

  /** Loads user either by Google ID or my UserLink.
   *
   * @param string $anyId - either 21+ numeric Google Id, or my 20 char alphanumeric id
   * @return User|null
   */
  public function loadUser($anyId) {
    if(is_numeric($anyId)) { // its number - id
      if(strlen($anyId > 10))
        return $this->loadUserByGoogleId($anyId);
      else
        return $this->loadUserById($anyId);
    }
    else
      return $this->loadUserByUsername($anyId);
  }

  public function loadUserById($userId) {
    if(!isset($this->user))
      $this->user = $this->find($userId);
    return $this->user;
  }

  public function updateUsername($newName) {
    $this->user->setUsername($newName);
    $this->manager->persist($this->user);
    $this->manager->flush();
  }

  /** Changes users language
   * @param User $user
   * @param string $newLanguage - 2 char string
   */
  public function changeLanguage($user, $newLanguage) {
    $user->setLanguage($newLanguage);
    $this->manager->persist($user);
    $this->manager->flush();
  }

  /** Returns all possible users that fit the substring.
   * Used for autocomplete where finding new users to share with.
   *
   * @param string $substring - part of users name or email address
   *
   * @return array - array of possile usernames (with part of email)
   */
  public function findUsersBySubstring($substring) {
    $qb = $this->createQueryBuilder('u')
      ->andWhere('u.isActive = 1')
      ->andWhere('u.email IS NOT NULL OR u.anonymousEmail  IS NOT NULL')
      ->andWhere('u.email LIKE :string OR u.username LIKE :string OR u.anonymousEmail LIKE :string')
      ->setParameter('string', '%'.$substring.'%')
      ->getQuery();
    $users = $qb->execute();
    $result = array();
    /** @var User $user */
    foreach($users as $user) {
      if($user->getEmail() === null)
        $mail = explode('@', $user->getAnonymousEmail());
      else
        $mail = explode('@', $user->getEmail());
      $email = $mail[0]."@ ... ";
      $result[] = strtolower($user->getUsername())." (".$email.")";
    }
    return $result;
  }

  public function createNewAnonymousUser($userLink) {
    $user = new User();
    $user->setUsername('Anonymous user');
    $user->setLink($userLink);
    $user->setColor($this->getRandomColor());
    $user->setLanguage('en');
    $this->manager->persist($user);
    $this->manager->flush();
    $this->user = $user;

    $this->createAnonymousTips($user);

    return $user;
  }

  /** Initiates tips for given user - only the ones for anonymous users
   * @param User $user
   */
  public function createAnonymousTips($user) {
    $tipNewIssue = new Tips();
    $tipNewIssue->setUser($user->getAnonymousLink());
    $tipNewIssue->setScreen('board');
    $tipNewIssue->setName('createNewIssue');
    $this->manager->persist($tipNewIssue);

    $tipNewTask = new Tips();
    $tipNewTask->setUser($user->getAnonymousLink());
    $tipNewTask->setScreen('issue');
    $tipNewTask->setName('createNewTask');
    $this->manager->persist($tipNewTask);

    $tipEditGauge = new Tips();
    $tipEditGauge->setUser($user->getAnonymousLink());
    $tipEditGauge->setScreen('issue');
    $tipEditGauge->setName('editGauge');
    $this->manager->persist($tipEditGauge);

    $tipMakeComment = new Tips();
    $tipMakeComment->setUser($user->getAnonymousLink());
    $tipMakeComment->setScreen('issue');
    $tipMakeComment->setName('makeComment');
    $this->manager->persist($tipMakeComment);

    $this->manager->flush();
  }

  /** Initiates tips for given user - the rest
   * @param User $user
   */
  public function createLoggedTips($user) {
    $tipNewBoard = new Tips();
    $tipNewBoard->setUser($user->getAnonymousLink());
    $tipNewBoard->setScreen('dashboard');
    $tipNewBoard->setName('createNewBoard');
    $this->manager->persist($tipNewBoard);

    $tipDeadlines = new Tips();
    $tipDeadlines->setUser($user->getAnonymousLink());
    $tipDeadlines->setScreen('issue');
    $tipDeadlines->setName('deadlines');
    $this->manager->persist($tipDeadlines);

    $tipReminders = new Tips();
    $tipReminders->setUser($user->getAnonymousLink());
    $tipReminders->setScreen('issue');
    $tipReminders->setName('reminders');
    $this->manager->persist($tipReminders);

    $tipSharing = new Tips();
    $tipSharing->setUser($user->getAnonymousLink());
    $tipSharing->setScreen('board');
    $tipSharing->setName('sharing');
    $this->manager->persist($tipSharing);

    $this->manager->flush();
  }

  /** Creates user account from Google Account data.
   * @param $userLink - my own 23 characters long unique id
   * @param $googleId - google id, usually 21 characters
   * @param $name - google full name
   * @param $email - google email
   * @param $image - link to google image
   *
   * @return User
   */
  public function createNewGoogleUser($userLink, $googleId, $name, $email, $image) {
    $user = new User();
    $user->setUsername($name);
    $user->setLink($userLink);
    $user->setGoogleId($googleId);
    $user->setColor($this->getRandomColor());
    $user->setEmail($email);
    $user->setImageLink($image);
    $user->setLanguage('en');
    $this->manager->persist($user);
    $this->manager->flush();
    $this->user = $user;

    $this->createLoggedTips($user);

    return $user;
  }

  private function getRandomColor() {
    //    $colors = ['d32f2f', 'c2185b', '7b1fa2', '512da8', '303f9f', '1976d2',
    //      '0097a7',  '00796b', '388e3c', '689f38', 'afb42b', 'fbc02d', '5d4037'];
    $colors = ['ad1457', 'ab47bc', '7e57c2', '5c6bc0', '42a5f5',
      '00bcd4', '4db6ac',  '66bb6a', '9ccc65', 'c0ca33'];
    $i = rand(0, sizeof($colors)-1);
    return '#'.$colors[$i];
  }


}
