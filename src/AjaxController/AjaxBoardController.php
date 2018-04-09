<?php
namespace App\AjaxController;

use App\Entity\Board;
use App\Entity\BoardRole;
use App\Repository\BoardRepository;
use App\Repository\BoardRoleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AjaxBoardController extends Controller {

//  /**
//   * @Route("/testb", name="testb")
//   */
//  public function testb(Request $request) {
//      $name = 'Strašně dlouhý název pochybného projektu #2';
//      $color = 'ad1457';
//
//      /** @var BoardRepository $boardRepository */
//      $boardRepository = $this->getDoctrine()->getRepository(Board::class);
//      $link = $boardRepository->createNewBoard($name, $color, $this->getUser());
//
//      $arrData = ['name' => $name, 'color' => $color, 'link' => $link];
//      return new JsonResponse($arrData);
//  }

  /**
   * @Route("/ajax/boardNew", name="boardNew")
   */
  public function boardCreateNew(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $name = $request->request->get('name');
      $color = $request->request->get('color');

      /** @var BoardRepository $boardRepository */
      $boardRepository = $this->getDoctrine()->getRepository(Board::class);
      $link = $boardRepository->createNewBoard($name, $color, $this->getUser());

      $arrData = ['name' => $name, 'color' => $color, 'link' => $link];
      return new JsonResponse($arrData);
    }
  }

  /**
   * @Route("/ajax/getBoardFavorite", name="getBoardFavorite")
   */
  public function boardGetFavorite(Request $request) {
    if ($request->isXmlHttpRequest()) {
      /** @var BoardRoleRepository $boardRole */
      $boardRole = $this->getDoctrine()->getRepository(BoardRole::class);
      /** @var BoardRole[][] $boards */
      $boards = $boardRole->getUserBoardsAndFavorite($this->getUser());

      $render = $this->renderView('navbar-projects.html.twig',
        ["boards" => $boards['boards'], 'favorite' => $boards['favorite']]);

      $arrData = ['render' => $render];
      return new JsonResponse($arrData);
    }
  }

  /**
   * @Route("/ajax/boardFavorite", name="boardFavorite")
   */
  public function boardMakeFavorite(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $boardId = $request->request->get('board');

      /** @var BoardRepository $boardRepository */
      $boardRepository = $this->getDoctrine()->getRepository(Board::class);
      $board = $boardRepository->getBoardByLink($boardId, $this->getUser());

      /** @var BoardRoleRepository $boardRoleRepository */
      $boardRoleRepository = $this->getDoctrine()->getRepository(BoardRole::class);
      $role = $boardRoleRepository->getUserRights($this->getUser(), $board);
      if($role->isFavorite()) { // make not favorite
        $role->makeFavorite(false);
        $render = '';
      }
      else { // make it favorite
        $role->makeFavorite(true);
        /** @var BoardRepository $boardRepository */
        $boardRepository = $this->getDoctrine()->getRepository(Board::class);
        $active = $boardRepository->getAllActiveUsers($board, 4);
        $role->getBoard()->setActiveUsers($active);
        $render = $this->renderView('dashboard/board-card.html.twig', ["role" => $role, 'section' => 'fav']);
      }

      $entityManager = $this->getDoctrine()->getManager();
      $entityManager->persist($role);
      $entityManager->flush();

      $arrData = ['board' => $boardId, 'isFavorite' => $role->isFavorite(), 'render' => $render];
      return new JsonResponse($arrData);
    }
  }

}