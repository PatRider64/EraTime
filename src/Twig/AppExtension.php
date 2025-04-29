<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;

class AppExtension extends AbstractExtension
{
    private $entityManager;

    public function __construct(private ManagerRegistry $doctrine, Security $security, EntityManagerInterface $entityManager)
    {
        $this->security = $security;
        $this->entityManager = $entityManager;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('activityDate', [$this, 'updateActivityDate']),
        ];
    }

    public function updateActivityDate(): void {
        $date = date_create("now", timezone_open("Europe/Paris"));
        $user = $this->security->getUser();
        $user->setLastActivityDate(date_format($date, "d/m/Y H:i:s"));
        $this->entityManager->flush();
    }
}