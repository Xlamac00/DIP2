<?php
namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class UserController extends Controller {
  /**
   * @Route("/login", name="login")
   */
  public function login() {
  }

  /**
   * @Route("/login-fail", name="login-fail")
   */
  public function loginFail() {
  }


  /**
   * @Route("/login/google", name="google")
   */
  public function loginGoogle() {
  }

  /**
   * @Route("/ajax/userNameChange", name="user_name_change")
   */
  public function changeName(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $name = $request->request->get('name');
      $googleId = $request->cookies->get('googleId');
      $userId = $request->cookies->get('clientId');

      $userRepository = $this->getDoctrine()->getRepository(User::class);
      if(sizeof($googleId) > 0)
        $userRepository->loadUserByGoogleId($googleId);
      else
        $userRepository->loadUserByUsername($userId);
      $userRepository->updateUsername($name);

      $arrData = ['name' => $name];
      return new JsonResponse($arrData);
    }
  }

}