<?php
namespace App\AjaxController;

use App\Entity\Deadline;
use App\Entity\Gauge;
use App\Entity\GaugeChanges;
use App\Entity\Issue;
use App\Entity\IssueRole;
use App\Entity\Notification;
use App\Entity\Reminder;
use App\Repository\DeadlineRepository;
use App\Repository\GaugeChangesRepository;
use App\Repository\GaugeRepository;
use App\Repository\IssueRepository;
use App\Repository\IssueRoleRepository;
use App\Repository\ReminderRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AjaxIssueController extends Controller {

  /**
   * @Route("/ajax/issueNew", name="issue_ajax_new")
   * @param Request $request - ajax Request
   * @return null|JsonResponse
   */
  public function issueNew(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $name = $request->request->get('name');
      $board_id = $request->request->get('board');

      /** @var IssueRepository $repo */
      $repo = $this->getDoctrine()->getRepository(Issue::class);
      $link = $repo->createNewIssue($name, $board_id, $this->getUser());

      $arrData = ['link' => $link];
      return new JsonResponse($arrData);
    } else return null;
  }

  /**
   * @Route("/ajax/issueDelete", name="issue_ajax_delete")
   * @param Request $request - ajax Request
   * @return null|JsonResponse
   */
  public function issueDelete(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $link = $request->request->get('value1');

      /** @var IssueRepository $issueRepository */
      $issueRepository = $this->getDoctrine()->getRepository(Issue::class);
      $issue = $issueRepository->getIssueByLink($link, $this->getUser());
      $return = $issue->getBoard()->getUrl();
      $entityManager = $this->getDoctrine()->getManager();


      /** @var IssueRoleRepository $issueRoleRepository */
      $issueRoleRepository = $this->getDoctrine()->getRepository(IssueRole::class);
      $roles = $issueRoleRepository->getIssueUsers($issue->getId());
      foreach($roles as $role) {
        if($role->getUser() !== $this->getUser()) {
          $notification = new Notification();
          $notification->setDate();
          $notification->setCreator($this->getUser());
          $notification->setUser($role->getUser());
          $notification->setUrl('');
          $notification->setText($this->getUser()->getUsername().' deleted <br>issue <b>'.$issue->getName().'</b>');
          $entityManager->persist($notification);
        }

        $role->delete();
        $entityManager->persist($role);
      }

      $entityManager->remove($issue);
      $entityManager->flush();

      $arrData = ['link' => $link, 'type' => 'issueDelete', 'return' => $return];
      return new JsonResponse($arrData);
    } else return null;
  }

  /**
   * @Route("/ajax/issueRestore", name="issue_ajax_restore")
   * @param Request $request - ajax Request
   * @return null|JsonResponse
   */
  public function issueRestore(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $link = $request->request->get('link');

      /** @var IssueRepository $issueRepository */
      $issueRepository = $this->getDoctrine()->getRepository(Issue::class);
      /** @var EntityManager $entityManager */
      $entityManager = $this->getDoctrine()->getManager();
      $entityManager->getFilters()->disable('softdeleteable'); // allow to load even deleted Issues
      $issue = $issueRepository->getIssueByLink($link, $this->getUser());
      $issue->restore();
      $entityManager->persist($issue);
      $entityManager->flush();
      $entityManager->getFilters()->enable('softdeleteable');

      $arrData = ['link' => $link];
      return new JsonResponse($arrData);
    } else return null;
  }

  /**
   * @Route("/ajax/issueGetShareModal", name="issue_ajax_share_modal")
   * @param Request $request - ajax Request
   * @return null|JsonResponse
   */
  public function issueGetShareModal(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $issueLink = $request->request->get('issue');

      /** @var IssueRepository $issueRepository */
      $issueRepository = $this->getDoctrine()->getRepository(Issue::class);
      $issue = $issueRepository->getIssueByLink($issueLink, $this->getUser());

      $render = $this->renderView('issue/share-modal.html.twig',['issue' => $issue]);

      $arrData = ['render' => $render, 'link' => $issueLink];
      return new JsonResponse($arrData);
    } else return null;
  }

  /**
   * @Route("/ajax/issueGraphChange", name="issue_ajax_graphChange")
   * @param Request $request - ajax Request
   * @return null|JsonResponse
   */
  public function graphChange(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $number = $request->request->get('gaugeNumber');
      $value = $request->request->get('gaugeValue');
      $issue = $request->request->get('issueId');
      $user = $request->request->get('userId');

      /** @var IssueRepository $issueRepository */
      $issueRepository = $this->getDoctrine()->getRepository(Issue::class);
      $issueRepository->getIssue($issue, $this->getUser());
      $arrData = $issueRepository->gaugeValueChange($number, $value, $user);
      return new JsonResponse($arrData);
    } else return null;
  }

  /**
   * @Route("/ajax/issueGraphDiscard", name="issue_ajax_graphDiscard")
   * @param Request $request - ajax Request
   * @return null|JsonResponse
   */
  public function graphChangeDiscard(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $issue = $request->request->get('issueId');

      /** @var GaugeRepository $gaugeRepository */
      $gaugeRepository = $this->getDoctrine()->getRepository(Gauge::class);
      $result = $gaugeRepository->gaugeValueDiscard($issue);

      $arrData =
        ['newValue' => $result['newValue'],
         'position' => $result['position']];
      return new JsonResponse($arrData);
    } else return null;
  }

  /**
   * @Route("/ajax/issueGraphComment", name="issue_ajax_graphComment")
   * @param Request $request - ajax Request
   * @return null|JsonResponse
   */
  public function graphChangeComment(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $issue = $request->request->get('issueId');
      $text = $request->request->get('text');

      /** @var GaugeChangesRepository $changeRepository */
      $changeRepository = $this->getDoctrine()->getRepository(GaugeChanges::class);
      $result = $changeRepository->gaugeCommentSave($issue, $text);

      $render = $this->renderView('issue/comment.html.twig', ['change' => $result]);
      return new JsonResponse($render);
    } else return null;
  }
  /**
   * @Route("/ajax/issueNewGauge", name="issue_ajax_newGauge")
   * @param Request $request - ajax Request
   * @return null|JsonResponse
   */
  public function graphNewGauge(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $name = $request->request->get('name');
      $color = $request->request->get('color');
      $issueId = $request->request->get('issueId');
      $userId = $request->request->get('userId');

      /** @var IssueRepository $issueRepository */
      $issueRepository = $this->getDoctrine()->getRepository(Issue::class);
      $gaugeNumber = $issueRepository->getNumberOfGauges($issueId);
      $issue = $issueRepository->getIssue($issueId, $this->getUser());

      $entityManager = $this->getDoctrine()->getManager();
      $gauge = new Gauge();
      $gauge->setValue(1);
      $gauge->setName($name);
      $gauge->setColor($color);
      $gauge->setIssue($issue);
      $gauge->setPosition($gaugeNumber);
      $entityManager->persist($gauge);
      $entityManager->flush();

      /** @var GaugeRepository $gaugeRepository */
      $gaugeRepository = $this->getDoctrine()->getRepository(Gauge::class);
      $gaugeRepository->setGauge($gauge);
      $gaugeRepository->gaugeValueLog(1, $userId);

      $issue = $issueRepository->getIssue($issueId, $this->getUser());
      $gaugeCount = $issueRepository->getNumberOfGauges();
      $labels = $this->renderView('graphs/graph-labels.html.twig',['gauges' => $issue->getGauges()]);
      $colors = $this->renderView('graphs/graph-colors.html.twig',['gauges' => $issue->getGauges()]);
      $values = $this->renderView('graphs/graph-values.html.twig',['gauges' => $issue->getGauges()]);

      $arrData =
        ['labels' => $labels,
         'colors' => $colors,
         'values' => $values,
         'gaugeCount' => $gaugeCount];
      return new JsonResponse($arrData);
    } else return null;
  }

  /**
   * @Route("/ajax/issueUpdateGauge", name="issue_ajax_gaugeUpdate")
   * @param Request $request - ajax Request
   * @return null|JsonResponse
   */
  public function graphUpdateGauge(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $issue_id = $request->request->get('issueId');
      $gauge_id = $request->request->get('gaugeId');
      $name = $request->request->get('name');
      $color = $request->request->get('color');

      /** @var GaugeRepository $gaugeRepository */
      $gaugeRepository = $this->getDoctrine()->getRepository(Gauge::class);
      $gaugeRepository->getGauge($gauge_id);
      $gaugeRepository->changeGaugeData($name, $color); // update the gauge

      /** @var IssueRepository $issueRepository */
      $issueRepository = $this->getDoctrine()->getRepository(Issue::class);
      $issue = $issueRepository->getIssue($issue_id, $this->getUser());

      /** @var GaugeChangesRepository $gaugeChangesRepository */
      $gaugeChangesRepository = $this->getDoctrine()->getRepository(GaugeChanges::class);
      $changes = $gaugeChangesRepository->getAllChangesForIssue($issue->getId());

      $labels = $this->renderView('graphs/graph-labels.html.twig',['gauges' => $issue->getGauges()]);
      $colors = $this->renderView('graphs/graph-colors.html.twig',['gauges' => $issue->getGauges()]);
      $values = $this->renderView('graphs/graph-values.html.twig',['gauges' => $issue->getGauges()]);
      $tab = $this->renderView('issue/editGaugeTab.html.twig',['gauges' => $issue->getGauges()]);
      $comments = $this->renderView('issue/commentsTab.html.twig',['changes' => $changes]);

      $arrData =
        ['labels' => $labels,
         'colors' => $colors,
         'values' => $values,
         'comments' => $comments,
         'tab' => $tab];
      return new JsonResponse($arrData);
    } else return null;
  }

  /**
   * @Route("/ajax/issueGetGauges", name="issue_ajax_gaugesInfo")
   * @param Request $request - ajax Request
   * @return null|JsonResponse
   */
  public function getGaugesInfo(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $issue_id = $request->request->get('issueId');

      /** @var IssueRepository $issueRepository */
      $issueRepository = $this->getDoctrine()->getRepository(Issue::class);
      $issue = $issueRepository->getIssue($issue_id, $this->getUser());

      $tab = $this->renderView('issue/editGaugeTab.html.twig',['gauges' => $issue->getGauges()]);
      return new JsonResponse($tab);
    } else return null;
  }

  /**
   * @Route("/ajax/issueOneGauge", name="issue_ajax_oneGaugeInfo")
   * @param Request $request - ajax Request
   * @return null|JsonResponse
   */
  public function getOneGaugeInfo(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $gauge_id = $request->request->get('gaugeId');

      /** @var GaugeRepository $gaugeRepository */
      $gaugeRepository = $this->getDoctrine()->getRepository(Gauge::class);
      $gauge = $gaugeRepository->getGauge($gauge_id);

      $tab = $this->renderView('issue/newGaugeTab.html.twig',
        ['name' => $gauge->getName(), 'title' => 'Edit '.$gauge->getName().":",
         'color' => $gauge->getColor(), 'id' => $gauge_id]);
      return new JsonResponse(array('id' => $gauge_id, 'render' => $tab));
    } else return null;
  }

  /**
   * @Route("/ajax/issueGaugeDelete", name="issue_ajax_gaugeDelete")
   * @param Request $request - ajax Request
   * @return null|JsonResponse
   */
  public function graphDeleteGauge(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $gauge_id = $request->request->get('value1');
      $issue_id = $request->request->get('value2');

      /** @var GaugeRepository $gaugeRepository */
      $gaugeRepository = $this->getDoctrine()->getRepository(Gauge::class);
      $gauge = $gaugeRepository->getGauge($gauge_id);

      $entityManager = $this->getDoctrine()->getManager();
      $entityManager->remove($gauge);
      $entityManager->flush();

      /** @var IssueRepository $issueRepository */
      $issueRepository = $this->getDoctrine()->getRepository(Issue::class);
      $issue = $issueRepository->getIssue($issue_id, $this->getUser());
      $issueRepository->updateGaugesIndex();

      /** @var GaugeChangesRepository $gaugeChangesRepository */
      $gaugeChangesRepository = $this->getDoctrine()->getRepository(GaugeChanges::class);
      $changes = $gaugeChangesRepository->getAllChangesForIssue($issue->getId());
      $gaugeCount = $issueRepository->getNumberOfGauges();

      $labels = $this->renderView('graphs/graph-labels.html.twig',['gauges' => $issue->getGauges()]);
      $colors = $this->renderView('graphs/graph-colors.html.twig',['gauges' => $issue->getGauges()]);
      $values = $this->renderView('graphs/graph-values.html.twig',['gauges' => $issue->getGauges()]);
      $tab = $this->renderView('issue/editGaugeTab.html.twig',['gauges' => $issue->getGauges()]);
      $comments = $this->renderView('issue/commentsTab.html.twig',['changes' => $changes]);

      $arrData =
        ['type' => 'gaugeDelete',
         'labels' => $labels,
         'colors' => $colors,
         'values' => $values,
         'comments' => $comments,
         'gaugeCount' => $gaugeCount,
         'tab' => $tab];
      return new JsonResponse($arrData);
    } else return null;
  }

  /**
   * @Route("/ajax/gaugeChangePosition", name="issue_ajax_gaugeChangePosition")
   * @param Request $request - ajax Request
   * @return null|JsonResponse
   */
  public function gaugeChangePosition(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $issue_id = $request->request->get('issueId');
      $gauge_id = $request->request->get('gaugeId');
      $new_position = $request->request->get('position');

      /** @var IssueRepository $issueRepository */
      $issueRepository = $this->getDoctrine()->getRepository(Issue::class);
      $issueRepository->getIssue($issue_id, $this->getUser());
      $issueRepository->updateGaugesIndex($gauge_id, $new_position);

      $entityManager = $this->getDoctrine()->getManager();
      $entityManager->clear();
      $issue = $issueRepository->getIssue($issue_id, $this->getUser());
      $labels = $this->renderView('graphs/graph-labels.html.twig',['gauges' => $issue->getGauges()]);
      $colors = $this->renderView('graphs/graph-colors.html.twig',['gauges' => $issue->getGauges()]);
      $values = $this->renderView('graphs/graph-values.html.twig',['gauges' => $issue->getGauges()]);
      $tab = $this->renderView('issue/editGaugeTab.html.twig',['gauges' => $issue->getGauges()]);

      $arrData =
        ['labels' => $labels,
         'colors' => $colors,
         'values' => $values,
         'tab' => $tab];
      return new JsonResponse($arrData);
    } else return null;
  }

  /** Updates Issue name.
   * @Route("/ajax/issueUpdate", name="issue_ajax_issueUpdate")
   * @param Request $request - ajax Request
   * @return null|JsonResponse
   */
  public function issueUpdate(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $issue = $request->request->get('issueId');
      $name = $request->request->get('name');

      /** @var IssueRepository $issueRepository */
      $issueRepository = $this->getDoctrine()->getRepository(Issue::class);
      $issueRepository->getIssue($issue, $this->getUser());
      $issueRepository->updateName($name);

      $arrData = ['name' => $name];
      return new JsonResponse($arrData);
    } else return null;
  }

  /** Changes the Issue daily reminder settings.
   * @Route("/ajax/issueChangeReminder", name="issue_ajax_changeReminder")
   * @param Request $request - ajax Request
   * @return null|JsonResponse
   */
  public function issueChangeReminder(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $issueId = $request->request->get('issueId');
      $days = $request->request->get('days');
      $remind = $request->request->get('remind');
      $users = $request->request->get('users');
      $text = $request->request->get('text');

      /** @var IssueRepository $issueRepository */
      $issueRepository = $this->getDoctrine()->getRepository(Issue::class);
      $issue = $issueRepository->getIssueByLink($issueId, $this->getUser());

      /** @var ReminderRepository $reminderRepository */
      $reminderRepository = $this->getDoctrine()->getRepository(Reminder::class);
      $data = $reminderRepository->getReminderByIssue($issue);
      $data->setSendAnyway($remind === 'true');
      $data->setText($text);
      $data->setUsers($users);
      $data->setDays($days);
      $entityManager = $this->getDoctrine()->getManager();
      $entityManager->persist($data);
      $entityManager->flush();

      $arrData = ['name' => $issue->getName(), 'days' => $days, 'remind' => $remind, 'text' => $text, 'users' =>
        $users];
      return new JsonResponse($arrData);
    } else return null;
  }

  /** Returns Issue reminder render
   * @Route("/ajax/issueGetReminder", name="issue_ajax_getReminder")
   * @param Request $request - ajax Request
   * @return null|JsonResponse
   */
  public function issueGetReminder(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $issueId = $request->request->get('issueId');

      /** @var IssueRepository $issueRepository */
      $issueRepository = $this->getDoctrine()->getRepository(Issue::class);
      $issue = $issueRepository->getIssueByLink($issueId, $this->getUser());

      /** @var ReminderRepository $reminderRepository */
      $reminderRepository = $this->getDoctrine()->getRepository(Reminder::class);
      $data = $reminderRepository->getReminderByIssue($issue);
      if($data === null) { // DOCASNE, PRECHOD NA NOVOU VERZI, POTE SMAZAT!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
        $reminder = new Reminder();
        $reminder->setIssue($issue);
        $reminder->setText('Hello!');
        $reminder->setDays(['false','false','false','false','false','false','false']);
        $reminder->setUsers([]);
        $reminder->setSendAnyway(false);
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($reminder);
        $entityManager->flush();
        $data = $reminder;
      }

      /** @var IssueRoleRepository $roleRepository */
      $roleRepository = $this->getDoctrine()->getRepository(IssueRole::class);
      $roles = $roleRepository->getIssueUsers($issue->getId());

      $render = $this->renderView('issue/reminderTab.html.twig',['roles' => $roles, 'data' => $data]);

      $arrData = ['render' => $render];
      return new JsonResponse($arrData);
    } else return null;
  }

  /** Creates new deadline in db
   * @Route("/ajax/issueNewDeadline", name="issue_ajax_newDeadline")
   * @param Request $request - ajax Request
   * @return null|JsonResponse
   */
  public function issueNewDeadline(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $issueId = $request->request->get('issueId');
      $start = $request->request->get('start');
      $end = $request->request->get('end');
      $checkbox = $request->request->get('checkbox');
      $gaugeId = $request->request->get('gauge');
      $text = $request->request->get('text');

      /** @var IssueRepository $issueRepository */
      $issueRepository = $this->getDoctrine()->getRepository(Issue::class);
      $issue = $issueRepository->getIssueByLink($issueId, $this->getUser());

      $deadline = new Deadline();
      $deadline->setFresh();
      $deadline->setIssue($issue);
      $deadline->setStart(date_create_from_format('d/m/Y', $start));
      $deadline->setEnd(date_create_from_format('d/m/Y', $end));
      $deadline->setText($text);
      if($checkbox === 'true' && $gaugeId !== 'null') {
        /** @var GaugeRepository $gaugeRepository */
        $gaugeRepository = $this->getDoctrine()->getRepository(Gauge::class);
        $gauge = $gaugeRepository->getGauge($gaugeId);
        $deadline->setGauge($gauge);
      }
      $entityManager = $this->getDoctrine()->getManager();
      $entityManager->persist($deadline);
      $entityManager->flush();

      $render = $this->renderView('issue/deadline.html.twig',['deadline' => $deadline]);

      $arrData = ['issue' => $issueId, "render" => $render, "check" => $checkbox, "gauge" => $gaugeId];
      return new JsonResponse($arrData);
    } else return null;
  }

  /** Deletes deadline
   * @Route("/ajax/issueDeleteDeadline", name="issue_ajax_deleteDeadline")
   * @param Request $request - ajax Request
   * @return null|JsonResponse
   */
  public function issueDeleteDeadline(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $id = $request->request->get('value1');

      /** @var DeadlineRepository $deadlineRepository */
      $deadlineRepository = $this->getDoctrine()->getRepository(Deadline::class);
      $deadline = $deadlineRepository->getDeadlineById($id);

      /** @var IssueRoleRepository $issueRoleRepository */
      $issueRoleRepository = $this->getDoctrine()->getRepository(IssueRole::class);
      $role = $issueRoleRepository->getUserRights($this->getUser(), $deadline->getIssue());
      if($role->getRights() === Issue::ROLE_ADMIN
        || $role->getRights() === Issue::ROLE_WRITE
        || $role->getRights() === Issue::ROLE_ANON) {
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($deadline);
        $entityManager->flush();
        $done = true;
      }
      else {
        $done = false;
      }

      $arrData = ['id' => $id, 'done' => $done, 'type' => 'deadlineDelete'];
      return new JsonResponse($arrData);
    } else return null;
  }
}
