<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UserEraTimeRepository;
use App\Form\UserEraTimeCreateForm;
use App\Form\UserEraTimeUpdateForm;
use App\Entity\UserEraTime;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/user')]
class UserEraTimeController extends AbstractController
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[Route('/', name: 'app_user', methods: ['GET', 'POST'])]
    public function index(EntityManagerInterface $entityManager, UserEraTimeRepository $userEraTimeRepository, Request $request, 
    PaginatorInterface $paginator, UserPasswordHasherInterface $hasher): Response
    {
        $users = $userEraTimeRepository->findAllCatalog($request->query->get('search'));

        $pagination = $paginator->paginate(
            $users,
            $request->query->getInt('page', 1),
            10
        );

        $form = $this->createForm(UserEraTimeCreateForm::class);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $newUser = new UserEraTime();
            $firstName = $form['firstName']->getData();
            $name = $form['name']->getData();
            $login = $form['login']->getData();
            $password = $form['password']->getData();
            $validatePassword = $form['validatePassword']->getData();
            $email = $form['email']->getData();
            
            if (strlen($firstName) < 2 || strlen($firstName) > 30) {
                $this->addFlash('danger', "Le prénom est incorrect");
            } elseif (strlen($name) < 2 || strlen($name) > 30) {
                $this->addFlash('danger', "Le nom est incorrect");
            } elseif (strlen($login) < 2 || strlen($login) > 30) {
                $this->addFlash('danger', "Le nom de l'identifiant est incorrect");
            } elseif (strlen($password) < 8) {
                $this->addFlash('danger', "Le mot de passe doit avoir une longueur d'au moins 8.");
            } elseif (preg_match_all('/[A-Z]/', $password) == 0) {
                $this->addFlash('danger', "Le mot de passe doit contenir au moins une lettre majuscule.");
            } elseif (preg_match_all('/[a-z]/', $password) == 0) {
                $this->addFlash('danger', "Le mot de passe doit contenir au moins une lettre minuscule.");
            } elseif (preg_match_all('/[0-9]/', $password) == 0) {
                $this->addFlash('danger', "Le mot de passe doit contenir au moins un chiffre.");
            } elseif ($password != $validatePassword) {
                $this->addFlash('danger', "Les deux mots de passe doivent être identiques");
            } elseif (strlen($email) < 4 || strlen($email) > 254 || !preg_match("/^\S+@[\w\d.-]{2,}\.[\w]{2,6}$/iU", $email)) {
                $this->addFlash('danger', "L'email est incorrect");
            } elseif ($userEraTimeRepository->findOneBy(['email' => $email])) {
                $this->addFlash('danger', "Cet adresse email est déjà utilisé.");
            } elseif ($userEraTimeRepository->findOneBy(['login' => $login])) {
                $this->addFlash('danger', "Ce login est déjà utilisé.");
            } else {
                $newUser->setFirstName($firstName);
                $newUser->setName(strtoupper($name));
                $newUser->setLogin($login);
                $newUser->setPassword($hasher->hashPassword($newUser, $password));
                $newUser->setEmail(preg_replace('((?:\n|\r|\t|%0A|%0D|%08|%09)+)i', '', htmlentities($email, ENT_QUOTES, "UTF-8")));
                date_default_timezone_set('Europe/Paris');
                $newUser->setLastActivityDate(date('d/m/Y H:i'));
                $phone = $form['phone']->getData();
                $newUser->setPhone(str_replace(" ", "-", htmlentities($phone, ENT_QUOTES, "UTF-8")));
                $superior = null;
                $superior2 = null;
                $superior3 = null;
                $superiorId = $request->request->get('superior');
                $superior2Id = $request->request->get('superior2');
                $superior3Id = $request->request->get('superior3');

                if ($superiorId != 0) {
                    $superior = $userEraTimeRepository->findOneBy(['id' => $superiorId]);
                }

                if ($superior2Id != 0) {
                    $superior2 = $userEraTimeRepository->findOneBy(['id' => $superior2Id]);
                }

                if ($superior3Id != 0) {
                    $superior3 = $userEraTimeRepository->findOneBy(['id' => $superior3Id]);
                }

                $newUser->setSuperior($superior);
                $newUser->setSuperior2($superior2);
                $newUser->setSuperior3($superior3);
                $newUser->setActive(true);
                $category = $form['category']->getData();
                $newUser->setCategory($category);
                $entityManager->persist($newUser);
                $entityManager->flush();
                
                $this->addFlash('success', 'L\'ajout de l\'utilisateur a été réalisé avec succès');

                return $this->redirectToRoute('app_user', [], Response::HTTP_SEE_OTHER);
            }    
        }

        return $this->render('user/index.html.twig', [
            'users' => $pagination,
            'userCreateForm' => $form->createView()
        ]);
    }

    #[Route('/update/{id}', name: 'app_user_update', methods: ['GET', 'POST'])]
    public function update(EntityManagerInterface $entityManager, UserEraTimeRepository $userEraTimeRepository, Request $request, 
    UserEraTime $user, PaginatorInterface $paginator): Response
    {
        $users = $userEraTimeRepository->findAllCatalog($request->query->get('search'));

        $pagination = $paginator->paginate(
            $users,
            $request->query->getInt('page', 1),
            10
        );

        $form = $this->createForm(UserEraTimeUpdateForm::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $firstName = $form['firstName']->getData();
            $name = $form['name']->getData();
            $login = $form['login']->getData();
            $email = $form['email']->getData();
            if (strlen($firstName) < 2 || strlen($firstName) > 30) {
                $this->addFlash('danger', "Le prénom est incorrect");
            } elseif (strlen($name) < 2 || strlen($name) > 30) {
                $this->addFlash('danger', "Le nom est incorrect");
            } elseif (strlen($login) < 2 || strlen($login) > 30) {
                $this->addFlash('danger', "Le nom de l'identifiant est incorrect");
            } elseif (strlen($email) < 4 || strlen($email) > 254) {
                $this->addFlash('danger', "L'email est incorrect");
            } elseif (!preg_match("/^\S+@[\w\d.-]{2,}\.[\w]{2,6}$/iU", $email)) {
                $this->addFlash('danger', "L'email est incorrect");
            } else {
                $user->setFirstName($firstName);
                $user->setName(strtoupper($name));
                $user->setLogin($login);
                $user->setEmail(preg_replace('((?:\n|\r|\t|%0A|%0D|%08|%09)+)i', '', htmlentities($email, ENT_QUOTES, "UTF-8")));
                $phone = $form['phone']->getData();
                $user->setPhone(str_replace(" ", "-", htmlentities($phone, ENT_QUOTES, "UTF-8")));
                $superior = null;
                $superior2 = null;
                $superior3 = null;
                $superiorId = $request->request->get('superior');
                $superior2Id = $request->request->get('superior2');
                $superior3Id = $request->request->get('superior3');

                if ($superiorId != 0) {
                    $superior = $userEraTimeRepository->findOneBy(['id' => $superiorId]);
                }

                if ($superior2Id != 0) {
                    $superior2 = $userEraTimeRepository->findOneBy(['id' => $superior2Id]);
                }

                if ($superior3Id != 0) {
                    $superior3 = $userEraTimeRepository->findOneBy(['id' => $superior3Id]);
                }

                $user->setSuperior($superior);
                $user->setSuperior2($superior2);
                $user->setSuperior3($superior3);

                $category = $form['category']->getData();
                $userCategory = [];
                foreach ($category as $cate) {
                    array_push($userCategory, $cate);
                }
                $user->setCategory($userCategory);

                $entityManager->flush();
                $this->addFlash('success', 'La mise à jour de l\'utilisateur a été réalisée avec succès.');

                return $this->redirectToRoute('app_user', [], Response::HTTP_SEE_OTHER);
            }    
        }
      
        return $this->render('user/update.html.twig', [
            'users' => $pagination,
            'userUpdateForm' => $form->createView(),
            'user' => $user
        ]);
    }

    #[Route('/delete/{id}', name: 'app_user_delete_api')]
    public function delete(EntityManagerInterface $entityManager, UserEraTime $user): Response
    {
        $user->setActive(false);
        $entityManager->flush();

        $this->addFlash('success', 'La suppression de l\'utilisateur a été réalisée avec succès.');

        return $this->redirectToRoute('app_user', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/superiors', name: 'app_superiors')]
    public function superiors(UserEraTimeRepository $userRepository)
    {
        $listSuperiors = [];
        $superiors = $userRepository->findAllSuperior();

        foreach ($superiors as $key => $superior) {
            $listSuperiors[$key]['id'] = $superior->getId();
            $listSuperiors[$key]['name'] = $superior->getFirstName().' '.$superior->getName();
        }

        $this->logger->info(json_encode($listSuperiors));
        return new JsonResponse($listSuperiors);
    }

    #[Route('/name', name: 'app_user_name')]
    public function getUserName(UserEraTimeRepository $userRepository)
    {
        $userId = $_REQUEST['id'];
        $user = $userRepository->findOneBy(['id' => $userId]);
        $userName = $user->getFirstName()." ".$user->getName();
        $this->logger->info(json_encode($userName));
        return new JsonResponse($userName);
    }

    #[Route('/users', name: 'app_users')]
    public function users(UserEraTimeRepository $userRepository) {
        $users = $userRepository->findAll();
        $newUser = [];

        foreach ($users as $key => $user) {
            $newUser[$key]['id'] = $user->getId();
            $newUser[$key]['name'] = $user->getFirstName().' '.$user->getName();
        }
        $this->logger->info(json_encode($newUser));
        return new JsonResponse($newUser);
    }
}
