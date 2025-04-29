<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Form\ResetPasswordForm;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\SecurityBundle\Security;
use App\Entity\UserSysTime;
use App\Repository\ParameterRepository;
use Symfony\Component\Mime\Address;
use Error;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Psr\Log\LoggerInterface;

class SecurityController extends AbstractController
{
    private $logger;
    private $client;
    private $security;
    
    public function __construct(private ManagerRegistry $doctrine, HttpClientInterface $client, LoggerInterface $logger, Security $security)
    {
        $this->client = $client;
        $this->logger = $logger;
        $this->security = $security;
    }

    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        // throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/resetpassword', name: 'app_reset_password')]
    public function resetPassword(UserPasswordHasherInterface $userPasswordHasherInterface, ParameterRepository $parameterRepository,
    Request $request): Response
    {
        $form = $this->createForm(ResetPasswordForm::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $smtp = $parameterRepository->findOneBy(['name' => 'SMTP'])->getValue();
            $port = $parameterRepository->findOneBy(['name' => 'Port'])->getValue();
            $username = $parameterRepository->findOneBy(['name' => 'Identifiant'])->getValue();
            $password = $parameterRepository->findOneBy(['name' => 'Mot de Passe'])->getValue();
            $expeditorName = $parameterRepository->findOneBy(['name' => 'Nom de l\'expéditeur'])->getValue();
            $displayedName = $parameterRepository->findOneBy(['name' => 'Nom affiché'])->getValue();
            $dsn = "smtp://".$username.":".$password."@".$smtp.":".$port;
            $transport = Transport::fromDsn($dsn);
            $mailer = new Mailer($transport);

            $this->logger->info('test');
            //request email
            $emailadress = $form['email']->getData();

            if (!$emailadress) {
                $this->addFlash('danger', "Veuillez saisir votre e-mail");
                return $this->redirectToRoute('app_reset_password');
            }
            $this->logger->info("resetPassword() - email : ".$emailadress);

            //generate random password
            $plainTextPassword = $this->generateRandomString(8);
            $this->logger->info("resetPassword() - plainTextPassword : ".$plainTextPassword);
            //hash password
            $login = $this->generateNewPassword($userPasswordHasherInterface, $emailadress, $plainTextPassword);
            //$this->logger->info("resetPassword() - hashedPassword : ".$hashedPassword);
            
            //Sent email with new password
            $email = (new Email())
                //->from('support.technique@sys-et-com.eu')
                ->from(new Address($expeditorName, $displayedName))
                ->to($emailadress)
                ->priority(Email::PRIORITY_HIGH)
                ->subject('EraTime : Réinitialisation de votre mot de passe')
                ->embedFromPath('../public/images/logo-era94.png', 'footer-signature', 'image/gif')
                ->html(
                    "<p>Bonjour, </p><p>Nous vous confirmons que vous avez réinitialisé le mot de passe de votre compte EraTime.</p>"
                    ."<h2>Votre identifiant : ".$login."</h2>"
                    ."<h2>Votre nouveau mot de passe : ".$plainTextPassword."</h2>"
                    ."<p>Une fois identifié avec votre nouveau mot de passe, vous pouvez le changer en cliquant sur votre nom en haut à droite de l'écran puis en choisissant Changer de mot de passe</p>"
                    ."<p>Si vous n'êtes pas à l'origine de cette demande, veuillez contacter votre administrateur.</p>"
                    ."<p>Cordialement,</p>"
                    ."<p>L'équipe ERA94.</p>"
                    ."<img src='cid:footer-signature' alt='signature' />"
                    ."<p><a href='tel:0474030587'>04 74 03 05 87</a><br>
                        SYS&COM - REGULATION, AUTOMATISMES ET GTB <br>
                        228, rue de l’Ecossais <br>
                        69400 Limas
                    </p>"
                )
            ;
            try {
                $mailer->send($email);
                return $this->redirectToRoute('app_login');
            } catch (TransportExceptionInterface $e) {
                $this->logger->error("resetPassword() - Erreur : ".$e);
                throw new Error($e);
            }
        }
        
        return $this->render("security/init_password.html.twig", [
            'resetPasswordForm' => $form->createView()
        ]);
    }

