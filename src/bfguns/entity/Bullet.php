<?php

namespace bfguns\entity;

use pocketmine\block\Block;
use pocketmine\entity\projectile\Projectile;
use pocketmine\level\particle\DestroyBlockParticle;
use pocketmine\math\RayTraceResult;

class Bullet extends Projectile
{

    public const NETWORK_ID = self::SNOWBALL;

    public $width = 0.1;
    public $height = 0.1;

    protected $gravity = 0.005;

    protected function onHitBlock(Block $blockHit, RayTraceResult $hitResult): void
    {
        parent::onHitBlock($blockHit, $hitResult);
        $this->level->addParticle(new DestroyBlockParticle($this, $blockHit));
        $this->flagForDespawn();
    }

}