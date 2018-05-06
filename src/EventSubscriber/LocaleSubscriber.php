<?php
namespace App\EventSubscriber;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

//http://symfony.com/doc/master/translation/locale.html
//http://symfony.com/doc/master/session/locale_sticky_session.html
class LocaleSubscriber implements EventSubscriberInterface {
  private $defaultLocale;
  private $manager;

  public function __construct(RegistryInterface $registry, $defaultLocale = 'en') {
    $this->defaultLocale = $defaultLocale;
    $this->manager = $registry->getManager();
  }

  public function onKernelRequest(GetResponseEvent $event) {
    $request = $event->getRequest();

    $user = null;
    /** @var UserRepository $userRepository */
    $userRepository = $this->manager->getRepository(User::class);
    if(!empty($request->cookies->get('googleId')))
      $user = $userRepository->loadUserByGoogleId($request->cookies->get('googleId'));
    elseif(!empty($request->cookies->get('clientId')))
      $user = $userRepository->loadUserByUsername($request->cookies->get('clientId'));

    if($user !== null)
      $lang = $user->getLanguage();
    if(!isset($lang) || strlen($lang) <= 0)
      $lang = 'en';

    $request->setLocale($lang);
  }

  public static function getSubscribedEvents() {
    return array(
      // must be registered before (i.e. with a higher priority than) the default Locale listener
      KernelEvents::REQUEST => array(array('onKernelRequest', 20)),
    );
  }
}