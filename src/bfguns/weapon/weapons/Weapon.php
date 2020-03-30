<?php

namespace bfguns\weapon\weapons;

use bfguns\BFGuns;
use bfguns\weapon\Tags;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\item\Durable;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\UUID;

abstract class Weapon implements Tags
{
    const CATEGORY_ID = "";

    const COMMON_STATUS = [
        //"Weapon_Id" => "WeaponId",
        //"Weapon_Category" => "ar",
        "Item_Name" => "WeaponName",
        "Item_ID" => "minecraft:wooden_pickaxe:0",
        "Item_Lore" => "説明文!n説明文2!n説明文3..."
    ];

    const DEFAULT_STATUS = [];

    /* @var $player Player*/
    protected $player;
    /* @var $uuid string*/
    protected $uuid;

    protected $weaponStatus = [];

    public static function initStatic(){

    }

    public function __construct(array $data, string $uuid = null)
    {
        $this->weaponStatus = static::getComplementedStatus($data);
        $this->uuid = $uuid === null ? UUID::fromRandom()->toString() : $uuid;
    }

    public function init(){

    }

    public function fin(){

    }

    public function getUUID() : string {
        return $this->uuid;
    }

    public function setOwner(Player $player){
        $this->player = $player;
    }

    public function getName() : string {
        return $this->weaponStatus["Item_Name"];
    }

    public function getItem() : Item{
        $item = Item::fromString($this->weaponStatus["Item_ID"]);

        $item->getNamedTag()->setString(self::TAG_UNIQUE_ID, $this->uuid);
        $item->getNamedTag()->setString(self::TAG_WEAPON_CATEGORY, $this->weaponStatus["Weapon_Category"]);
        $item->getNamedTag()->setString(self::TAG_WEAPON_ID, $this->weaponStatus["Weapon_Id"]);

        $item->setCustomName($this->weaponStatus["Item_Name"]);
        $item->setLore(explode("!n", $this->weaponStatus["Item_Lore"]));

        if($item instanceof Durable){
            $item->setUnbreakable(true);
        }

        return $item;
    }

    protected static function getComplementedStatus(array $data) : array {
        if(!isset($data["Weapon_Id"])) $data["Weapon_Id"] = UUID::fromRandom()->toString();
        if(!isset($data["Weapon_Id"])) $data["Weapon_Category"] = static::CATEGORY_ID;

        foreach (self::COMMON_STATUS as $key => $value){
            if(!isset($data[$key])) $data[$key] = $value;
        }

        foreach (static::DEFAULT_STATUS as $key => $value){
            if(!isset($data[$key])) $data[$key] = $value;
        }

        return $data;
    }

    protected function playSound(Position $pos, string $soundName, float $pitch, float $volume){
        $pk = new PlaySoundPacket();
        $pk->soundName = $soundName;
        $pk->volume = $volume;
        $pk->pitch = $pitch;
        $pk->x = $pos->x;
        $pk->y = $pos->y;
        $pk->z = $pos->z;

        Server::getInstance()->broadcastPacket($pos->getLevel()->getPlayers(), $pk);
    }

    protected function getDirectionVector($yaw, $pitch) : Vector3{
        $y = -sin(deg2rad($pitch));
        $xz = cos(deg2rad($pitch));
        $x = -$xz * sin(deg2rad($yaw));
        $z = $xz * cos(deg2rad($yaw));
        return new Vector3($x, $y, $z);
    }

    public function onUpdate(int $currentTick){

    }

    public function onInteract(PlayerInteractEvent $event){

    }

    public function onDropItem(PlayerDropItemEvent $event){

    }

    public function onPacketReceive(DataPacketReceiveEvent $event){

    }

    public function onDeath(PlayerDeathEvent $event){

    }
}