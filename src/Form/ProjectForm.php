<?php

namespace CaRMen\Form;

use CaRMen\Entity\Customer;
use CaRMen\Entity\Project;
use CaRMen\Form\CustomerAutocompleteField;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('WinnerFile')
            ->add('Customer', CustomerAutocompleteField::class, [
              'extra_options' => [
                    'excluded_foods' => [1],
              ],
              'class' => 'CaRMen\Entity\Customer'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Project::class,
        ]);
    }
}
