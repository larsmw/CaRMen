<?php

namespace CaRMen\Form;

use CaRMen\Entity\Customer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;

#[AsEntityAutocompleteField]
class CustomerAutocompleteField extends AbstractType {

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Customer::class,
            'preload' => false,
            'query_builder' => function (Options $options) {
                return function (EntityRepository $er) use ($options) {
                    
                    $qb = $er->createQueryBuilder('o');

                    $excludedFoods = $options['extra_options']['excluded_foods'] ?? [];
                    if ([] !== $excludedFoods) {
                        $qb->andWhere($qb->expr()->notIn('o.id', $excludedFoods));
                    }
                    //dump($options);
                    //dump($er);
                    //dump($qb->__toString());
                    return $qb;
                };
            }
        ]);
    }

    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }
}
