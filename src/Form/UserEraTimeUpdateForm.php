<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\UserEraTime;
use App\Repository\UserEratimeRepository;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ResetType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserEraTimeUpdateForm extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom'
            ])
            ->add('name', TextType::class, [
                'label' => 'Nom'
            ])
            ->add('email', EmailType::class, [
                'attr' => [
                    'placeholder' => 'identifiant@sys-et-com.fr',
                ]
            ])
            ->add('login', TextType::class, [
                'label' => 'Identifiant',
                'attr' => [
                    'placeholder' => 'prenom.nom',
                ]
            ])
            ->add('phone', TelType::class, [
                'label' => 'Téléphone', 
                'required' => false,
                'attr' => [
                    'placeholder' => '00 00 00 00 00',
                ]
            ])
            ->add("category", ChoiceType::class, [
                'label' => 'Catégorie',
                'required' => false,
                'expanded' => true,
                'multiple' => true,
                'choices' => [
                    "Administratif" => 'Administratif',
                    "Chargé d'affaires" => 'Affaires',
                    "Commercial" => 'Commercial',
                    "Technicien" => 'Technicien',
                    "Support" => 'Support',
                    "Supérieur hiérarchique" => 'Supérieur hiérarchique',
                    "Administrateur" => 'Administrateur',
                    "Administrateur congés" => 'Administrateur congés'
                ],
                'label_attr' => [
                    'class' => 'checkbox-inline',
                ],
                'choice_attr' => [
                    'Administratif' => ['style' => 'width:20px; height:20px'],
                    'Chargé d\'affaires' => ['style' => 'width:20px; height:20px'],
                    'Commercial' => ['style' => 'width:20px; height:20px'],
                    'Technicien' => ['style' => 'width:20px; height:20px'],
                    'Support' => ['style' => 'width:20px; height:20px'],
                    'Supérieur hiérarchique' => ['style' => 'width:20px; height:20px'],
                    'Administrateur' => ['style' => 'width:20px; height:20px', 'class' => 'administrator-checkbox'],
                    'Administrateur congés' => ['style' => 'width:20px; height:20px']
                ]
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Mettre à jour',
                'attr' => ['class' => 'button-era94', 'style' => 'float: left; margin-right: 5px']
            ])
            ;
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserEraTime::class,
        ]);
    }
}
