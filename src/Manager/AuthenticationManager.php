<?php

namespace BaseBundle\Manager;

use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class AuthenticationManager.
 */
class AuthenticationManager
{
    protected $om;
    protected $passwordEnconder;
    protected $jwtAuthenticationEncoder;
    protected $jwtTimeExpiration;
    protected $userClass;

    public function __construct(ObjectManager $om, $passwordEnconder)
    {
        $this->om = $om;
        $this->passwordEnconder = $passwordEnconder;
    }

    public function getUser($username)
    {
        $qb = $this->getOm()->createQueryBuilder('u');

        $select = $qb->select('u')
            ->from($this->getUserClass(), 'u')
            ->where('u.username = :username OR u.email = :email')
            ->setParameter('username', $username)
            ->setParameter('email', $username);

        $user = $select->getQuery()
            ->getOneOrNullResult();

        return $user;
    }

    public function hasUser(\Symfony\Component\Security\Core\User\UserInterface $user)
    {
        $qb = $this->getOm()->createQueryBuilder('u');

        $select = $qb->select('u')
            ->from($this->getUserClass(), 'u')
            ->where('u.username = :username OR u.email = :email')
            ->setParameter('username', $user->getUsername())
            ->setParameter('email', $user->getEmail());

        $user = $select->getQuery()
            ->getOneOrNullResult();

        return $user;
    }

    public function createUser(\Symfony\Component\Security\Core\User\UserInterface $user)
    {
        $checkOldUser = $this->hasUser($user);

        if ($checkOldUser) {
            throw new \Exception('User already exists.');
        }

        $encoder = $this->getPasswordEnconder()->encodePassword($user, $user->getPassword());

        $user->setPassword($encoder);
        $this->om->persist($user);
        $this->getOm()->flush();

        $this->getOm()->refresh($user);

        return $user;
    }

    public function validatePassword(\Symfony\Component\Security\Core\User\UserInterface $user, $password)
    {
        $isValid = $this->getPasswordEnconder()
            ->isPasswordValid($user, $password);

        return $isValid;
    }

    public function encodeJwtUserAuthentication(\Symfony\Component\Security\Core\User\UserInterface $user)
    {
        $token = $this->getJwtAuthenticationEncoder()
            ->encode([
                         'username' => $user->getUsername(),
                         'exp' => time() + $this->getJwtTimeExpiration(),
                     ]);

        return $token;
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectManager
     */
    public function getOm()
    {
        return $this->om;
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $om
     *
     * @return AuthenticationManager
     */
    public function setOm(ObjectManager $om)
    {
        $this->om = $om;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPasswordEnconder()
    {
        return $this->passwordEnconder;
    }

    /**
     * @param mixed $passwordEnconder
     *
     * @return AuthenticationManager
     */
    public function setPasswordEnconder($passwordEnconder)
    {
        $this->passwordEnconder = $passwordEnconder;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getUserClass()
    {
        return $this->userClass;
    }

    /**
     * @param mixed $userClass
     *
     * @return AuthenticationManager
     */
    public function setUserClass($userClass)
    {
        $this->userClass = $userClass;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getJwtAuthenticationEncoder()
    {
        return $this->jwtAuthenticationEncoder;
    }

    /**
     * @param mixed $jwtAuthenticationEncoder
     *
     * @return AuthenticationManager
     */
    public function setJwtAuthenticationEncoder($jwtAuthenticationEncoder)
    {
        $this->jwtAuthenticationEncoder = $jwtAuthenticationEncoder;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getJwtTimeExpiration()
    {
        return $this->jwtTimeExpiration;
    }

    /**
     * @param mixed $jwtTimeExpiration
     *
     * @return AuthenticationManager
     */
    public function setJwtTimeExpiration($jwtTimeExpiration)
    {
        $this->jwtTimeExpiration = $jwtTimeExpiration;

        return $this;
    }
}
