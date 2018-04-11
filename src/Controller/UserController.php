<?php
namespace App\Controller;

use App\Entity\Board;
use App\Repository\BoardRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

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
   * @Route("/u/{link}/{name}", name="user_share")
   */
  public function showBoard($link, $name) {
    /** @var BoardRepository $boardRepository */
    $boardRepository = $this->getDoctrine()->getRepository(Board::class);
    $board = $boardRepository->getBoardByLink($link, $this->getUser());
    if($board !== null) { // Its Board
      return $this->redirectToRoute('board', array('link' => $link, 'name' => $name));
    }
    else { // Its Issue
      return $this->redirectToRoute('issue', array('link' => $link, 'name' => $name));
    }
  }
}