<?php

namespace App\Controller\User;

use App\Entity\Trick;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use Vich\UploaderBundle\Form\Type\VichImageType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
class TrickCrudController extends AbstractCrudController
{ public static function getEntityFqcn(): string
{
    return Trick::class;
}
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->addFormTheme('@FOSCKEditor/Form/ckeditor_widget.html.twig')
            ->overrideTemplate('crud/index','bundles/EasyAdminBundle/custom/crud_index_custom.html.twig')
            ->overrideTemplate('crud/edit', 'bundles/EasyAdminBundle/custom/crud_edit_custom.html.twig')
            ->overrideTemplate('crud/detail','bundles/EasyAdminBundle/custom/crud_detail_custom.html.twig')
            ->overrideTemplate('crud/new', 'bundles/EasyAdminBundle/custom/crud_new_custom.html.twig')
            ;
    }
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action->setIcon('fa fa-trash')
                    ->setLabel('Supprimer')
                    ->addCssClass('text-light btn btn-danger');
            })
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action->setIcon('fa fa-edit')
                    ->setLabel('Editer')
                    ->addCssClass('text-light btn btn-info');
            })
            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action->setIcon('fa fa-plus')
                    ->setLabel('Nouvel article')
                    ->addCssClass('text-light btn btn-success');
            });
    }
    public function configureFields(string $pageName): iterable
    {
        $imageFields =ImageField::new('imageFile')
            ->setFormType( VichImageType::class)
            ->setLabel('Image');
        $image =ImageField::new('imageName')
            ->setBasePath('/uploads/images')
            ->setLabel('Image');


        $fields=[
            IdField::new('id')->onlyOnIndex(),
            TextField::new('title','Titre'),
            TextEditorField::new('description','Description')
                ->setFormType( CKEditorType::class),
            TimeField::new('createdAt','Création')->onlyOnIndex(),
            TimeField::new('updatedAt', 'Modification')->onlyOnIndex(),
        ];

        if ($pageName === Crud::PAGE_INDEX || $pageName === Crud::PAGE_DETAIL) {
            $fields[] = $image;
        } else {
            $fields[] = $imageFields;
        }

        return $fields;
    }
}
