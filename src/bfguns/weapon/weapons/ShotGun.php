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

class ShotGun extends SingleShot implements Tags
{
//無駄が多いよーーー
    const CATEGORY_ID = "sg";

    const DEFAULT_STATUS = [
        "Shot_Rate" => 5,
        "Ammo_Capacity" => 10,
        "Bullet_Amount" => 8,
        "Bullet_Damage" => 5,
        "Bullet_Speed" => 5,
        "Bullet_Spread" => 2,
        "Reload_Duration" => 60,
        "Sound_Shot_Name" => "bf2.shotgun_shot",
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

    public function onTouch(){//ここも共通処理にできる
        if(!$this->reloading) {
            if (Server::getInstance()->getTick() - $this->lastShot >= $this->weaponStatus["Shot_Rate"]) {
                $item = $this->player->getInventory()->getItemInHand();
                if ($item->getNamedTag()->offsetGet(self::TAG_UNIQUE_ID) !== $this->uuid) return;
                $tag = $item->getNamedTag();
                $ammo = $tag->getInt(self::TAG_WEAPON_AMMO);
                if ($ammo > 0) {
                    $ammo--;
                    $this->lastShot = Server::getInstance()->getTick();
                    $tag->setInt(self::TAG_WEAPON_AMMO, $ammo);
                    $item->setNamedTag($tag);
                    $item->setCustomName($this->weaponStatus["Item_Name"] . "§r§f ▪ «" . $ammo . "»");
                    $this->player->getInventory()->setItemInHand($item);

                    for ($i = 0; $i < $this->weaponStatus["Bullet_Amount"]; $i++) {
                        $spread = $this->weaponStatus["Bullet_Spread"];
                        $nbt = Entity::createBaseNBT(
                            $this->player->add(0, $this->player->getEyeHeight(), 0),
                            $this->getDirectionVector($this->player->yaw + mt_rand(-$spread, $spread) / 100, $this->player->pitch + mt_rand(-$spread, $spread) / 100)->multiply($this->weaponStatus["Bullet_Speed"]),
                            0,
                            0
                        );

                        $entity = new Bullet($this->player->level, $nbt, $this->player, $this);
                        $entity->setBaseDamage($this->weaponStatus["Bullet_Damage"]);
                        $entity->setGravity(0.08);
                        $entity->setDrag(0.1);
                        $entity->spawnToAll();
                    }

                    //装飾
                    $this->playSound($this->player, $this->weaponStatus["Sound_Shot_Name"], $this->weaponStatus["Sound_Shot_Pitch"], $this->weaponStatus["Sound_Shot_Volume"]);
                    $this->player->sendPopup($item->getCustomName());
                } else {
                    $this->reloading = true;
                }
            }
        }
    }
}