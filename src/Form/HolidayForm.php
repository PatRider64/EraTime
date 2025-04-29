<?php

namespace App\Form;

use App\Entity\Holiday;
use App\Entity\HolidayType;
use App\Repository\HolidayTypeRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ResetType;

class HolidayForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('typeHalf', EntityType::class, [
                'label' => 'Type de congé',
                'class' => HolidayType::class,
                'mapped' => false,
                'choice_label' => 'name',
                'query_builder' => function (HolidayTypeRepository $htr) {
                    return $htr->createQueryBuilder('ht')
                        ->andWhere("ht.id != 5") //Ancienneté
                        ->andWhere("ht.id != 8") //Home Office
                        ->orderBy('ht.id', 'ASC')
                    ;
                },
                'attr' => [
                    'class' => "form-control"
                ]
            ])
            ->add('type', EntityType::class, [
                'label' => 'Type de congé',
                'class' => HolidayType::class,
                'choice_label' => 'name',
                'query_builder' => function (HolidayTypeRepository $htr) {
                    return $htr->createQueryBuilder('ht')
                        ->andWhere("ht.id != 8") //Home Office
                        ->orderBy('ht.id', 'ASC')
                    ;
                },
                'attr' => [
                    'class' => "form-control"
                ]
            ])
            ->add('dateStartHalf', DateType::class, [
                'label' => 'Date',
                'required' => false,
                'mapped' => false,
                'widget' => 'single_text'
            ])
            ->add('halfHolidaySingle', ChoiceType::class, [
                'label' => false,
                'expanded' => true,
                'multiple' => false,
                'data' => 'Matin',
                'choices' => [
                    'Matin' => 'Matin',
                    'Après-midi' => 'Après-midi'
                ], 
                'label_attr' => [
                    'class' => 'radio-inline',
                ],
                'choice_attr' => function ($choice) {
                    return ['style' => 'width:20px; height:20px'];
                }
            ])
            ->add('dateStartOne', DateType::class, [
                'label' => 'Date',
                'required' => false,
                'mapped' => false,
                'widget' => 'single_text'
            ])
            ->add('dateStartPeriod', DateType::class, [
                'label' => 'Début',
                'required' => false,
                'mapped' => false,
                'widget' => 'single_text'
            ])
            ->add('halfHolidayAfternoonStart', CheckboxType::class, [
                'label' => 'Après-midi seulement',
                'required' => false,
                'attr' => [
                    'style' => 'width:20px; height:20px'
                ]
            ])
            ->add('dateEndPeriod', DateType::class, [
                'label' => 'Fin',
                'required' => false,
                'mapped' => false,
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
            'data_class' => Holiday::class,
        ]);
    }
}
