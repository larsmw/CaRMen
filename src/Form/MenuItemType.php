<?php

namespace CaRMen\Form;

use CaRMen\Entity\MenuItem;
use CaRMen\Repository\MenuItemRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MenuItemType extends AbstractType
{
    public function __construct(private MenuItemRepository $menuItemRepository) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var MenuItem $menuItem */
        $menuItem = $options['data'];
        $menu = $menuItem->getMenu();

        $choices = [];
        if ($menu !== null) {
            $siblings = $this->menuItemRepository->findBy(['menu' => $menu]);
            foreach ($siblings as $item) {
                if ($item->getId() !== $menuItem->getId()) {
                    $choices[$item->getName()] = $item->getId();
                }
            }
        }

        $builder
            ->add('name')
            ->add('route')
            ->add('title')
            ->add('menu')
            ->add('parent', ChoiceType::class, [
                'choices'     => $choices,
                'placeholder' => '-- none --',
                'required'    => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MenuItem::class,
        ]);
    }
}