    #[Route('/changepasswordform', name: 'app_change_password')]
    public function changePassword() {
        return $this->render("security/change_password.html.twig") ;
    }

    #[Route('/changepassword', name: 'app_change_password_api', methods: ['POST'])]
    public function changePasswordApi(EntityManagerInterface $entityManager, UserPasswordHasherInterface $hasher, Request $request)
    {
        $user = $this->security->getUser();
        $newPassword = $request->request->get('newPassword');
        $validatePassword = $request->request->get('validatePassword');

        if (strlen($newPassword) < 8) {
            $this->addFlash('danger', "Le mot de passe doit avoir une longueur d'au moins 8.");
            return $this->redirectToRoute('app_change_password', [], Response::HTTP_SEE_OTHER);
        } elseif (preg_match_all('/[A-Z]/', $newPassword) == 0) {
            $this->addFlash('danger', "Le mot de passe doit contenir au moins une lettre majuscule.");
            return $this->redirectToRoute('app_change_password', [], Response::HTTP_SEE_OTHER);
        } elseif (preg_match_all('/[a-z]/', $newPassword) == 0) {
            $this->addFlash('danger', "Le mot de passe doit contenir au moins une lettre minuscule.");
            return $this->redirectToRoute('app_change_password', [], Response::HTTP_SEE_OTHER);
        } elseif (preg_match_all('/[0-9]/', $newPassword) == 0) {
            $this->addFlash('danger', "Le mot de passe doit contenir au moins un chiffre.");
            return $this->redirectToRoute('app_change_password', [], Response::HTTP_SEE_OTHER);
        } elseif ($newPassword != $validatePassword) {
            $this->addFlash('danger', "Les deux mots de passe doivent être identiques.");
            return $this->redirectToRoute('app_change_password', [], Response::HTTP_SEE_OTHER);
        }
        $user->setPassword($hasher->hashPassword($user, $newPassword));

        $entityManager->flush();

        $this->addFlash('success', "Le changement du mot de passe a été réalisé avec succès.");
        return $this->redirectToRoute('app_change_password', [], Response::HTTP_SEE_OTHER);
    }

    public function generateNewPassword(UserPasswordHasherInterface $userPasswordHasherInterface, String $email, String $plainTextPassword)
    {
        $userEmailToUpdate = $email;
        $hashedPassword = "";
        $user = $this->doctrine->getRepository(UserSysTime::class)->findOneBy(['email' => $userEmailToUpdate]);
        if($user){
            // encode the plain password
            $userTemp = $user->setPassword(
                $userPasswordHasherInterface->hashPassword(
                    $user,
                    $plainTextPassword
                )
            );
            $entityManager = $this->doctrine->getManager();
            $entityManager->persist($user);
            $entityManager->flush();
            // do anything else you need here, like send an email

            $this->logger->info("generateNewPassword() - Le mot de passe a été mis à jour.");
            $this->addFlash(
                "success",
                "Le mot de passe a été mis à jour, merci de vérifier vos mails."
            );
        }else{
            $this->logger->error("generateNewPassword() - Aucun utilisateur trouvé avec l'email : ".$userEmailToUpdate);
            $this->addFlash(
                "warning",
                "Aucun utilisateur trouvé avec l'email : ".$userEmailToUpdate
            );
        }
        $hashedPassword = $userTemp->getPassword();
        return $user->getLogin();
    }

    public function generateRandomString(int $lenght)
    {
        //return random string of lenght $lenght
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $plainTextPassword = substr(str_shuffle($characters), 0, $lenght);
        //$this->logger->info("generateRandomString() - charactersLength : ".$charactersLength);
        return $plainTextPassword;
    }
}
