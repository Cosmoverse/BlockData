<?php

declare(strict_types=1);

namespace cosmicpe\blockdata;

use pocketmine\nbt\tag\CompoundTag;

final class BlockDataFactory{

	private const TAG_BLOCK_TYPE = "Type";
	private const TAG_BLOCK_DATA = "Data";

	/** @var BlockData[]|string[] */
	private static $types = [];

	/** @var string[] */
	private static $class_types = [];

	public static function register(string $type, string $class) : void{
		self::$types[$type] = $class;
		self::$class_types[$class] = $type;
	}

	public static function nbtDeserialize(CompoundTag $tag) : ?BlockData{
		return isset(self::$types[$type = $tag->getString(self::TAG_BLOCK_TYPE)]) ? self::$types[$type]::nbtDeserialize($tag->getCompoundTag(self::TAG_BLOCK_DATA)) : null;
	}

	public static function nbtSerialize(BlockData $data) : CompoundTag{
		return CompoundTag::create()
			->setString(self::TAG_BLOCK_TYPE, self::$class_types[get_class($data)])
			->setTag(self::TAG_BLOCK_DATA, $data->nbtSerialize());
	}
}