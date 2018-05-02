<?php

namespace App\Repository;

use App\Entity\Tips;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class TipsRepository extends ServiceEntityRepository {
  private $manager;
  private $registry;

  public function __construct(RegistryInterface $registry) {
    parent::__construct($registry, Tips::class);
    $this->manager = $registry->getManager();
    $this->registry = $registry;
  }

  public function getNewTipsForPage($pageName, $userLink) {
    $tips = $this->findBy(array('screen' => $pageName, 'shown' => NULL, 'user_link' => $userLink));
//    $tips = $this->findBy(array('screen' => $pageName, 'user_link' => $userLink));
    $this->hideTips($tips);
    return $tips;
  }

  /**
   * @param Tips[] $tips
   */
  private function hideTips($tips) {
    foreach ($tips as $tip) {
      $tip->setShown();
    }
    $this->manager->flush();
  }

  public function hideAllTips($userLink) {

  }
}
