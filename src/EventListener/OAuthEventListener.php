<?php
/**
 * Created by PhpStorm.
 * User: diego
 * Date: 19/04/16
 * Time: 00:07
 */

namespace BaseBundle\EventListener;

use FOS\OAuthServerBundle\Event\OAuthEvent;

class OAuthEventListener
{
    private $container;
    private $em;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->em = $this->container->get('doctrine')->getManager();
    }

    public function onPreAuthorizationProcess(OAuthEvent $event)
    {
        var_dump($event);exit;
        if ($user = $this->getUser($event)) {
            $event->setAuthorizedClient(
                $user->isAuthorizedClient($event->getClient())
            );
        }
    }

    public function onPostAuthorizationProcess(OAuthEvent $event)
    {

        if ($event->isAuthorizedClient()) {
            if (null !== $client = $event->getClient()) {
                $user = $this->getUser($event);
                $user->addClient($client);
                $this->em($user);
                $this->em->flush();
            }
        }
    }

    protected function getUser(OAuthEvent $event)
    {
        var_dump($event->getUser());exit;
        return $this->em->getRepository('AppBundle:User')->findOneByUsername($event->getUser()->getUsername());
    }
}
