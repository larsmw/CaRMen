<?php

namespace CaRMen\Form;

use CaRMen\Entity\Customer;
use CaRMen\Entity\Project;
use CaRMen\Form\CustomerAutocompleteField;
use CaRMen\Form\DataTransformer\StringToFileTransformer;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectForm extends AbstractType
{
    public function __construct(private StringToFileTransformer $transformer) {}
    
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('WinnerFile')
            ->add('WinnerFileName', FileType::class)
            ->add('customer', CustomerAutocompleteField::class)
        ;
        $builder->get('WinnerFile')->addModelTransformer($this->transformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Project::class,
        ]);
    }
}
