<?php


namespace App\H3;


use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Router\CrudUrlGenerator;

trait TTCrudTrait
{

    public function __configureActions(Actions $actions){
        return $actions->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action){
                return $action->setLabel('Ajouter')->setIcon('fa fa-plus');
        })
        ->update(Crud::PAGE_INDEX, Action::EDIT, function(Action $action){
            return $action->setIcon('fa fa-pencil');
        })
        ->update(Crud::PAGE_INDEX, Action::DELETE, function(Action $action){
                return $action->setIcon('fa fa-trash');
        });
    }

    public function status_field($field_name, string $pageName, $label = 'Etat'){
        return (Crud::PAGE_INDEX == $pageName) ?
            BooleanField::new($field_name)->setLabel($label)->onlyOnIndex()
            : ChoiceField::new($field_name)->setLabel($label)
            ->setChoices(['Activé' => 1, "Désactivé" => 0])
            ->setRequired(true)->onlyOnForms();
    }

    public function generateCrudAdminUrl($classname, $mode, $entityID = 0): string {
        /** @var CrudUrlGenerator $generator */
        $generator = $this->get(CrudUrlGenerator::class);
        $url = $generator->build()
            ->setController($classname)
            ->setAction($mode);
        if ( 0 != $entityID ){
            $url->setEntityId( $entityID );
        }
        return $url->generateUrl();
    }

}