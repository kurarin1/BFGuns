<?php

namespace bfguns\entity;

use bfguns\event\EntityDamageByWeaponEvent;
use bfguns\weapon\weapons\Weapon;
use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\entity\projectile\Projectile;
use pocketmine\level\Level;
use pocketmine\level\particle\DestroyBlockParticle;
use pocketmine\math\RayTraceResult;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\ActorEventPacket;

class Bullet extends Projectile
{

    public const NETWORK_ID = self::SNOWBALL;

    public $width = 0.1;
    public $height = 0.1;

    protected $gravity = 0.005;

    /* @var $spawnVector Vector3*/
    protected $spawnVector;
    /* @var $weapon Weapon*/
    protected $weapon;

    public function __construct(Level $level, CompoundTag $nbt, ?Entity $shootingEntity = null, ?Weapon $weapon = null)
    {
        parent::__construct($level, $nbt, $shootingEntity);
        $this->spawnVector = $this->asVector3();
        $this->weapon = $weapon;
    }

    protected function onHitBlock(Block $blockHit, RayTraceResult $hitResult): void{
        parent::onHitBlock($blockHit, $hitResult);
        $this->level->addParticle(new DestroyBlockParticle($this, $blockHit));
        $this->flagForDespawn();
    }

    protected function onHitEntity(Entity $entityHit, RayTraceResult $hitResult): void{
        $shooter = $this->getOwningEntity();
        $event = new EntityDamageByWeaponEvent($shooter === null ? $this : $shooter, $entityHit, $this->getBaseDamage(), $this->weapon);
        $event->call();
        if(!$event->isCancelled()){
            $entityHit->setLastDamageCause($event);
            $entityHit->broadcastEntityEvent(ActorEventPacket::HURT_ANIMATION);
            $entityHit->setHealth($entityHit->getHealth() - $event->getBaseDamage());
        }
        $this->flagForDespawn();
    }

    public function onUpdate(int $currentTick): bool{
        $doUpdate = parent::onUpdate($currentTick);

        if($this->spawnVector->distance($this) > 80){//80ブロック以上先のEntityは描画されないため
            $this->flagForDespawn();
            return true;
        }

        if($this->ticksLived > 200){//スポーンして10秒以上経ったらデスポーン
            $this->flagForDespawn();
            return true;
        }

        return $doUpdate;
    }

}