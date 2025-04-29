<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Repository\ParameterRepository;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class ParametersUpdateFormType extends AbstractType
{
    public function __construct(ParameterRepository $paremeterRepository)
    {
        $this->parameterRepository = $paremeterRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $smtp = $this->parameterRepository->findOneBy(['name' => 'SMTP'])->getValue();
        $port = $this->parameterRepository->findOneBy(['name' => 'Port'])->getValue();
        $username = $this->parameterRepository->findOneBy(['name' => 'Identifiant'])->getValue();
        $password = $this->parameterRepository->findOneBy(['name' => 'Mot de Passe'])->getValue();
        $expeditorName = $this->parameterRepository->findOneBy(['name' => 'Nom de l\'expéditeur'])->getValue();
        $displayedName = $this->parameterRepository->findOneBy(['name' => 'Nom affiché'])->getValue();

        $builder
            ->add('smtp', TextType::class, [
                'label' => "SMTP",
                'data' => $smtp
            ])
            ->add('port', TextType::class, [
                'label' => "Port",
                'data' => $port
            ])
            ->add('username', TextType::class, [
                'label' => "Identifiant",
                'data' => $username
            ])
            ->add('password', TextType::class, [
                'label' => "Mot de passe",
                'data' => $password
            ])
            ->add('expeditorName', TextType::class, [
                'label' => "Adresse de l'expéditeur",
                'data' => $expeditorName
            ])
            ->add('displayedName', TextType::class, [
                'label' => "Nom affiché",
                'data' => $displayedName
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Mettre à jour',
                'attr' => ['class' => 'button-era94']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
