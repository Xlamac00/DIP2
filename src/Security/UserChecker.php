<?php
namespace App\Security;

use App\Exception\NotSufficientRightsException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface {
  public function checkPreAuth(UserInterface $user) {
//    die("kocicka");
//        throw new NotSufficientRightsException('...');
  }

  public function checkPostAuth(UserInterface $user) {
  }
}
