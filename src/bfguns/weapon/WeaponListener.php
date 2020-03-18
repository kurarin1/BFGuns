<?php

namespace bfguns\weapon;

use bfguns\BFGuns;
use bfguns\weapon\weapons\BFEmpty;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\LoginPacket;

class WeaponListener implements Listener , Tags
{

    /**
     * @priority LOWEST
     * @param PlayerJoinEvent $event
     */
    public function onJoin(PlayerJoinEvent $event){
        //$event->getPlayer()->getInventory()->setContents([BFGuns::getWeaponManager()->getWeapon("sample")->getItem()]);
    }

    /**
     * @priority HIGHEST
     * @param PlayerJoinEvent $event
     */
    public function onJoin2(PlayerJoinEvent $event){
        try {
            BFGuns::getWeaponManager()->setPlayerWeaponFromId($event->getPlayer(), $event->getPlayer()->getInventory()->getItemInHand()->getNamedTag()->getString(self::TAG_WEAPON_ID), $event->getPlayer()->getInventory()->getItemInHand()->getNamedTag()->getString(self::TAG_UNIQUE_ID));
        }catch (\Exception $exception){
            BFGuns::getWeaponManager()->setPlayerWeapon($event->getPlayer(), new BFEmpty());
        }
    }

    public function onQuit(PlayerQuitEvent $event){
        BFGuns::getWeaponManager()->unsetPlayerWeapon($event->getPlayer());
    }

    public function onItemHeld(PlayerItemHeldEvent $event){
        $player = $event->getPlayer();

        $newItem = $event->getItem();
        $newTag = $newItem->getNamedTag();

        if(BFGuns::getWeaponManager()->getPlayerWeapon($player)->getUUID() !== $newTag->offsetGet(self::TAG_UNIQUE_ID)){
            try {
                BFGuns::getWeaponManager()->setPlayerWeaponFromId($event->getPlayer(), $newTag->getString(self::TAG_WEAPON_ID), $newTag->getString(self::TAG_UNIQUE_ID));
            }catch (\Exception $exception){
                BFGuns::getWeaponManager()->setPlayerWeapon($event->getPlayer(), new BFEmpty());
            }
        }
    }

    public function onInteract(PlayerInteractEvent $event){
        BFGuns::getWeaponManager()->getPlayerWeapon($event->getPlayer())->onInteract($event);
    }

    public function onDropItem(PlayerDropItemEvent $event){
        BFGuns::getWeaponManager()->getPlayerWeapon($event->getPlayer())->onDropItem($event);
    }

    public function onPacketReceive(DataPacketReceiveEvent $event){
        BFGuns::getWeaponManager()->getPlayerWeapon($event->getPlayer())->onPacketReceive($event);
    }

}