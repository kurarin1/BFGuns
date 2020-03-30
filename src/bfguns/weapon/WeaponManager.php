<?php

namespace bfguns\weapon;

use bfguns\BFGuns;
use bfguns\weapon\weapons\AssaultRifle;
use bfguns\weapon\weapons\FullAuto;
use bfguns\weapon\weapons\BFEmpty;
use bfguns\weapon\weapons\LightMachineGun;
use bfguns\weapon\weapons\SubMachineGun;
use bfguns\weapon\weapons\Weapon;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;

class WeaponManager implements Tags
{
    /* @var $weaponData array[]*/
    private $weaponData = [];
    /* @var $weaponCategory string[]*/
    private $weaponCategory = [];
    /* @var $playerWeapon Weapon[]*/
    private $playerWeapon = [];

    public function __construct()
    {
        $this->init();
    }

    public function init(){
        $this->registerCategory(AssaultRifle::CATEGORY_ID, FullAuto::class);
        $this->registerCategory(SubMachineGun::CATEGORY_ID, SubMachineGun::class);
        $this->registerCategory(LightMachineGun::CATEGORY_ID, LightMachineGun::class);
        $this->read();

        BFGuns::getInstance()->getScheduler()->scheduleRepeatingTask(new ClosureTask(
            function (int $currentTick): void {
                BFGuns::getWeaponManager()->updateWeapons($currentTick);
            }
        ), 1);

        Server::getInstance()->getPluginManager()->registerEvents(new WeaponListener(),  BFGuns::getInstance());
    }


    public function fin(){
        $this->write();
    }

    private function read(){
        $folder = BFGuns::getInstance()->getDataFolder() . "weapon";
        if(!file_exists($folder)) mkdir($folder);
        $dir = $folder .= DIRECTORY_SEPARATOR;
        foreach(scandir($dir) as $file){
            if($file !== "." and $file !== ".."){
                $data = yaml_parse_file($dir . $file);
                if(isset($data["Weapon_Id"])){
                    $this->weaponData[$data["Weapon_Id"]] = $data;
                }else{
                    Server::getInstance()->getLogger()->warning($file . "を読み込めません");
                }
            }
        }
    }

    private function write(){
        $folder = BFGuns::getInstance()->getDataFolder() . "weapon" . DIRECTORY_SEPARATOR;
        foreach ($this->weaponData as $key => $value){
            yaml_emit_file($folder . $key . ".yml", $value, YAML_UTF8_ENCODING);
        }
    }

    private function registerCategory(string $categoryId, string $class){
        $class::initStatic();
        $this->weaponCategory[$categoryId] = $class;
    }

    public function getWeapon(string $weaponId, string $uuid = null) : Weapon{
        try {
            return new $this->weaponCategory[$this->weaponData[$weaponId]["Weapon_Category"]]($this->weaponData[$weaponId], $uuid);
        }catch (\Exception $exception){
            return new BFEmpty();
        }
    }

    public function setPlayerWeapon(Player $player, Weapon $weapon){
        if(isset($this->playerWeapon[$player->getName()])) $this->unsetPlayerWeapon($player);
        $weapon->setOwner($player);
        $this->playerWeapon[$player->getName()] = $weapon;
        $weapon->init();
    }

    public function setPlayerWeaponFromId(Player $player, string $id, string $uuid = null){
        $this->setPlayerWeapon($player, $this->getWeapon($id, $uuid));
    }

    public function getPlayerWeapon(Player $player) : Weapon{
        return isset($this->playerWeapon[$player->getName()]) ? $this->playerWeapon[$player->getName()] : new BFEmpty();
    }

    public function unsetPlayerWeapon(Player $player){
        if ($this->getPlayerWeapon($player) !== null) $this->getPlayerWeapon($player)->fin();
        unset($this->playerWeapon[$player->getName()]);
    }

    public function updateWeapons($currentTick){
        foreach ($this->playerWeapon as $weapon){
            $weapon->onUpdate($currentTick);
        }
    }

    /*public function checkPlayerWeapon(Player $player, Item $newItem){
        $newTag = $newItem->getNamedTag();
        if(BFGuns::getWeaponManager()->getPlayerWeapon($player)->getUUID() !== $newTag->offsetGet(self::TAG_UNIQUE_ID)){
            try {
                BFGuns::getWeaponManager()->setPlayerWeaponFromId($player, $newTag->getString(self::TAG_WEAPON_ID), $newTag->getString(self::TAG_UNIQUE_ID));
            }catch (\Exception $exception){
                BFGuns::getWeaponManager()->setPlayerWeapon($player, new BFEmpty());
            }
        }
    }*/

}