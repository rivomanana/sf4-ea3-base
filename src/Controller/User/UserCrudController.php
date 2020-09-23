<?php

namespace App\Controller\User;

use App\Entity\User;
use App\H3\TTCrudTrait;
use App\Services\Roles;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserCrudController extends AbstractCrudController
{
    use TTCrudTrait;

    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var Roles
     */
    private $roles;
    /**
     * @var UserPasswordEncoderInterface
     */
    private $encoder;

    public function __construct(TranslatorInterface $translator, Roles $roles, UserPasswordEncoderInterface $encoder)
    {
        $this->translator = $translator;
        $this->roles = $roles;
        $this->encoder = $encoder;
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {

        $roles  = ($pageName == Crud::PAGE_EDIT or Crud::PAGE_NEW == $pageName) ?
            (ChoiceField::new('roles')->setLabel('Rôles')->onlyOnForms()
                ->setChoices(['Administrateur'=>'ROLE_ADMIN', 'Utilisateur'=>'ROLE_USER'])
                ->allowMultipleChoices()) :
            (ArrayField::new('roles')->setLabel('Rôles')
                ->setTemplatePath('user/role.list.table.html.twig')->onlyOnIndex());
        $usernameField = TextField::new('username')->setLabel('Nom d\'utilisateur');

        if ( !$this->getUser()->is_admin() ){
            $usernameField->setFormTypeOptions([
                'disabled' => true
            ])->setFormType(TextType::class);
        }

        $fields  = [];
        $fields[] = FormField::addPanel($this->translator->trans('Information de base'));
        $fields[] = TextField::new('fullName')->setLabel('Nom / Prénom')->onlyOnIndex();
        $fields[] = TextField::new('lastname')->setLabel('Nom')->onlyOnForms();
        $fields[] = TextField::new('firstname')->setLabel('Prénom')->onlyOnForms();
        $fields[] = TextField::new('email')->setLabel('Email');
        $fields[] = TextField::new('phone')->setLabel('Numero de téléphone')->onlyOnForms();

        $fields[] = FormField::addPanel($this->translator->trans('Identifiants'));
        $fields[] = $usernameField;
        $fields[] = TextField::new('password')->setLabel('Mot de passe')
            ->setRequired( !($pageName == Crud::PAGE_EDIT) )
            ->setFormType(RepeatedType::class)
            ->setFormTypeOption('mapped', false)
            ->setFormTypeOption('type', PasswordType::class)
            ->setFormTypeOption('first_options',  [
                'label' => 'Mot de passe',
                'data' => ''
            ])
            ->setFormTypeOption('second_options',  [
                'label' => 'Verifier le mot de passe',
                'data' => ''
            ])
            ->onlyOnForms()
            ->setFormTypeOption('invalid_message', $this->translator->trans('The password fields must match'))
        ;
        if ( $this->getUser()->is_admin() ){
            $fields[] = FormField::addPanel($this->translator->trans('Roles'));
            $fields[] = $roles;

            $fields[] = FormField::addPanel($this->translator->trans('Statut'));
            $fields[] = $this->status_field('status', $pageName);
        }

        $fields[] = DateTimeField::new('created_at', 'Date de création')
            ->formatValue(function($value, User $entity){
                return $this->get('twig')->render('ea/list/date.fr.html.twig', ['value' => $entity->getCreatedAt()]);
            })
            ->onlyOnIndex();

        return $fields;
    }

    public function delete(AdminContext $context)
    {
        /** @var User $user_in_context */
        $user_in_context  = $context->getEntity()->getInstance();
        if ( $user_in_context->getId() != $this->getUser()->getId() )
            return parent::delete($context);
        else {
            $this->addFlash('warning', $this->translator->trans('Vous ne pouvez pas supprimer votre propre compte'));
            return $this->redirect($this->generateCrudAdminUrl(self::class, Crud::PAGE_INDEX));
        }
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var User $user */
        $user  = $entityInstance;
        $post  = $this->get('request_stack')->getCurrentRequest()->request->get('User');
        if ( "" != $post['password']['first'] and "" != $post['password']['second'] and $post['password']['second'] == $post['password']['first']){
            $user->setPassword( $this->encoder->encodePassword( $user, $post['password']['first'] ) );
        }
        $this->addFlash('notice', $this->translator->trans('Mis à jour reussie'));
        parent::updateEntity( $entityManager, $user );
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var User $user */
        $user  = $entityInstance;
        $post  = $this->get('request_stack')->getCurrentRequest()->request->get('User');
        $user->setPassword( $this->encoder->encodePassword( $user, $post['password']['first'] ) );
        $this->addFlash('notice', $this->translator->trans('Le nouvel utilisateur a été crée avec succes'));
        parent::updateEntity( $entityManager, $user );
    }

    public function index(AdminContext $context)
    {
        if ( !$this->getUser()->is_admin() ){
            return $this->redirect(
                $this->generateCrudAdminUrl(self::class, Crud::PAGE_EDIT, $this->getUser()->getId())
            );
        }
        return parent::index($context);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $this->__configureActions($actions);
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setPageTitle(Crud::PAGE_INDEX, 'Utilisateurs')
            ->setPageTitle(Crud::PAGE_NEW, 'Ajouter un nouvel utilisateur')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier un utilisateur');
    }

    public function edit(AdminContext $context)
    {
        $request_uid  = $context->getRequest()->get('entityId');
        $user = $this->getUser();
        if ( $user->is_admin() or  $request_uid == $user->getid() ){
            return parent::edit( $context );
        } else {
            throw new AccessDeniedException();
        }
    }

    /**
     * @Route("/admin/profile/edit/{id}", name="route_edit_profile")
     */
    public function editProfile($id){
        return $this->redirect(
            $this->generateCrudAdminUrl(self::class, Crud::PAGE_EDIT, $id)
        );
    }
}
