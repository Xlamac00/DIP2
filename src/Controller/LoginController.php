<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class LoginController extends Controller {
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

}