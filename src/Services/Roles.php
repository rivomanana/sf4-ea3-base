<?php


namespace App\Services;


use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class Roles
{

    /**
     * @var array
     */
    private $roles = [];

    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var ParameterBagInterface
     */
    private $parameterBag;
    /**
     * @var Security
     */
    private $security;

    public function __construct(TranslatorInterface $translator, ParameterBagInterface $parameterBag, Security $security)
    {
        $this->translator = $translator;
        $this->parameterBag = $parameterBag;
        $this->security = $security;
        foreach ( $this->parameterBag->get('roles_h3') as $role ){
            $role_item = strtolower( str_replace('ROLE_', '', $role) );
            $this->roles[ $role ] =  $this->translator->trans('role_label.'.$role_item);
        }
    }

    public function get_roles_choices(){
        return array_flip($this->roles);
    }

    public function getRoleLabel( $role ){
        return ( isset( $this->roles[ $role ] ) ) ? $this->roles[ $role ] : '-';
    }

    public function is_granted($roles)
    {
        if ( is_null( $roles ) )
            return true;
        $granted = false;
        $rolesUser = $this->security->getUser()->getRoles();
        if ( is_array( $roles ) ){
            foreach ( $roles as $role )
                if ( in_array( $role, $rolesUser ) )
                    $granted = true;
        } else {
            $granted = in_array( $roles, $rolesUser );
        }
        return $granted;
    }

}