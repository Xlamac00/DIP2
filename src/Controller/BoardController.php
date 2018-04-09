<?php

namespace App\Controller;

use App\Entity\Board;
use App\Entity\BoardRole;
use App\Repository\BoardRepository;
use App\Repository\BoardRoleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class BoardController extends Controller {
  /**
   * @Route("/b/{link}/{name}", name="board")
   */
  public function showBoard($link, $name) {
    /** @var BoardRepository $boardRepository */
    $boardRepository = $this->getDoctrine()->getRepository(Board::class);
    $board  = $boardRepository->getBoardByLink($link, $this->getUser());

    return $this->render('board/board-overview.html.twig', ["board" => $board]);
  }
}