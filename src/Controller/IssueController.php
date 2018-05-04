<?php

namespace App\Controller;

use App\Entity\Deadline;
use App\Entity\Gauge;
use App\Entity\GaugeChanges;
use App\Entity\Issue;
use App\Entity\Notification;
use App\Entity\Tips;
use App\Entity\User;
use App\Repository\DeadlineRepository;
use App\Repository\GaugeChangesRepository;
use App\Repository\GaugeRepository;
use App\Repository\IssueRepository;
use App\Repository\NotificationRepository;
use App\Repository\TipsRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class IssueController extends Controller {

  /** Link to redirect to Issue when sharing just one Gauge with user email
   * @Route("/g/{link}/{name}", name="gauge")
   */
  public function gaugeShare($link, $name) {
    return $this->index($link, $name);
  }

  /**
   * @Route("/i/{link}/{name}", name="issue")
   */
  public function index($link, $name) {
    /** @var User $user */
    $user = $this->getUser();

    /** @var IssueRepository $issueRepository */
    $issueRepository = $this->getDoctrine()->getRepository(Issue::class);
    $issue = $issueRepository->getIssueByLink($link, $user);
    // During first access via the link, the order of operations is kind of messed up, so set user rights correctly
    if($issue->getThisUserRights() === null) {
      // force Board entity reload with new user rights
      $issue = $issueRepository->getIssue($issue->getId(), $user, true);
    }
    $gaugeCount = $issueRepository->getNumberOfGauges();
    if($issue->getThisUserRights()->getRights() == Issue::ROLE_GAUGE) {
      /** @var GaugeRepository $gaugeRepository */
      $gaugeRepository = $this->getDoctrine()->getRepository(Gauge::class);
      $gaugeEdit = $gaugeRepository->getBoundGauges($issue, $user);
    }
    else $gaugeEdit = array_fill(0, $gaugeCount, 1);
    /** @var GaugeChangesRepository $gaugeChangesRepository */
    $gaugeChangesRepository = $this->getDoctrine()->getRepository(GaugeChanges::class);
    $changes = $gaugeChangesRepository->getAllChangesForIssue($issue->getId());
    $users = $issueRepository->getAllActiveUsers($issue->getId());

    /** @var DeadlineRepository $deadlineRepository */
    $deadlineRepository = $this->getDoctrine()->getRepository(Deadline::class);
    $deadlines = $deadlineRepository->getDeadlinesForIssue($issue);

    /** @var NotificationRepository $notificationRepository */
    $notificationRepository = $this->getDoctrine()->getRepository(Notification::class);
    $notifications = $notificationRepository->getUnreadNotifications($user);

    /** @var TipsRepository $tipsRepository */
    $tipsRepository = $this->getDoctrine()->getRepository(Tips::class);
    $tips = $tipsRepository->getNewTipsForPage('issue', $user->getAnonymousLink());

    return $this->render('issue/issue-detail.html.twig',
      ["issue" => $issue, "changes" => $changes, "gaugeCount" => $gaugeCount,
       "users" => array_reverse($users), "deadlines" => $deadlines,
       'notifications' => $notifications, "tips" => $tips, "gaugeEdit" => $gaugeEdit]);
  }

}
