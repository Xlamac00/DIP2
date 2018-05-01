<?php

namespace App\CronController;

use App\Entity\Board;
use App\Entity\BoardRole;
use App\Entity\GaugeChanges;
use App\Entity\Issue;
use App\Entity\IssueRole;
use App\Entity\Notification;
use App\Entity\Reminder;
use App\Entity\ReminderHistory;
use App\Entity\User;
use App\Repository\BoardRepository;
use App\Repository\BoardRoleRepository;
use App\Repository\GaugeChangesRepository;
use App\Repository\IssueRepository;
use App\Repository\IssueRoleRepository;
use App\Repository\ReminderHistoryRepository;
use App\Repository\ReminderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class CronReminderController extends Controller {

  /**
   * @Route("/cron/reminder/{string}", name="cron_reminder")
   * @param \Swift_Mailer $mailer
   */
  public function cronReminder($string, \Swift_Mailer $mailer) {
    if($string !== 'startScript') die('');
    $send = 0;

    /** @var IssueRoleRepository $roleRepository */
    $roleRepository = $this->getDoctrine()->getRepository(IssueRole::class);
    /** @var ReminderRepository $reminderRepository */
    $reminderRepository = $this->getDoctrine()->getRepository(Reminder::class);
    /** @var ReminderHistoryRepository $reminderHistoryRepository */
    $reminderHistoryRepository = $this->getDoctrine()->getRepository(ReminderHistory::class);
    /** @var GaugeChangesRepository $gaugeChangesRepository */
    $gaugeChangesRepository = $this->getDoctrine()->getRepository(GaugeChanges::class);
    // returns only reminders that are set for TODAY
    $reminders = $reminderRepository->getReminderByDay(date('N')-1);
    $entityManager = $this->getDoctrine()->getManager();

    foreach ($reminders as $reminder) { // for each Issue reminder, get all users and send notifications
      $users = $roleRepository->getIssueUsers($reminder->getIssue()->getId());

      $url = (isset($_SERVER['HTTPS']) ? "https" : "http")."://".$_SERVER['HTTP_HOST'].'/'.$reminder->getIssue()->getUrl();
      $message = (new \Swift_Message('Weekly progress: '.$reminder->getIssue()->getName()))
        ->setFrom('project-manager@heroku.com')
        ->setBody(
          $reminder->getText()."<br>".
          "<p><h3>".$reminder->getIssue()->getName().":</h3><a href='".$url."' target='_blank'>".$url."</a></p>",
          'text/html'
        );
      foreach ($users as $user) { // for each user in the issue
        // if user has rights to write to this Issue
        /** @var User $myUser */
        $myUser = $user->getUser();
        if($user->isActive() &&
          ($user->getRights() == Issue::ROLE_ADMIN || $user->getRights() == Issue::ROLE_ANON ||
            ($user->getRights() == Issue::ROLE_WRITE && !$myUser->isAnonymous()))) {
          $last = $reminderHistoryRepository->getLastReminderDate($myUser, $reminder->getIssue());
          if($last !== null && $last->getTimestamp() > strtotime('-20 hours')){
            continue; // last reminder was send recently - probably called this function more times!
          }
          // check changes from last reminder - is it required?
          if(!$reminder->canSendAnyway()) {
            $change = $gaugeChangesRepository->getUserNewestChange($myUser, $reminder->getIssue());
            if($change !== null && $last !== null) {
              if($change > $last) continue; // skip - there was change between last notification and now
            }
          }
          // check all "banned" user (aka users not to inform) in this issue
          $skip = false;
          foreach($reminder->getUsers() as $banned) {
            if($banned == $myUser->getId()) $skip = true; // if the user is "banned", skip him
          }
          if($skip === true) continue;
          $email = $myUser->isAnonymous() ? $myUser->getAnonymousEmail() : $myUser->getEmail();
          if(strlen($email) > 4) { // user has saved email, send him
            $message->setTo($email);
            $mailer->send($message);
            $send++;
          }

          // save notification for every user
          $notification = new Notification();
          $notification->setDate();
          $notification->setCreator($this->getUser());
          $notification->setUser($user->getUser());
          $notification->setUrl($reminder->getIssue()->getUrl());
          $notification->setText('Don\'t forget to update <br>your progress in <b>'.$reminder->getIssue()->getName()
          .'</b>!');
          $entityManager->persist($notification);

          $history = new ReminderHistory();
          $history->setTime();
          $history->setIssue($reminder->getIssue());
          $history->setUser($myUser);
          $entityManager->persist($history);
        }
      }
      $entityManager->flush();
    }
    $message = (new \Swift_Message('Weekly progress complete'))
      ->setFrom('project-manager@heroku.com')
      ->setTo('edgar417@gmail.com')
      ->setBody("Emails: ".$send,'text/html');
    $mailer->send($message);
    die($send."");
  }
}