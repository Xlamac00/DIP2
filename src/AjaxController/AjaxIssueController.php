<?php
namespace App\AjaxController;

use App\Entity\AbstractSharableEntity;
use App\Entity\Deadline;
use App\Entity\Gauge;
use App\Entity\GaugeChanges;
use App\Entity\GaugeRole;
use App\Entity\Issue;
use App\Entity\IssueRole;
use App\Entity\Notification;
use App\Entity\Reminder;
use App\Entity\User;
use App\Entity\UserGaugeShare;
use App\Entity\UserShare;
use App\Repository\DeadlineRepository;
use App\Repository\GaugeChangesRepository;
use App\Repository\GaugeRepository;
use App\Repository\GaugeRoleRepository;
use App\Repository\IssueRepository;
use App\Repository\IssueRoleRepository;
use App\Repository\ReminderRepository;
use App\Repository\UserRepository;
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
      $issueId = $request->request->get('issueId');
      $user = $request->request->get('userId');

      /** @var IssueRepository $issueRepository */
      $issueRepository = $this->getDoctrine()->getRepository(Issue::class);
      $issue = $issueRepository->getIssue($issueId, $this->getUser());
      if($issue->getThisUserRights() == Issue::ROLE_GAUGE) {
        /** @var GaugeRepository $gaugeRepository */
        $gaugeRepository = $this->getDoctrine()->getRepository(Gauge::class);
        if(!$gaugeRepository->isGaugeRightForIssue($this->getUser(), $issue)) return null;
      }
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

      if($issue->getThisUserRights()->getRights() == Issue::ROLE_GAUGE) {
        /** @var GaugeRepository $gaugeRepository */
        $gaugeRepository = $this->getDoctrine()->getRepository(Gauge::class);
        $gaugeEdit = $gaugeRepository->getBoundGauges($issue, $this->getUser());
      }
      else $gaugeEdit = array_fill(0, $gaugeCount, 1);

      /** @var DeadlineRepository $deadlineRepository */
      $deadlineRepository = $this->getDoctrine()->getRepository(Deadline::class);
      $deadlines = $deadlineRepository->getDeadlinesForIssue($issue);

      $list = $this->renderView('issue/deadlineList.html.twig',['deadlines' => $deadlines,'issue' => $issue]);
      $select = $this->renderView('issue/deadlinesSelect.html.twig',['deadlines' => $deadlines,'issue' => $issue]);
      $labels = $this->renderView('graphs/graph-labels.html.twig',['gauges' => $issue->getGauges()]);
      $colors = $this->renderView('graphs/graph-colors.html.twig',['gauges' => $issue->getGauges()]);
      $values = $this->renderView('graphs/graph-values.html.twig',['gauges' => $issue->getGauges()]);
      $names = $this->renderView('graphs/graph-names.html.twig',['gauges' => $issue->getGauges()]);

      $arrData =
        ['labels' => $labels,
         'colors' => $colors,
         'values' => $values,
         'deadlines' => $list,
         'names' => $names,
         'select' => $select,
         'gaugeRights' => $gaugeEdit,
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
      if(strlen($name) > 0)
        $gaugeRepository->changeGaugeName($name); // update the gauge
      if(strlen($color) > 0)
        $gaugeRepository->changeGaugeColor($color); // update the gauge

      /** @var IssueRepository $issueRepository */
      $issueRepository = $this->getDoctrine()->getRepository(Issue::class);
      $issue = $issueRepository->getIssue($issue_id, $this->getUser());

      if($issue->getThisUserRights()->getRights() == Issue::ROLE_GAUGE) {
        /** @var GaugeRepository $gaugeRepository */
        $gaugeRepository = $this->getDoctrine()->getRepository(Gauge::class);
        $gaugeEdit = $gaugeRepository->getBoundGauges($issue, $this->getUser());
      }
      else $gaugeEdit = array_fill(0, 4, 1);

      /** @var GaugeChangesRepository $gaugeChangesRepository */
      $gaugeChangesRepository = $this->getDoctrine()->getRepository(GaugeChanges::class);
      $changes = $gaugeChangesRepository->getAllChangesForIssue($issue->getId());

      /** @var DeadlineRepository $deadlineRepository */
      $deadlineRepository = $this->getDoctrine()->getRepository(Deadline::class);
      $deadlines = $deadlineRepository->getDeadlinesForIssue($issue);

      $list = $this->renderView('issue/deadlineList.html.twig',['deadlines' => $deadlines,'issue' => $issue]);
      $select = $this->renderView('issue/deadlinesSelect.html.twig',['deadlines' => $deadlines,'issue' => $issue]);
      $labels = $this->renderView('graphs/graph-labels.html.twig',['gauges' => $issue->getGauges()]);
      $colors = $this->renderView('graphs/graph-colors.html.twig',['gauges' => $issue->getGauges()]);
      $values = $this->renderView('graphs/graph-values.html.twig',['gauges' => $issue->getGauges()]);
      $names = $this->renderView('graphs/graph-names.html.twig',['gauges' => $issue->getGauges()]);
      $tab = $this->renderView('issue/editGaugeTab.html.twig',
        ['gauges' => $issue->getGauges(), 'gaugeEdit' => $gaugeEdit, 'canWrite' => $issue->canUserWrite()]);
      $comments = $this->renderView('issue/commentsTab.html.twig',['changes' => $changes]);

      $arrData =
        ['labels' => $labels,
         'colors' => $colors,
         'values' => $values,
         'deadlines' => $list,
         'names' => $names,
         'select' => $select,
         'gaugeRights' => $gaugeEdit,
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

      if($issue->getThisUserRights()->getRights() == Issue::ROLE_GAUGE) {
        /** @var GaugeRepository $gaugeRepository */
        $gaugeRepository = $this->getDoctrine()->getRepository(Gauge::class);
        $gaugeEdit = $gaugeRepository->getBoundGauges($issue, $this->getUser());
      }
      else $gaugeEdit = array_fill(0, 4, 1);

      $tab = $this->renderView('issue/editGaugeTab.html.twig',
        ['gauges' => $issue->getGauges(), 'gaugeEdit' => $gaugeEdit, 'canWrite' => $issue->canUserWrite()]);
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

      if($issue->getThisUserRights()->getRights() == Issue::ROLE_GAUGE) {
        /** @var GaugeRepository $gaugeRepository */
        $gaugeRepository = $this->getDoctrine()->getRepository(Gauge::class);
        $gaugeEdit = $gaugeRepository->getBoundGauges($issue, $this->getUser());
      }
      else $gaugeEdit = array_fill(0, 4, 1);

      /** @var GaugeChangesRepository $gaugeChangesRepository */
      $gaugeChangesRepository = $this->getDoctrine()->getRepository(GaugeChanges::class);
      $changes = $gaugeChangesRepository->getAllChangesForIssue($issue->getId());
      $gaugeCount = $issueRepository->getNumberOfGauges();

      /** @var DeadlineRepository $deadlineRepository */
      $deadlineRepository = $this->getDoctrine()->getRepository(Deadline::class);
      $deadlines = $deadlineRepository->getDeadlinesForIssue($issue);

      $list = $this->renderView('issue/deadlineList.html.twig',['deadlines' => $deadlines,'issue' => $issue]);
      $select = $this->renderView('issue/deadlinesSelect.html.twig',['deadlines' => $deadlines,'issue' => $issue]);
      $labels = $this->renderView('graphs/graph-labels.html.twig',['gauges' => $issue->getGauges()]);
      $colors = $this->renderView('graphs/graph-colors.html.twig',['gauges' => $issue->getGauges()]);
      $values = $this->renderView('graphs/graph-values.html.twig',['gauges' => $issue->getGauges()]);
      $names = $this->renderView('graphs/graph-names.html.twig',['gauges' => $issue->getGauges()]);
      $tab = $this->renderView('issue/editGaugeTab.html.twig',
        ['gauges' => $issue->getGauges(), 'gaugeEdit' => $gaugeEdit, 'canWrite' => $issue->canUserWrite()]);
      $comments = $this->renderView('issue/commentsTab.html.twig',['changes' => $changes]);

      $arrData =
        ['type' => 'gaugeDelete',
         'labels' => $labels,
         'colors' => $colors,
         'values' => $values,
         'comments' => $comments,
         'deadlines' => $list,
         'names' => $names,
         'select' => $select,
         'gaugeRights' => $gaugeEdit,
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
      $issue = $issueRepository->getIssue($issue_id, $this->getUser(), true);

      if($issue->getThisUserRights()->getRights() == Issue::ROLE_GAUGE) {
        /** @var GaugeRepository $gaugeRepository */
        $gaugeRepository = $this->getDoctrine()->getRepository(Gauge::class);
        $gaugeEdit = $gaugeRepository->getBoundGauges($issue, $this->getUser());
      }
      else $gaugeEdit = array_fill(0, 4, 1);

      $entityManager = $this->getDoctrine()->getManager();
      $entityManager->clear();
      $issue = $issueRepository->getIssue($issue_id, $this->getUser());
      $labels = $this->renderView('graphs/graph-labels.html.twig',['gauges' => $issue->getGauges()]);
      $colors = $this->renderView('graphs/graph-colors.html.twig',['gauges' => $issue->getGauges()]);
      $values = $this->renderView('graphs/graph-values.html.twig',['gauges' => $issue->getGauges()]);
      $names = $this->renderView('graphs/graph-names.html.twig',['gauges' => $issue->getGauges()]);
      $tab = $this->renderView('issue/editGaugeTab.html.twig',
        ['gauges' => $issue->getGauges(), 'gaugeEdit' => $gaugeEdit, 'canWrite' => $issue->canUserWrite()]);

      $arrData =
        ['labels' => $labels,
         'colors' => $colors,
         'values' => $values,
         'names' => $names,
         'gaugeRights' => $gaugeEdit,
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
      $gaugeId = $request->request->get('gauge');
      $text = $request->request->get('text');

      /** @var IssueRepository $issueRepository */
      $issueRepository = $this->getDoctrine()->getRepository(Issue::class);
      $issue = $issueRepository->getIssueByLink($issueId, $this->getUser());

      /** @var DeadlineRepository $deadlineRepository */
      $deadlineRepository = $this->getDoctrine()->getRepository(Deadline::class);
      $deadline = $deadlineRepository->getDeadlineByIssue($issue, $gaugeId === 'issue' ? null : $gaugeId);

      if($deadline === null) { // create new deadline
        $deadline = new Deadline();
        $deadline->setIssue($issue);
        $deadline->setStart(date_create_from_format('d/m/Y', $start));
        $deadline->setEnd(date_create_from_format('d/m/Y', $end));
        $deadline->setText($text);
        if($gaugeId !== 'issue') {
          /** @var GaugeRepository $gaugeRepository */
          $gaugeRepository = $this->getDoctrine()->getRepository(Gauge::class);
          $gauge = $gaugeRepository->getGauge($gaugeId);
          $deadline->setGauge($gauge);
        }
      }
      else {
        $deadline->setText($text);
        $deadline->setStart(date_create_from_format('d/m/Y', $start));
        $deadline->setEnd(date_create_from_format('d/m/Y', $end));
      }

      $entityManager = $this->getDoctrine()->getManager();
      $entityManager->persist($deadline);
      $entityManager->flush();

      $deadlines = $deadlineRepository->getDeadlinesForIssue($issue);

      $list = $this->renderView('issue/deadlineList.html.twig',['deadlines' => $deadlines,'issue' => $issue]);
      $select = $this->renderView('issue/deadlinesSelect.html.twig',['deadlines' => $deadlines,'issue' => $issue]);

      $arrData = ['issue' => $issueId, "list" => $list, "select" => $select, "gauge" => $gaugeId];
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
      $issue = $deadline->getIssue();

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

      $deadlines = $deadlineRepository->getDeadlinesForIssue($issue);

      $list = $this->renderView('issue/deadlineList.html.twig',['deadlines' => $deadlines]);
      $select = $this->renderView('issue/deadlinesSelect.html.twig',['deadlines' => $deadlines,'issue' => $issue]);

      $arrData = ['id' => $id, 'done' => $done, 'type' => 'deadlineDelete', "list" => $list, "select" => $select];
      return new JsonResponse($arrData);
    } else return null;
  }

  /** Gives rights to edit your one gauge (and view issue, board and all its issue) to one user.
   * @Route("/tesi", name="tesi")
   * @param \Swift_Mailer $mailer
   * @return null|JsonResponse
   */
  public function tesi(\Swift_Mailer $mailer) {
      $issueId = '52b54aab';
      $gaugeId = '31';
      $username = 'xlamac00@stud.fit.vutbr.cz';

      $gauge = null;
      /** @var IssueRepository $issueRepository */
      $issueRepository = $this->getDoctrine()->getRepository(Issue::class);
      $issue = $issueRepository->getIssueByLink($issueId, $this->getUser());
      if($issue !== null) {
        $rights = $issue->getThisUserRights();
        if($rights === null || $rights->getRights() === Issue::ROLE_VOID) $issue = null;
      }

      /** @var GaugeRepository $gaugeRepository */
      $gaugeRepository = $this->getDoctrine()->getRepository(Gauge::class);
      if($issue !== null) {
        $gauge = $gaugeRepository->getGaugeInIssue($gaugeId, $issue);
      }

      $result = array();
      $user = null;
      $email = null;
      $name = '';
      /** @var UserRepository $userRepository */
      $userRepository = $this->getDoctrine()->getRepository(User::class);
      // invite new user from my DB
      if(preg_match('/^.+\(.{2,}@ \.\.\. \)$/i', $username, $result) === 1) {
        $name = explode('(', $result[0]);
        $mail = explode('@', $name[1]);
        $user = $userRepository->findUserByNameAndEmail(trim($name[0]), trim($mail[0]));
      }
      // send email to new user
      elseif(preg_match('/^.{2,}@[a-z0-9\.\-]{2,}\.[a-z0-9]+$/i', $username, $result) === 1) {
        $email = $result[0];
        $user = $userRepository->findUserByEmail(trim($result[0]));
      }
      elseif(strlen($username) <= 1 && $gauge !== null) {
        // delete users right to edit single gauge
        $gaugeRepository->bindUserWithGauge($gauge, null);
      }
      else {
        if($gauge !== null) {
          $name = $gauge->getBindUserName();
        }
        else $name = '';
      }

      if($user instanceof User && $issue !== null && $gauge !== null) { // User is already in my DB
        $gaugeRepository->bindUserWithGauge($gauge, $user);
        $name = $user->getUsername();

        /** @var IssueRoleRepository $issueRoleRepository */
        $issueRoleRepository = $this->getDoctrine()->getRepository(IssueRole::class);
        $issueRoleRepository->giveUserRightsToIssue($user, $issue, Issue::ROLE_READ, null, null);

        $this->inviteUserByEmail($user->getEmail(), $issue->getUrl(), $issue, $this->getUser(), $mailer);
      }
      elseif(strlen($email) > 2 && $issue !== null && $gauge !== null) { // only users email was included
        $share = new UserShare();
        $share->setUser($this->getUser());
        $share->setRole(Issue::ROLE_READ);
        $share->setEmail($email);
        $share->setEntity($issue);
        $share->setGauge($gauge);
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($share);
        $entityManager->flush();

        $this->inviteUserByEmail($email, $share->getUrl(), $issue, $this->getUser(), $mailer);
      }

      $arrData = ['issue' => $issueId, 'gauge' => $gaugeId, 'user' => $name];
      return new JsonResponse($arrData);
  }

  /** Gives rights to edit your one gauge (and view issue, board and all its issue) to one user.
   * @Route("/ajax/issueInviteGauge", name="issue_ajax_inviteGauge")
   * @param Request $request - ajax Request
   * @param \Swift_Mailer $mailer
   * @return null|JsonResponse
   */
  public function issueInviteGauge(Request $request, \Swift_Mailer $mailer) {
    if ($request->isXmlHttpRequest()) {
      $issueId = $request->request->get('issueId');
      $gaugeId = $request->request->get('gaugeId');
      $username = $request->request->get('userName');

      $gauge = null;
      /** @var IssueRepository $issueRepository */
      $issueRepository = $this->getDoctrine()->getRepository(Issue::class);
      $issue = $issueRepository->getIssueByLink($issueId, $this->getUser());
      if($issue !== null) {
        $rights = $issue->getThisUserRights();
        if($rights === null || $rights->getRights() === Issue::ROLE_VOID) $issue = null;
      }

      /** @var GaugeRepository $gaugeRepository */
      $gaugeRepository = $this->getDoctrine()->getRepository(Gauge::class);
      if($issue !== null) {
        $gauge = $gaugeRepository->getGaugeInIssue($gaugeId, $issue);
      }

      $result = array();
      $user = null;
      $email = null;
      $name = '';
      $success = false;
      /** @var UserRepository $userRepository */
      $userRepository = $this->getDoctrine()->getRepository(User::class);
      // invite new user from my DB
      if(preg_match('/^.+\(.{2,}@ \.\.\. \)$/i', $username, $result) === 1) {
        $name = explode('(', $result[0]);
        $mail = explode('@', $name[1]);
        $user = $userRepository->findUserByNameAndEmail(trim($name[0]), trim($mail[0]));
      }
      // send email to new user
      elseif(preg_match('/^.{2,}@[a-z0-9\.\-]{2,}\.[a-z0-9]+$/i', $username, $result) === 1) {
        $email = $result[0];
        $user = $userRepository->findUserByEmail(trim($result[0]));
      }
      elseif(strlen($username) <= 0 && $gauge !== null) {
        // delete users right to edit single gauge
        $gaugeRepository->bindUserWithGauge($gauge, null);
        $success = true;
      }
      else {
        if($gauge !== null) {
          $name = $gauge->getBindUserMail();
          if($name === $username)
            $success = true;
        }
        else $name = '';
      }

      if($user instanceof User && $issue !== null && $gauge !== null) { // User is already in my DB
        if($user->isAnonymous() && $user->getAnonymousEmail() !== null)
          $name = explode('@', $user->getAnonymousEmail())[0]."@...";
        else
          $name = $user->getUsername();
        if($gauge->getBindUserName() !== $user->getUsername()) {
          $gaugeRepository->bindUserWithGauge($gauge, $user);

          /** @var IssueRoleRepository $issueRoleRepository */
          $issueRoleRepository = $this->getDoctrine()->getRepository(IssueRole::class);
          $issueRoleRepository->giveUserRightsToIssue($user, $issue, Issue::ROLE_READ, null, null);

          if($user !== $this->getUser()) {
            $notification = new Notification();
            $notification->setDate();
            $notification->setCreator($this->getUser());
            $notification->setUser($user);
            $notification->setUrl($issue->getUrl());
            $notification->setText($this->getUser()->getUsername().' assigned you <br>to task <b>'.$gauge->getName().'</b>');
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($notification);
            $entityManager->flush();
          }
//          $this->inviteUserByEmail($user->getEmail(), $issue->getUrl(), $issue, $this->getUser(), $mailer);
        }
        $success = true;
      }
      elseif(strlen($email) > 2 && $issue !== null && $gauge !== null) { // only users email was included
        $share = new UserShare();
        $share->setUser($this->getUser());
        $share->setRole(Issue::ROLE_READ);
        $share->setEmail($email);
        $share->setEntity($issue);
        $share->setGauge($gauge);
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($share);
        $entityManager->flush();
        $name = "user invited";
        $gaugeRepository->bindUserWithGauge($gauge, null); // restart user

        $this->inviteUserByEmail($email, $share->getUrl(), $issue, $this->getUser(), $mailer);
        $success = true;
      }

      /** @var DeadlineRepository $deadlineRepository */
      $deadlineRepository = $this->getDoctrine()->getRepository(Deadline::class);
      $deadlines = $deadlineRepository->getDeadlinesForIssue($issue);

      $list = $this->renderView('issue/deadlineList.html.twig',['deadlines' => $deadlines,'issue' => $issue]);
      $labels = $this->renderView('graphs/graph-labels.html.twig',['gauges' => $issue->getGauges()]);
      $colors = $this->renderView('graphs/graph-colors.html.twig',['gauges' => $issue->getGauges()]);
      $values = $this->renderView('graphs/graph-values.html.twig',['gauges' => $issue->getGauges()]);
      $names = $this->renderView('graphs/graph-names.html.twig',['gauges' => $issue->getGauges()]);

      $arrData =
        ['labels' => $labels,
         'colors' => $colors,
         'values' => $values,
         'list' => $list,
         'names' => $names,
         'issue' => $issueId,
         'gauge' => $gaugeId,
         'success' => $success,
         'user' => $name];
      return new JsonResponse($arrData);
    } else return null;
  }

  /**
   * @param string                 $email
   * @param string                 $shareLink
   * @param Issue $entity
   * @param User                   $owner
   * @param \Swift_Mailer          $mailer
   */
  private function inviteUserByEmail(string $email, string $shareLink,
                                     Issue $entity, User $owner, \Swift_Mailer $mailer) {
    $message = (new \Swift_Message('Task invitation'))
      ->setFrom('project-manager@heroku.com')
      ->setTo($email)
      ->setBody(
        '<h2>Hello,</h2> '.$owner->getUsername().' ( '.$owner->getEmail().' )'
        .' wants to share a task with you.'
        .'<p><h3>'.$entity->getName().'</h3>'
        .'<a href="https://xlamac00-dip.herokuapp.com/'.$shareLink.'" target="_blank">'.$shareLink.'</a></p>', // Its not an error!
        'text/html'
      );
    $mailer->send($message);
  }
}
