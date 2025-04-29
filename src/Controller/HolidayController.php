<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;
use App\Entity\Holiday;
use App\Entity\UserEraTime;
use App\Repository\HolidayRepository;
use App\Repository\UserEraTimeRepository;
use App\Form\HolidayForm;
use App\Form\HolidayUsersForm;
use Knp\Component\Pager\PaginatorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Error;

#[Route('/holiday')]
class HolidayController extends AbstractController
{
    private $security;
    private $client;
    private $logger;

    public function __construct(private ManagerRegistry $doctrine, LoggerInterface $logger, HttpClientInterface $client, Security $security)
    {
        $this->security = $security;
        $this->client = $client;
        $this->logger = $logger;
    }

    #[Route('/{section}/{year}/{monthnb}/{userId}', name: 'app_holiday', methods: ['GET', 'POST'])]
    public function index(EntityManagerInterface $entityManager, HolidayRepository $holidayRepository, UserEraTimeRepository $userRepository, 
    Request $request, PaginatorInterface $paginator, $year, $monthnb, $section, $userId): Response
    {
        $users = $userRepository->findAll();
        $userStart = $userRepository->findOneBy(['id' => $userId]);
        $conn = $this->doctrine->getConnection();
        $pagination = '';
        $dateActuelle = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $lastMonth;
    
        switch ($dateActuelle->format('m')) {
            case 1:
                $lastMonth = 'de Décembre';
                break;
            case 2:
                $lastMonth = 'de Janvier';
                break;
            case 3:
                $lastMonth = 'de Février';
                break;
            case 4:
                $lastMonth = 'de Mars';
                break;
            case 5:
                $lastMonth = 'd\'Avril';
                break;
            case 6:
                $lastMonth = 'de Mai';
                break;
            case 7:
                $lastMonth = 'de Juin';
                break;
            case 8:
                $lastMonth = 'de Juillet';
                break;
            case 9:
                $lastMonth = 'de\'Août';
                break;
            case 10:
                $lastMonth = 'de Septembre';
                break;
            case 11:
                $lastMonth = 'd\'Octobre';
                break;
            case 12:
                $lastMonth = 'de Novembre';
        }

        if ($section == 'holidays') {
            $holidays = $holidayRepository->findAllCatalog($request->query->get('search'));

            if (!$holidays) {
                $this->logger->info("no holiday found");
            }

            $pagination = $paginator->paginate(
                $holidays,
                $request->query->getInt('page', 1),
                20
            );
        }

        $formHoliday = $this->createForm(HolidayForm::class);
        $formHoliday->handleRequest($request);

        if ($formHoliday->isSubmitted() && $formHoliday->isValid()) {
            $timeType = $_POST['time_type'];

            if ($timeType == 'half_day') {
                $newHoliday = $formHoliday->getData();
                $date = $formHoliday['dateStartHalf']->getData();
                $type = $formHoliday['typeHalf']->getData();
                $dateString = $date->format('Y-m-d H:i:s');
                $user = $userRepository->findBy(['id' => $request->request->get('users')])[0];
                $id = $user->getId();
                $userId = $id;

                $sql = "SELECT date_start, date_end FROM holiday WHERE users_id = '$id' AND (date_start < '$dateString' OR date_start > '$dateString') AND status != 'Refusée';";
                $stmt = $conn->prepare($sql);
                $resultSet = $stmt->executeQuery();
                $halfDayHolidays = $resultSet->fetchAllAssociative();

                foreach ($halfDayHolidays as $holiday) {
                    $dateStart = date('Y-m-d H:i:s', strtotime($holiday["date_start"]));
                    $dateEnd = date('Y-m-d H:i:s', strtotime($holiday["date_end"]));

                    if ($dateString >= $dateStart && $dateString <= $dateEnd) {
                        $this->addFlash('danger', "La date se retrouve dans une période de congé existant");
                        return $this->redirectToRoute('app_holiday', ['year' => $year, 'monthnb' => $monthnb, 'section' => $section, 'userId' => $userId], Response::HTTP_SEE_OTHER);
                   }
                }

                $holiday = $holidayRepository->findOneBy([
                    'dateStart' => $date,
                    'users' => $id
                ]);

                if ($holiday) {
                    $typeHoliday = $holiday->getType()->getId();

                    if ($type->getId() == $typeHoliday) {
                        $nbJours =  $holiday->getNbTotalDays();
                        $holiday->setNbTotalDays($nbJours + 0.5);
                        $halfDaySingle = $formHoliday['halfHolidaySingle']->getData();

                        if ($holiday->getHalfHolidaySingle() == "Après-midi" && $halfDaySingle == "Matin") {
                            $holiday->setHalfHolidaySingle(null);
                        } elseif ($holiday->getHalfHolidaySingle() == "Matin" && $halfDaySingle == "Après-midi") {
                            $holiday->setHalfHolidaySingle(null);
                        } else {
                            $this->addFlash('danger', "Modification refusée");  
                            return $this->redirectToRoute('app_holiday', ['year' => $year, 'monthnb' => $monthnb, 'section' => $section, 'userId' => $userId], Response::HTTP_SEE_OTHER);
                        }

                        $entityManager->flush();
                    } else {
                        $this->addFlash('danger', "Un congé de type différent existe déjà pour ce jour. Merci de modifier votre demande");
                        return $this->redirectToRoute('app_holiday', ['year' => $year, 'monthnb' => $monthnb, 'section' => $section, 'userId' => $userId], Response::HTTP_SEE_OTHER);
                    }
                } else {
                    $newHoliday->setDateStart($date);
                    $newHoliday->setDateEnd($date);
                    $newHoliday->setUsers($user);
                    $newHoliday->setType($type);
                    $newHoliday->setStatus('Acceptée');
                    $newHoliday->setDateDemande(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
                    $newHoliday->setNbTotalDays(0.5);
                    $halfDaySingle = $formHoliday['halfHolidaySingle']->getData();
                    $newHoliday->setHalfHolidaySingle($halfDaySingle);
                    $entityManager->persist($newHoliday);
                    $entityManager->flush();

                    $this->addFlash('success', 'L\'ajout du congé a été réalisé avec succès');
                    return $this->redirectToRoute('app_holiday', ['year' => $year, 'monthnb' => $monthnb, 'section' => $section, 'userId' => $userId], Response::HTTP_SEE_OTHER);
                }
            } elseif ($timeType == 'one_day') {
                $newHoliday = $formHoliday->getData();
                $date = $formHoliday['dateStartOne']->getData();
                $dateString = $date->format('Y-m-d H:i:s');
                $user = $userRepository->findBy(['id' => $request->request->get('users')])[0];
                $id = $user->getId();
                $userId = $id;

                $sql = "SELECT date_start, date_end FROM holiday WHERE users_id = '$id' AND status != 'Refusée';";
                $stmt = $conn->prepare($sql);
                $resultSet = $stmt->executeQuery();
                $OneDayHolidays = $resultSet->fetchAllAssociative();

                foreach ($OneDayHolidays as $holiday) {
                    $dateStart = date('Y-m-d H:i:s', strtotime($holiday["date_start"]));
                    $dateEnd = date('Y-m-d H:i:s', strtotime($holiday["date_end"]));
                    if ($dateString >= $dateStart && $dateString <= $dateEnd) {
                        $this->addFlash('danger', "La date se retrouve dans une période de congé existant");
                        return $this->redirectToRoute('app_holiday', ['year' => $year, 'monthnb' => $monthnb, 'section' => $section, 'userId' => $userId], Response::HTTP_SEE_OTHER);
                    }
                }

                $type = $formHoliday['type']->getData();
                $newHoliday->setDateStart($date);
                $newHoliday->setDateEnd($date);
                $newHoliday->setUsers($user);
                $newHoliday->setType($type);
                $newHoliday->setStatus('Acceptée');
                $newHoliday->setDateDemande(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
                $newHoliday->setNbTotalDays(1);
                $newHoliday->setHalfHolidaySingle(null);
                $entityManager->persist($newHoliday);
                $entityManager->flush();

                $this->addFlash('success', 'L\'ajout du congé a été réalisé avec succès');
                return $this->redirectToRoute('app_holiday', ['year' => $year, 'monthnb' => $monthnb, 'section' => $section, 'userId' => $userId], Response::HTTP_SEE_OTHER);
            } elseif ($timeType == 'period') {
                $newHoliday = $formHoliday->getData();
                $dateStart = $formHoliday['dateStartPeriod']->getData();
                $dateEnd = $formHoliday['dateEndPeriod']->getData();
                $user = $userRepository->findBy(['id' => $request->request->get('users')])[0];
                $id = $user->getId();
                $userId = $id;
                $dateStartString = $dateStart->format('Y-m-d H:i:s');
                $dateEndString = $dateEnd->format('Y-m-d H:i:s');

                if ($dateEnd < $dateStart) {
                    $this->addFlash('danger', "La date de fin ne peut pas être antérieure à la date de début");
                } else {
                    $arr_bank_holidays = array();
                    $diff_year = $dateEnd->format('Y') - $dateStart->format('Y');

                    for ($i = 0; $i <= $diff_year; $i++) {

                        $yearHolidays = $dateStart->format('Y') + $i;

                        $arr_bank_holidays[] = $yearHolidays . '-01-01';
                        $arr_bank_holidays[] = $yearHolidays . '-05-01';
                        $arr_bank_holidays[] = $yearHolidays . '-05-08';
                        $arr_bank_holidays[] = $yearHolidays . '-07-14';
                        $arr_bank_holidays[] = $yearHolidays . '-08-15';
                        $arr_bank_holidays[] = $yearHolidays . '-11-01';
                        $arr_bank_holidays[] = $yearHolidays . '-11-11';
                        $arr_bank_holidays[] = $yearHolidays . '-12-25';
                    
                        $easter = easter_date($yearHolidays);
                        $arr_bank_holidays[] = date($yearHolidays . '-m-d', $easter + 86400); // Paques
                        $arr_bank_holidays[] = date($yearHolidays . '-m-d', $easter + (86400 * 39)); // Ascension
                        //$arr_bank_holidays[] = date($yearHolidays.'-m-d', $easter + (86400*50)); // Pentecote
                    }

                    $nbDaysOpen = 0;
                    $interval = new \DateInterval('P1D');
                    $dateEnd2 = clone $dateEnd; 
                    $dateEnd2->modify('+1 day');
                    $period = new \DatePeriod($dateStart, $interval, $dateEnd2);

                    foreach ($period as $date) {
                        if (!in_array($date->format('w'), array(0, 6)) && !in_array($date->format('Y-m-d'), $arr_bank_holidays)) {
                            $nbDaysOpen++;
                        }
                        $date = mktime(date('H', $date->format('H')), date('i', $date->format('i')), 
                        date('s', $date->format('s')), date('m', $date->format('m')), date('d', $date->format('d')) + 1, 
                        date('Y', $date->format('Y')));
                    }

                    $halfHolidayAfternoonStart = $formHoliday['halfHolidayAfternoonStart']->getData();

                    if ($halfHolidayAfternoonStart == true) {
                        $nbDaysOpen = $nbDaysOpen - 0.5;
                    }

                    $halfHolidayMorningEnd = $formHoliday['halfHolidayMorningEnd']->getData();

                    if ($halfHolidayMorningEnd == true) {
                        $nbDaysOpen = $nbDaysOpen - 0.5;
                    }

                    $sql = "SELECT * FROM holiday WHERE users_id = '$id' AND status != 'Refusée';";
                    $stmt = $conn->prepare($sql);
                    $resultSet = $stmt->executeQuery();
                    $periodHolidays = $resultSet->fetchAllAssociative();

                    foreach ($periodHolidays as $holiday) {
                        $dateStartHoliday = date('Y-m-d H:i:s', strtotime($holiday["date_start"]));
                        $dateEndHoliday = date('Y-m-d H:i:s', strtotime($holiday["date_end"]));

                        if ($dateStartHoliday <= $dateStartString && $dateStartString <= $dateEndHoliday) {
                            $this->addFlash('danger', "La date de début se retrouve dans une période de congé existant");
                            return $this->redirectToRoute('app_holiday', ['year' => $year, 'monthnb' => $monthnb, 'section' => $section, 'userId' => $userId], Response::HTTP_SEE_OTHER);
                        }
                        if ($dateStartHoliday <= $dateEndString && $dateEndString <= $dateEndHoliday) {
                            $this->addFlash('danger', "La date de fin se retrouve dans une période de congé existant");
                            return $this->redirectToRoute('app_holiday', ['year' => $year, 'monthnb' => $monthnb, 'section' => $section, 'userId' => $userId], Response::HTTP_SEE_OTHER);
                        }
                        if (($dateStartString <= $dateStartHoliday && $dateStartHoliday <= $dateEndString) && ($dateStartString <= $dateEndHoliday && $dateEndHoliday <= $dateEndString)) {
                            $this->addFlash('danger', "Une période de congé existant se trouve entre les deux dates");
                            return $this->redirectToRoute('app_holiday', ['year' => $year, 'monthnb' => $monthnb, 'section' => $section, 'userId' => $userId], Response::HTTP_SEE_OTHER);
                        }
                    }

                    $type = $formHoliday['type']->getData();
                    $newHoliday->setDateStart($dateStart);
                    $newHoliday->setDateEnd($dateEnd);
                    $newHoliday->setUsers($user);
                    $newHoliday->setType($type);
                    $newHoliday->setStatus('Acceptée');
                    $newHoliday->setDateDemande(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
                    $newHoliday->setNbTotalDays($nbDaysOpen);
                    $newHoliday->setHalfHolidayAfternoonStart($halfHolidayAfternoonStart);
                    $newHoliday->setHalfHolidayMorningEnd($halfHolidayMorningEnd);
                    $newHoliday->setHalfHolidaySingle(null);
                    $entityManager->persist($newHoliday);
                    $entityManager->flush();

                    $this->addFlash('success', 'L\'ajout du congé a été réalisé avec succès');
                    return $this->redirectToRoute('app_holiday', ['year' => $year, 'monthnb' => $monthnb, 'section' => $section, 'userId' => $userId], Response::HTTP_SEE_OTHER);
                }
            }
        }

        $formHolidayUsers = $this->createForm(HolidayUsersForm::class);
        $formHolidayUsers->handleRequest($request);

        if ($formHolidayUsers->isSubmitted() && $formHolidayUsers->isValid()) {
            $dateStart = $formHolidayUsers['dateStart']->getData();
            $dateEnd = $formHolidayUsers['dateEnd']->getData();
            $users = $formHolidayUsers['users']->getData();
            $dateStartString = $dateStart->format('Y-m-d H:i:s');
            $dateEndString = $dateEnd->format('Y-m-d H:i:s');

            if (count($users) == 0) {
                $this->addFlash('danger', 'Vous devez sélectionner au moins un utilisateur.');
            } else {
                if ($dateEnd < $dateStart) {
                    $this->addFlash('danger', "La date de fin ne peut pas être antérieure à la date de début");
                } else {
                    $arr_bank_holidays = array();
                    $diff_year = $dateEnd->format('Y') - $dateStart->format('Y');

                    for ($i = 0; $i <= $diff_year; $i++) {
                        $yearHolidays = $dateStart->format('Y') + $i;

                        $arr_bank_holidays[] = $yearHolidays . '-01-01';
                        $arr_bank_holidays[] = $yearHolidays . '-05-01';
                        $arr_bank_holidays[] = $yearHolidays . '-05-08';
                        $arr_bank_holidays[] = $yearHolidays . '-07-14';
                        $arr_bank_holidays[] = $yearHolidays . '-08-15';
                        $arr_bank_holidays[] = $yearHolidays . '-11-01';
                        $arr_bank_holidays[] = $yearHolidays . '-11-11';
                        $arr_bank_holidays[] = $yearHolidays . '-12-25';

                        $easter = easter_date($yearHolidays);
                        $arr_bank_holidays[] = date($yearHolidays . '-m-d', $easter + 86400); // Paques
                        $arr_bank_holidays[] = date($yearHolidays . '-m-d', $easter + (86400 * 39)); // Ascension
                        //$arr_bank_holidays[] = date($yearHolidays.'-m-d', $easter + (86400*50)); // Pentecote
                    }

                    $nbDaysOpen = 0;
                    $interval = new \DateInterval('P1D');
                    $dateEnd2 = clone $dateEnd; 
                    $dateEnd2->modify('+1 day');
                    $period = new \DatePeriod($dateStart, $interval, $dateEnd2);

                    foreach ($period as $date) {
                        if (!in_array($date->format('w'), array(0, 6)) && !in_array($date->format('Y-m-d'), $arr_bank_holidays)) {
                            $nbDaysOpen++;
                        }
                        $date = mktime(date('H', $date->format('H')), date('i', $date->format('i')), 
                        date('s', $date->format('s')), date('m', $date->format('m')), date('d', $date->format('d')) + 1, 
                        date('Y', $date->format('Y')));
                    }

                    $halfHolidayAfternoonStart = $formHolidayUsers['halfHolidayAfternoonStart']->getData();

                    if ($halfHolidayAfternoonStart == true) {
                        $nbDaysOpen = $nbDaysOpen - 0.5;
                    }

                    $halfHolidayMorningEnd = $formHolidayUsers['halfHolidayMorningEnd']->getData();

                    if ($halfHolidayMorningEnd == true) {
                        $nbDaysOpen = $nbDaysOpen - 0.5;
                    }

                    foreach ($users as $user) {
                        $id = $user->getId();

                        $sql = "SELECT * FROM holiday WHERE users_id = '$id' AND status != 'Refusée';";
                        $stmt = $conn->prepare($sql);
                        $resultSet = $stmt->executeQuery();
                        $periodHolidays = $resultSet->fetchAllAssociative();
                        $errorPeriod = false;

                        foreach ($periodHolidays as $holiday) {
                            $dateStartHoliday = date('Y-m-d H:i:s', strtotime($holiday["date_start"]));
                            $dateEndHoliday = date('Y-m-d H:i:s', strtotime($holiday["date_end"]));

                            if ($dateStartHoliday <= $dateStartString && $dateStartString <= $dateEndHoliday) {
                                $this->addFlash('danger', "La date de début se retrouve dans une période de congé existant pour l'utilisateur ".$user->getFirstName()." ".$user->getName());
                                $errorPeriod = true;
                            } elseif ($dateStartHoliday <= $dateEndString && $dateEndString <= $dateEndHoliday) {
                                $this->addFlash('danger', "La date de fin se retrouve dans une période de congé existant pour l'utilisateur ".$user->getFirstName()." ".$user->getName());
                                $errorPeriod = true;
                            } elseif (($dateStartString <= $dateStartHoliday && $dateStartHoliday <= $dateEndString) && ($dateStartString <= $dateEndHoliday && $dateEndHoliday <= $dateEndString)) {
                                $this->addFlash('danger', "Une période de congé existant se trouve entre les deux dates pour l'utilisateur ".$user->getFirstName()." ".$user->getName());
                                $errorPeriod = true;
                            }
                        }

                        if (!$errorPeriod) {
                            $type = $formHolidayUsers['type']->getData();

                            $newHoliday = new Holiday();
                            $newHoliday->setDateStart($dateStart);
                            $newHoliday->setDateEnd($dateEnd);
                            $newHoliday->setUsers($user);
                            $newHoliday->setType($type);
                            $newHoliday->setStatus('Acceptée');
                            $newHoliday->setDateDemande(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
                            $newHoliday->setNbTotalDays($nbDaysOpen);
                            $newHoliday->setHalfHolidayAfternoonStart($halfHolidayAfternoonStart);
                            $newHoliday->setHalfHolidayMorningEnd($halfHolidayMorningEnd);
                            $newHoliday->setHalfHolidaySingle(null);
                            $entityManager->persist($newHoliday);
                            
                            $this->addFlash('success', 'L\'ajout du congé pour l\'utilisateur '.$user->getFirstName()." ".$user->getName().' a été réalisé avec succès');
                        }
                    }
                    
                    $entityManager->flush();

                    return $this->redirectToRoute('app_holiday', ['year' => $year, 'monthnb' => $monthnb, 'section' => $section, 'userId' => $userId], Response::HTTP_SEE_OTHER);
                }
            }
        }

        if ($monthnb <= 0) {
            $monthnb = 12;
            $year = $year - 1;
        } elseif ($monthnb > 12) {
            $monthnb = 1;
            $year = $year + 1;
        }

        if (strlen($monthnb) < 2) {
            $monthnb = '0' . $monthnb;
        }

        switch ($monthnb) {
            case 1:
                $month = 'Janvier';
                break;
            case 2:
                $month = 'Février';
                break;
            case 3:
                $month = 'Mars';
                break;
            case 4:
                $month = 'Avril';
                break;
            case 5:
                $month = 'Mai';
                break;
            case 6:
                $month = 'Juin';
                break;
            case 7:
                $month = 'Juillet';
                break;
            case 8:
                $month = 'Août';
                break;
            case 9:
                $month = 'Septembre';
                break;
            case 10:
                $month = 'Octobre';
                break;
            case 11:
                $month = 'Novembre';
                break;
            case 12:
                $month = 'Décembre';
                break;
        }

        $jours = array("Di", "Lu", "Ma", "Me", "Je", "Ve", "Sa");
        $nbCols = date("t", mktime(0, 0, 0, $monthnb, 1, $year));
        $dayName = date("l", mktime(0, 0, 0, $monthnb, 1, $year));

        switch ($dayName) {
            case 'Monday':
                $day = 1;
                break;
            case 'Tuesday':
                $day = 2;
                break;
            case 'Wednesday':
                $day = 3;
                break;
            case 'Thursday':
                $day = 4;
                break;
            case 'Friday':
                $day = 5;
                break;
            case 'Saturday':
                $day = 6;
                break;
            case 'Sunday':
                $day = 0;
                break;
        }

        return $this->render('holiday/index.html.twig', [
            'section' => $section,
            'holidayForm' => $formHoliday->createView(),
            'holidayUsersForm' => $formHolidayUsers->createView(),
            'users' => $users,
            'userStart' => $userStart,
            'table' => $pagination,
            'year' => $year,
            'month' => $month,
            'monthnb' => $monthnb,
            'userId' => $userId,
            'nbCols' => $nbCols,
            'jours' => $jours,
            'day' => $day,
            'lastMonth' => $lastMonth
        ]);
    }

    #[Route('delete/{id}/{year}/{monthnb}/{userId}', name: 'app_holiday_delete', methods: ['POST'])]
    public function delete(EntityManagerInterface $entityManager, Request $request, Holiday $holiday, $year, $monthnb)
    {
        if ($this->isCsrfTokenValid('delete'.$holiday->getId(), $request->request->get('_token'))) {
            $entityManager->remove($holiday);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_holiday', ['year' => $year, 'monthnb' => $monthnb, 'section' => 'holidays', 'userId' => $userId], Response::HTTP_SEE_OTHER);
    }

    #[Route('/nbDays', name: 'app_holiday_nb_days')]
    public function nbDays()
    {
        $dateStart = new \DateTime($_REQUEST['dateStart']);
        $dateEnd = new \DateTime($_REQUEST['dateEnd']);
        $halfHolidayAfternoonStart = $_REQUEST['halfHolidayAfternoonStart'];
        $halfHolidayMorningEnd = $_REQUEST['halfHolidayMorningEnd'];
        $nbDays = 0;

        $arr_bank_holidays = array();
        $diff_year = $dateEnd->format('Y') - $dateStart->format('Y');

        for ($i = 0; $i <= $diff_year; $i++) {
            $year = $dateStart->format('Y') + $i;

            $arr_bank_holidays[] = $year . '-01-01';
            $arr_bank_holidays[] = $year . '-05-01';
            $arr_bank_holidays[] = $year . '-05-08';
            $arr_bank_holidays[] = $year . '-07-14';
            $arr_bank_holidays[] = $year . '-08-15';
            $arr_bank_holidays[] = $year . '-11-01';
            $arr_bank_holidays[] = $year . '-11-11';
            $arr_bank_holidays[] = $year . '-12-25';
                
            $easter = easter_date($year);
            $arr_bank_holidays[] = date($year . '-m-d', $easter + 86400); // Paques
            $arr_bank_holidays[] = date($year . '-m-d', $easter + (86400 * 39)); // Ascension
            //$arr_bank_holidays[] = date($year.'-m-d', $easter + (86400*50)); // Pentecote
        }

        $interval = new \DateInterval('P1D');
        $dateEnd2 = clone $dateEnd; 
        $dateEnd2->modify('+1 day');
        $period = new \DatePeriod($dateStart, $interval, $dateEnd2);

        foreach ($period as $date) {
            if (!in_array($date->format('w'), array(0, 6)) && !in_array($date->format('Y-m-d'), $arr_bank_holidays)) {
                $nbDays++;
            }
            $date = mktime(date('H', $date->format('H')), date('i', $date->format('i')), 
            date('s', $date->format('s')), date('m', $date->format('m')), date('d', $date->format('d')) + 1, 
            date('Y', $date->format('Y')));
        }

        if ($halfHolidayAfternoonStart == 'true') {
            $nbDays -= 0.5;
        }
        if ($halfHolidayMorningEnd == 'true') {
            $nbDays -= 0.5;
        }

        return new JsonResponse($nbDays);
    }

    #[Route('/userId', name: 'app_user_id')]
    public function userId(UserEraTimeRepository $userRepository, HolidayRepository $holidayRepository) {
        $id = $_REQUEST['id'];
        $year = $_REQUEST['year'];
        $monthnb = $_REQUEST['monthnb'];
        $nbDays = $_REQUEST['nbDays'];
        $users = $userRepository->findBy(['id' => $id])[0];
        $user = [];

        $user['id'] = $users->getId();
        $user['name'] = $users->getName();
        $user['firstName'] = $users->getFirstName();
        $user['days'] = [];

        array_push($user['days'], ['color' => '', 'half_day' => '']);

        $arr_bank_holidays = [];

        $arr_bank_holidays[] = $year . '-01-01';
        $arr_bank_holidays[] = $year . '-05-01';
        $arr_bank_holidays[] = $year . '-05-08';
        $arr_bank_holidays[] = $year . '-07-14';
        $arr_bank_holidays[] = $year . '-08-15';
        $arr_bank_holidays[] = $year . '-11-01';
        $arr_bank_holidays[] = $year . '-11-11';
        $arr_bank_holidays[] = $year . '-12-25';

        $easter = easter_date($year);
        $arr_bank_holidays[] = date($year . '-m-d', $easter + 86400);
        $arr_bank_holidays[] = date($year . '-m-d', $easter + (86400 * 39));
        //$arr_bank_holidays[] = date($year.'-m-d', $easter + (86400*50));

        for ($i = 1; $i <= $nbDays; $i++) {
            $type = "";
            $color = "";
            $half_day = "";
            if ($i < 10) {
                $day = '0'.$i;
            } else {
                $day = $i;
            }

            if ($monthnb <= 0) {
                $monthnb = 12;
                $year = $year - 1;
            } elseif ($monthnb > 12) {
                $monthnb = 1;
                $year = $year + 1;
            }
    
            if (strlen($monthnb) < 2) {
                $monthnb = '0'.$monthnb;
            }

            $date = $year.'-'.$monthnb.'-'.$day;

            if (in_array($date, $arr_bank_holidays)) {
                $color = 'blue';
            } else {
                $dt1 = strtotime($date);
                $dt2 = date("l", $dt1);
                $dt3 = strtolower($dt2);

                if (($dt3 == "saturday") || ($dt3 == "sunday")) {
                    $color = 'lightslategray';
                } else {
                    $holidays = $holidayRepository->findBy(['users' => $id, 'status' => 'Acceptée']);
                    
                    foreach ($holidays as $holiday) {
                        $dateDebut = $holiday->getDateStart()->format('Y-m-d');
                        $dateFin = $holiday->getDateEnd()->format('Y-m-d');
                        
                        if ($date == $dateDebut && $date == $dateFin) {
                            if ($holiday->getHalfHolidaySingle() == "Matin") {
                                $half_day = "M";
                            }
                        
                            if ($holiday->getHalfHolidaySingle() == "Après-midi") {
                                $half_day = "A";
                            }
                            $type = $holiday->getType()->getId();
                        } elseif ($date == $dateDebut) {
                            if ($holiday->isHalfHolidayAfternoonStart() == true) {
                                $half_day = "A";
                            }
                            $type = $holiday->getType()->getId();
                        } elseif ($date == $dateFin) {
                            if ($holiday->isHalfHolidayMorningEnd() == true) {
                                $half_day = "M";
                            }
                            $type = $holiday->getType()->getId();
                        } elseif ($date > $dateDebut && $date < $dateFin) {
                            $type = $holiday->getType()->getId();
                        }
                    }
                    switch ($type) {
                        case 1:
                            $color = '#E67E30';
                            break;
                        case 2:
                        case 3:
                            $color = 'green';
                            break;
                        case 4:
                            $color = '#463F32';
                            break;
                        case 5: 
                            $color = '#A10684';
                            break;
                        case 6:
                            $color = '#33FFFF';
                            break;
                        case 7:
                            $color = '#BCFF00';
                            break;
                        case 8:
                            $color = '#9933FF';
                            break;
                        case 9:
                            $color = 'red';
                    }
                }
            }
            array_push($user['days'], ['color' => $color, 'half_day' => $half_day]);
        }

        $this->logger->info(json_encode($user));
        return new JsonResponse($user);
    }
}
