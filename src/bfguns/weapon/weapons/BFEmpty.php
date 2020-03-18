<?php

namespace bfguns\weapon\weapons;

class BFEmpty extends Weapon
{

    const CATEGORY_ID = "";

    const DEFAULT_STATUS = [];


    public function __construct()
    {
        parent::__construct([]);
    }


}