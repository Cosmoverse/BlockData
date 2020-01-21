<?php

declare(strict_types=1);

namespace cosmicpe\blockdata;

use pocketmine\nbt\tag\CompoundTag;

interface BlockData{

	public static function nbtDeserialize(CompoundTag $nbt) : BlockData;

	public function nbtSerialize() : CompoundTag;
}