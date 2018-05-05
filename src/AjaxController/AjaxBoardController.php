<?php
namespace App\AjaxController;

use App\Entity\Board;
use App\Entity\BoardRole;
use App\Entity\Deadline;
use App\Entity\Gauge;
use App\Entity\Issue;
use App\Entity\IssueRole;
use App\Entity\Notification;
use App\Entity\Reminder;
use App\Repository\BoardRepository;
use App\Repository\BoardRoleRepository;
use App\Repository\DeadlineRepository;
use App\Repository\GaugeRepository;
use App\Repository\IssueRoleRepository;
use App\Repository\ReminderRepository;
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
      $oldBoard = $request->request->get('oldBoard');

      /** @var BoardRepository $boardRepository */
      $boardRepository = $this->getDoctrine()->getRepository(Board::class);
      if($oldBoard === null || $oldBoard === '') {
        $link = $boardRepository->createNewBoard($name, $color, $this->getUser());

        $arrData = ['name' => $name, 'color' => $color, 'link' => $link];
      }
      else { // just change boards name and color
        $board = $boardRepository->getBoardByLink($oldBoard, $this->getUser());
        $board->setColor($color);
        $board->setName($name);
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($board);
        $entityManager->flush();
        $arrData = ['name' => $name, 'color' => $color];
      }

      return new JsonResponse($arrData);
    }
  }

  /**
   * @Route("/ajax/boardDelete", name="boardDelete")
   */
  public function boardDelete(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $boardId = $request->request->get('board');

      /** @var BoardRepository $boardRepository */
      $boardRepository = $this->getDoctrine()->getRepository(Board::class);
      $board = $boardRepository->getBoard($boardId);

      $entityManager = $this->getDoctrine()->getManager();
      /** @var BoardRoleRepository $boardRoleRepository */
      $boardRoleRepository = $this->getDoctrine()->getRepository(BoardRole::class);
      $roles = $boardRoleRepository->getBoardUsers($boardId);
      foreach($roles as $role) {
        if($role->getUser() !== $this->getUser()) {
          $notification = new Notification();
          $notification->setDate();
          $notification->setCreator($this->getUser());
          $notification->setUser($role->getUser());
          $notification->setUrl('');
          $notification->setText($this->getUser()->getUsername().' deleted <br>project <b>'.$board->getName().'</b>');
          $entityManager->persist($notification);
        }

        $role->delete();
        $entityManager->persist($role);
      }

      $entityManager->remove($board);
      $entityManager->flush();

      $arrData = ['board' => $boardId];
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

  /** Creates copy of the whole Board.
   * @Route("/ajax/boardDuplicate", name="boardDuplicate")
   * @param Request $request
   * @return JsonResponse
   */
  public function boardDuplicate(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $boardId = $request->request->get('board');
      $name = $request->request->get('name');
      $start = $request->request->get('start');
//  /**
//   * @Route("/ttt", name="boardDuplicate")
//   */
//  public function boardDuplicate() {
//    {
//      $boardId = '27';
//      $name = 'Pije';
//      $start = '04/05/2018';

      $entityManager = $this->getDoctrine()->getManager();
      /** @var BoardRepository $boardRepository */
      $boardRepository = $this->getDoctrine()->getRepository(Board::class);
      $board = $boardRepository->getBoard($boardId, $this->getUser());

      // create new board
      $copy = new Board();
      $copy->setColor($board->getColor());
      $copy->setName($name);
      $copy->setShareEnabled($board->isShareEnabled());
      $copy->setShareRights($board->getShareRights());
      $boardRepository->generateShareLink($copy);
      $entityManager->persist($copy);

      // give all board users same rights
      /** @var BoardRoleRepository $boardRoleRepository */
      $boardRoleRepository = $this->getDoctrine()->getRepository(BoardRole::class);
      $boardRights = $boardRoleRepository->getBoardUsers($board->getId());
      foreach($boardRights as $right) {
        if($right->isShareEnabled() && $right->isActive() && !$right->isDeleted()) {
          $newRight = new BoardRole();
          $newRight->setBoard($copy);
          $newRight->setRole($right->getRights());
          $newRight->setUser($right->getUser());
          $newRight->setBoardHistory($right->getBoardHistory());
          $entityManager->persist($newRight);

          $notification = new Notification();
          $notification->setDate();
          $notification->setCreator($this->getUser());
          $notification->setUser($right->getUser());
          $notification->setUrl($copy->getUrl());
          if($this->getUser() == $right->getUser())
            $notification->setText('Copy of <b>'.$copy->getName().'</b><br>successfully created');
          else
            $notification->setText(
              $this->getUser()->getUsername().' created copy<br>of project <b>'.$copy->getName().'</b>');
          $entityManager->persist($notification);
        }
      }

      // set all issues: rights, gauges, deadlines
      /** @var IssueRoleRepository $issueRoleRepository */
      $issueRoleRepository = $this->getDoctrine()->getRepository(IssueRole::class);
      /** @var GaugeRepository $gaugeRepository */
      $gaugeRepository = $this->getDoctrine()->getRepository(Gauge::class);
      /** @var DeadlineRepository $deadlineRepository */
      $deadlineRepository = $this->getDoctrine()->getRepository(Deadline::class);
      /** @var ReminderRepository $reminderRepository */
      $reminderRepository = $this->getDoctrine()->getRepository(Reminder::class);
      foreach ($board->getIssues() as $issue) {
        $copyIssue = new Issue();
        $copyIssue->setBoard($copy);
        $copyIssue->setName($issue->getName());
        $copyIssue->setShareRights($issue->getShareRights());
        $copyIssue->setShareEnabled($issue->isShareEnabled());
        $boardRepository->generateShareLink($copyIssue);
        $entityManager->persist($copyIssue);

        // for all issue give users same rights
        $issueRights = $issueRoleRepository->getIssueUsers($issue->getId());
        foreach ($issueRights as $right) {
          if($right->isShareEnabled() && $right->isActive() && !$right->isDeleted()) {
            $newRight = new IssueRole();
            $newRight->setIssue($copyIssue);
            $newRight->setBoardHistory($right->getBoardHistory());
            $newRight->setIssueHistory($right->getIssueHistory());
            $newRight->setUser($right->getUser());
            $newRight->setRole($right->getRights());
            $entityManager->persist($newRight);
          }
        }

        $startDate = date_create_from_format('d/m/Y', $start);
        // give issue all its gauges with value to 1
        $gauges = $gaugeRepository->getGaugesInIssue($issue);
        foreach($gauges as $gauge) {
          $copyGauge = new Gauge();
          $copyGauge->setIssue($copyIssue);
          $copyGauge->setName($gauge->getName());
          $copyGauge->setColor($gauge->getColor());
          $copyGauge->setPosition($gauge->getPosition());
          $copyGauge->setValue(1);
          $copyGauge->bindUserToGauge($gauge->getBindUser());
          $entityManager->persist($copyGauge);
          // logs first gauge value as 1.
          $gaugeRepository->gaugeValueInit($copyGauge, $this->getUser());

          // creates deadline for gauge
          $deadline = $deadlineRepository->getDeadlineByIssue($issue->getId(), $gauge->getId());
          if($deadline !== null) {
            $newDeadline = new Deadline();
            $newDeadline->setText($deadline->getText());
            $newDeadline->setIssue($copyIssue);
            $newDeadline->setGauge($copyGauge);
            $newDeadline->setStart($startDate);
            $endDate = date_create_from_format('d/m/Y', $start);
            $endDate->modify("+ ".$deadline->getDuration()." days");
            $newDeadline->setEnd($endDate);
            $entityManager->persist($newDeadline);
          }
        }

        //creates deadline for whole issue
        $deadline = $deadlineRepository->getDeadlineByIssue($issue->getId(), null);
        if($deadline !== null) {
          $newDeadline = new Deadline();
          $newDeadline->setText($deadline->getText());
          $newDeadline->setIssue($copyIssue);
          $newDeadline->setGauge(null);
          $newDeadline->setStart($startDate);
          $endDate = date_create_from_format('d/m/Y', $start);
          $endDate->modify("+ ".$deadline->getDuration()." days");
          $newDeadline->setEnd($endDate);
          $entityManager->persist($newDeadline);
        }

        // copy reminder settings
        $reminder = $reminderRepository->getReminderByIssue($issue->getId(), true);
        $newReminder = new Reminder();
        $newReminder->setIssue($copyIssue);
        $newReminder->setText($reminder->getText());
        $newReminder->setSendAnyway($reminder->canSendAnyway());
        $newReminder->setUsers($reminder->getUsers());
        $newReminder->setDays($reminder->getDays());
        $entityManager->persist($newReminder);
      }
      $entityManager->flush();
      $arrData = ['url' => $copy->getUrl()];
      return new JsonResponse($arrData);
    }
  }

}