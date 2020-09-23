<?php


namespace App\H3;


use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;

trait TTControllerTrait
{
    public function configureAssets(): Assets
    {
        return Assets::new()
            ->addCssFile('assets/css/style.css');
    }
}