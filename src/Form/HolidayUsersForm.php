<?php

namespace App\Form;

use App\Entity\Holiday;
use App\Entity\HolidayType;
use App\Entity\UserEraTime;
use App\Repository\UserEraTimeRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ResetType;

class HolidayUsersForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', EntityType::class, [
                'label' => 'Type de congé',
                'class' => HolidayType::class,
                'choice_label' => 'name',
                'attr' => [
                    'class' => "form-control"
                ]
            ])
            ->add('selectAll', CheckboxType::class, [
                'label' => 'Tout sélectionner',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'selectAllCheckboxesAdd',
                    'style' => 'width:20px; height:20px'
                ],
            ])
            ->add('users', EntityType::class, [
                'label' => 'Utilisateurs',
                'class' => UserEraTime::class,
                'mapped' => false,
                'expanded' => true,
                'multiple' => true,
                'query_builder' => function (UserEraTimeRepository $ur) {
                    return $ur->createQueryBuilder('u')
                        ->andWhere('u.active = true')
                        ->orderBy('u.name', 'ASC')
                    ;
                },
                'choice_label' => function ($user) {
                    return $user->getFirstName() . ' ' . $user->getName();
                },
                'choice_attr' => function ($choice) {
                    return ['class' => 'form-check-add',
                    'style' => 'width:20px; height:20px'];
                }
            ])
            ->add('dateStart', DateType::class, [
                'label' => 'Début',
                'widget' => 'single_text'
            ])
            ->add('halfHolidayAfternoonStart', CheckboxType::class, [
                'label' => 'Après-midi seulement',
                'required' => false,
                'attr' => [
                    'style' => 'width:20px; height:20px'
                ]
            ])
            ->add('dateEnd', DateType::class, [
                'label' => 'Fin',
                'widget' => 'single_text'
            ])
            ->add('halfHolidayMorningEnd', CheckboxType::class, [
                'label' => 'Matin seulement',
                'required' => false,
                'attr' => [
                    'style' => 'width:20px; height:20px'
                ]
            ])
            ->add('cancel', ResetType::class, [
                'label' => 'Annuler',
                'attr' => array('style' => 'float: left; margin-right: 5px')
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Ajouter',
                'attr' => ['class' => 'button-era94']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
