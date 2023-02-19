<?php

namespace App\Form;

use App\Entity\Trick;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

class TrickFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title',TextType::class, ['label'=>'Titre'])
            ->add('chapo',TextType::class, ['label'=>'Chapo'])
            ->add('content', CKEditorType::class)
            ->add('imageFile', VichImageType::class, [
                'label' => 'Image(JPG or PNG file)',
                'required' => false,
                'allow_delete' => true,
                'delete_label' => 'delete ?',
                'download_uri' => false,
                'imagine_pattern' => 'Squared_thumbnail_small'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Trick::class,
        ]);
    }
}
