<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ParameterRepository;
use App\Form\ParametersUpdateFormType;

class ParametersController extends AbstractController
{
    #[Route('/parameters', name: 'app_parameters')]
    public function index(EntityManagerInterface $entityManager, ParameterRepository $parameterRepository, 
    Request $request): Response
    {
        $form = $this->createForm(ParametersUpdateFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newSmtp = $form['smtp'] ->getData();
            $newPort = $form['port'] ->getData();
            $newUsername = $form['username'] ->getData();
            $newPassword = $form['password'] ->getData();
            $newExpeditorName = $form['expeditorName'] ->getData();
            $newDisplayedName = $form['displayedName'] ->getData();

            $smtp = $parameterRepository->findOneBy(['name' => 'SMTP']);
            $port = $parameterRepository->findOneBy(['name' => 'Port']);
            $username = $parameterRepository->findOneBy(['name' => 'Identifiant']);
            $password = $parameterRepository->findOneBy(['name' => 'Mot de Passe']);
            $expeditorName = $parameterRepository->findOneBy(['name' => 'Nom de l\'expéditeur']);
            $displayedName = $parameterRepository->findOneBy(['name' => 'Nom affiché']);

            $smtp->setValue($newSmtp);
            $port->setValue($newPort);
            $username->setValue($newUsername);
            $password->setValue($newPassword);
            $expeditorName->setValue($newExpeditorName);
            $displayedName->setValue($newDisplayedName);

            $entityManager->flush();

            $this->addFlash('success', 'La mise à jour des paramètres a été réalisé avec succès');

            return $this->redirectToRoute('app_parameters', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('parameters/index.html.twig', [
            'parameterForm' => $form->createView()
        ]);
    }
}
