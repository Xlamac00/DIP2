<?php

namespace App\Controller;

use App\Entity\Board;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class DashboardController extends Controller {

  /**
   * @Route("/", name="homepage")
   */
  public function index() {
    $board = $this->getDoctrine()->getRepository(Board::class)->getBoard();

    return $this->render('issue/issue.html.twig',
          ["board" => $board]);
  }
}