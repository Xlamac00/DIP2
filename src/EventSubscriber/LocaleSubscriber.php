<?php
namespace App\EventSubscriber;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

//http://symfony.com/doc/master/translation/locale.html
//http://symfony.com/doc/master/session/locale_sticky_session.html
class LocaleSubscriber implements EventSubscriberInterface {
  private $defaultLocale;

  public function __construct($defaultLocale = 'en') {
    $this->defaultLocale = $defaultLocale;
  }

  public function onKernelRequest(GetResponseEvent $event) {
    $request = $event->getRequest();
//    if (!$request->hasPreviousSession()) {
//      return;
//    }


    $request->setLocale('cz');

//    // try to see if the locale has been set as a _locale routing parameter
//    if ($locale = $request->attributes->get('_locale')) {
//      $request->getSession()->set('_locale', $locale);
//    } else {
//      // if no explicit locale has been set on this request, use one from the session
//      $request->setLocale($request->getSession()->get('_locale', $this->defaultLocale));
//    }
  }

  public static function getSubscribedEvents() {
    return array(
      // must be registered before (i.e. with a higher priority than) the default Locale listener
      KernelEvents::REQUEST => array(array('onKernelRequest', 20)),
    );
  }
}