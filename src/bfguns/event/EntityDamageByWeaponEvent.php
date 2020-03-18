<?php

namespace bfguns\event;

use bfguns\weapon\weapons\Weapon;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class EntityDamageByWeaponEvent extends EntityDamageByEntityEvent
{

    /* @var $weapon Weapon*/
    protected $weapon;

    public function __construct(Entity $damager, Entity $entity, float $damage, Weapon $weapon)
    {
        parent::__construct($damager, $entity, self::CAUSE_ENTITY_ATTACK, $damage, [], 0);
        $this->weapon = $weapon;
    }

    public function getWeapon() : Weapon{
        return $this->weapon;
    }

}