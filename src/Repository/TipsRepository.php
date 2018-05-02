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
    return $this->findBy(array('screen' => $pageName, 'shown' => NULL, 'user_link' => $userLink));
//    return $this->findBy(array('screen' => $pageName, 'user_link' => $userLink));
  }

  public function tipExists($tip, $userLink) {
    $tip = $this->findOneBy(array('user_link' => $userLink, 'name' => $tip));
    return $tip !== null;
  }

  /**
   * @param Tips $tip
   * @param string $userLink - unique user_link (same for anonymous and logged)
   */
  public function hideOneTip($tip, $userLink) {
    $tip = $this->findOneBy(array('user_link' => $userLink, 'name' => $tip));
    $tip->setShown();
    $this->manager->flush();
  }

  public function hideAllTips($userLink) {
    /** @var Tips[] $tips */
    $tips = $this->findBy(array('user_link' => $userLink, 'shown' => NULL));
    foreach($tips as $tip) {
      if($tip->getName() !== "editGauge")
        $tip->setShown();
    }
    $this->manager->flush();
  }
}
