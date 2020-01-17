<?php

namespace AppBundle\Form;

use AppBundle\Entity\RestaurantCategory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RestaurantCategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('name', TextType::class, [
                'label'=>'Nome',
                'required' => true,
            ])
            ->add('description', TextareaType::class, [
                'required' => true,
                'label' => 'localBusiness.form.description',
            ])
            ->add('delete', SubmitType::class, [
                'label' => 'basics.delete',
            ])
        ;

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(array(
            'data_class' => RestaurantCategory::class,
        ));
    }
}
