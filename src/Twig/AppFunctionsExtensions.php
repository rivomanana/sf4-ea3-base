<?php


namespace App\Twig;


use App\Services\Roles;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppFunctionsExtensions extends AbstractExtension
{
    /**
     * @var Roles
     */
    private $roles;

    public function __construct(Roles $roles)
    {
        $this->roles = $roles;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('_role', [$this, 'role_display'])
        ];
    }

    public function role_display($role){
        return $this->roles->getRoleLabel( $role );
    }

}