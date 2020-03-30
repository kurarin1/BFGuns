<?php

namespace bfguns;

use bfguns\weapon\WeaponManager;
use bfguns\weapon\weapons\AssaultRifle;
use bfguns\weapon\weapons\LightMachineGun;
use bfguns\weapon\weapons\SubMachineGun;
use pocketmine\plugin\PluginBase;

class BFGuns extends PluginBase
{

    private static $instance;

    /* @var $weaponManager WeaponManager*/
    private static $weaponManager;

    public function onEnable(){
        self::$instance = $this;
        self::$weaponManager = new WeaponManager();
    }

    public function onDisable()
    {
        self::$weaponManager->fin();
    }

    public static function getInstance() : self {
        return self::$instance;
    }

    public static function getWeaponManager() : WeaponManager{
        return self::$weaponManager;
    }

}