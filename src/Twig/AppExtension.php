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
            new TwigFunction('day', [$this, 'checkDay']),
            new TwigFunction('activityDate', [$this, 'updateActivityDate']),
            new TwigFunction('id', [$this, 'getUserId']),
        ];
    }

    public function checkDay($date, $year, $month, $i, $id): array
    {
        $conn = $this->doctrine->getConnection();
        $retour = array();
        $retour[0] = "";
        $retour[1] = "";

        $arr_bank_holidays = array();

        $arr_bank_holidays[] = $year . '-01-01 00:00:00';
        $arr_bank_holidays[] = $year . '-05-01 00:00:00';
        $arr_bank_holidays[] = $year . '-05-08 00:00:00';
        $arr_bank_holidays[] = $year . '-07-14 00:00:00';
        $arr_bank_holidays[] = $year . '-08-15 00:00:00';
        $arr_bank_holidays[] = $year . '-11-01 00:00:00';
        $arr_bank_holidays[] = $year . '-11-11 00:00:00';
        $arr_bank_holidays[] = $year . '-12-25 00:00:00';

        $easter = easter_date($year);
        $arr_bank_holidays[] = date($year . '-m-d 00:00:00', $easter + 86400);
        $arr_bank_holidays[] = date($year . '-m-d 00:00:00', $easter + (86400 * 39));
        //$arr_bank_holidays[] = date($year.'-m-d 00:00:00', $easter + (86400*50));

        for ($i = 0; $i <= count($arr_bank_holidays); $i++) {
            if (in_array($date, $arr_bank_holidays)) {
                $retour[0] = "ferie";
                return $retour;
            }
        }

        $dt1 = strtotime($date);
        $dt2 = date("l", $dt1);
        $dt3 = strtolower($dt2);
        if (($dt3 == "saturday") || ($dt3 == "sunday")) {
            $retour[0] = "weekend";
            return $retour;
        }

        $sql = "SELECT type_id, date_start, date_end, status, half_holiday_afternoon_start, half_holiday_morning_end, half_holiday_single
        FROM holiday WHERE users_id = '$id' AND status = 'Acceptée';";
        $stmt = $conn->prepare($sql);
        $resultSet = $stmt->executeQuery();
        $holidays = $resultSet->fetchAllAssociative();

        foreach ($holidays as $holiday) {
            $dateDebut = $holiday['date_start'];
            $dateFin = $holiday['date_end'];
            if ($date == $dateDebut && $date == $dateFin) {
                if ($holiday["half_holiday_single"] == "Matin") {
                    $retour[1] = "demi_m";
                }
                if ($holiday["half_holiday_single"] == "Après-midi") {
                    $retour[1] = "demi_a";
                }
                $retour[0] = $holiday['type_id'];
                return $retour;
            }
            if ($date == $dateDebut) {
                if ($holiday["half_holiday_afternoon_start"] == true) {
                    $retour[1] = "demi_a";
                }
                $retour[0] = $holiday['type_id'];
                return $retour;
            } elseif ($date == $dateFin) {
                if ($holiday["half_holiday_morning_end"] == true) {
                    $retour[1] = "demi_m";
                }
                $retour[0] = $holiday['type_id'];
                return $retour;
            } elseif ($date > $dateDebut && $date < $dateFin) {
                $retour[0] = $holiday['type_id'];
                return $retour;
            }
        }
        return $retour;
    }

    public function updateActivityDate(): void {
        $date = date_create("now", timezone_open("Europe/Paris"));
        $user = $this->security->getUser();
        $user->setLastActivityDate(date_format($date, "d/m/Y H:i:s"));
        $this->entityManager->flush();
    }

    public function getUserId(): int {
        $conn = $this->doctrine->getConnection();
        $sql = "SELECT * FROM public.user_era_time where active = true order by name LIMIT 1;";
        $stmt = $conn->prepare($sql);
        $resultSet = $stmt->executeQuery();
        $firstUser = $resultSet->fetchAllAssociative()[0];
        $id = $firstUser['id'];

        return $id;
    }
}