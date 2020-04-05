<?php

namespace bfguns\weapon\weapons;

use bfguns\BFGuns;
use bfguns\entity\Bullet;
use bfguns\weapon\Tags;
use bfguns\weapon\WeaponManager;
use ddapi\DeviceDataAPI;
use pocketmine\entity\Attribute;
use pocketmine\entity\Entity;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\item\Item;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\MoveActorDeltaPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;

abstract class SingleShot extends Weapon implements Tags
{

    const CATEGORY_ID = "";

    const DEFAULT_STATUS = [
        "Shot_Rate" => 5,
        "Ammo_Capacity" => 10,
        "Bullet_Damage" => 5,
        "Bullet_Speed" => 5,
        "Bullet_Spread" => 2,
        "Reload_Duration" => 60,
        "Sound_Shot_Name" => "bf2.handgun_shot",
        "Sound_Shot_Pitch" => 1,
        "Sound_Shot_Volume" => 1,
        "Sound_Reload_Name" => "bf2.reload1",
        "Sound_Reload_Pitch" => 1,
        "Sound_Reload_Volume" => 1,
        "Sound_Reload_Period" => 99999,
        "Sound_Reloaded_Name" => "bf2.reloaded1",
        "Sound_Reloaded_Pitch" => 1,
        "Sound_Reloaded_Volume" => 1
    ];

    protected $reloading = false;
    protected $reloadCounter = 0;

    protected $lastShot = 0;

    public static function initStatic()
    {
        Entity::registerEntity(Bullet::class);
    }

    public function getItem(): Item
    {
        $item = parent::getItem();

        $item->getNamedTag()->setInt(self::TAG_WEAPON_AMMO, $this->weaponStatus["Ammo_Capacity"]);
        $item->setCustomName("§r§f" . $this->weaponStatus["Item_Name"] . "§r§f ▪ «" . $this->weaponStatus["Ammo_Capacity"] . "»");

        return $item;
    }

    public function onPacketReceive(DataPacketReceiveEvent $event)
    {
        $pk = $event->getPacket();

        if($pk instanceof InventoryTransactionPacket)
        {
            if($pk->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM)
            {
                //interact
                $this->onTouch();
            }
            elseif($pk->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY)
            {
                //useItemOnEntity
                $event->setCancelled(true);
                if(DeviceDataAPI::getInstance()->getCurrentInputMode($this->player) !== DeviceDataAPI::INPUTMODE_KEYBOARD) $this->onTouch();
            }
        }

        if($pk instanceof LevelSoundEventPacket)
        {
            if($pk->sound === LevelSoundEventPacket::SOUND_ATTACK_NODAMAGE){
                //preInteract
                if(DeviceDataAPI::getInstance()->getCurrentInputMode($this->player) === DeviceDataAPI::INPUTMODE_TAP) $this->onTouch();
            }
        }
    }

    public function onTouch(){//ここも共通処理にできる
        if(!$this->reloading){
            if(Server::getInstance()->getTick() - $this->lastShot >= $this->weaponStatus["Shot_Rate"]){
                $item = $this->player->getInventory()->getItemInHand();
                if($item->getNamedTag()->offsetGet(self::TAG_UNIQUE_ID) !== $this->uuid) return;
                $tag = $item->getNamedTag();
                $ammo = $tag->getInt(self::TAG_WEAPON_AMMO);
                if($ammo > 0){
                    $ammo--;
                    $this->lastShot = Server::getInstance()->getTick();
                    $tag->setInt(self::TAG_WEAPON_AMMO, $ammo);
                    $item->setNamedTag($tag);
                    $item->setCustomName($this->weaponStatus["Item_Name"] . "§r§f ▪ «" . $ammo . "»");
                    $this->player->getInventory()->setItemInHand($item);

                    $spread = $this->weaponStatus["Bullet_Spread"];
                    $nbt = Entity::createBaseNBT(
                        $this->player->add(0, $this->player->getEyeHeight(), 0),
                        $this->getDirectionVector($this->player->yaw + mt_rand(-$spread, $spread)/100, $this->player->pitch + mt_rand(-$spread, $spread)/100)->multiply($this->weaponStatus["Bullet_Speed"]),
                        0,
                        0
                    );

                    $entity = new Bullet($this->player->level, $nbt, $this->player, $this);
                    $entity->setBaseDamage($this->weaponStatus["Bullet_Damage"]);
                    $entity->spawnToAll();

                    //装飾
                    $this->playSound($this->player, $this->weaponStatus["Sound_Shot_Name"], $this->weaponStatus["Sound_Shot_Pitch"], $this->weaponStatus["Sound_Shot_Volume"]);
                    $this->player->sendPopup($item->getCustomName());
                }else{
                    $this->reloading = true;
                }
            }
        }
    }

    public function onDeath(PlayerDeathEvent $event){
        $this->reloading = false;
    }

    public function onDropItem(PlayerDropItemEvent $event)
    {
        $event->setCancelled(true);
        $this->reloading = true;
    }

    public function onUpdate(int $currentTick)
    {
        if($this->reloading){
            $item = $this->player->getInventory()->getItemInHand();
            if($item->getNamedTag()->offsetGet(self::TAG_UNIQUE_ID) !== $this->uuid) return;
            /*装飾*/
            $this->player->sendPopup($item->getCustomName() . "®");
            $bar = '⢸⢸⢸⢸⢸⢸⢸⢸⢸⢸⢸⢸⢸⢸⢸⢸⢸⢸⢸⢸';
            $percent = round(($this->reloadCounter / $this->weaponStatus["Reload_Duration"])*100);
            $barProgress = floor($percent / 5);
            $this->player->addActionBarMessage("\n\n\n|§a" . preg_replace("/^.{0,$barProgress}+\K/us", '§7', $bar) . "§f|" . $percent . "%");
            if($this->reloadCounter % $this->weaponStatus["Sound_Reload_Period"] === 0){
                $this->playSound($this->player, $this->weaponStatus["Sound_Reload_Name"], $this->weaponStatus["Sound_Reload_Pitch"], $this->weaponStatus["Sound_Reload_Volume"]);
            }

            $this->reloadCounter++;
            if($this->reloadCounter >= $this->weaponStatus["Reload_Duration"]){
                $tag = $item->getNamedTag();
                $tag->setInt(self::TAG_WEAPON_AMMO, $this->weaponStatus["Ammo_Capacity"]);
                $item->setNamedTag($tag);
                $item->setCustomName($this->weaponStatus["Item_Name"] . "§r§f ▪ «" . $this->weaponStatus["Ammo_Capacity"] . "»");
                $this->player->getInventory()->setItemInHand($item);

                $this->reloading = false;
                $this->reloadCounter = 0;

                /*装飾*/
                $this->playSound($this->player, $this->weaponStatus["Sound_Reloaded_Name"], $this->weaponStatus["Sound_Reloaded_Pitch"], $this->weaponStatus["Sound_Reloaded_Volume"]);
                $this->player->sendPopup($item->getCustomName());
                $this->player->addActionBarMessage("\n\n\n|§a" . $bar . "§f|100%");
                $player = $this->player;
                BFGuns::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(
                    function (int $currentTick) use ($player): void {
                        if($player->isOnline()) $player->addActionBarMessage(" ");
                    }
                ), 10);
            }
        }
    }//リロードの処理とか共通にしたい
}