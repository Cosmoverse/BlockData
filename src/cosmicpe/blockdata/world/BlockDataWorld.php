<?php

declare(strict_types=1);

namespace cosmicpe\blockdata\world;

use cosmicpe\blockdata\BlockData;
use LevelDB;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\plugin\Plugin;
use pocketmine\world\World;

final class BlockDataWorld{

	/** @var BigEndianNbtSerializer */
	private $serializer;

	/** @var World */
	private $world;

	/** @var LevelDB */
	private $database;

	/** @var BlockDataChunk[] */
	private $chunks = [];

	public function __construct(Plugin $plugin, World $world){
		$this->serializer = new BigEndianNbtSerializer();
		$this->world = $world;

		$directory = $plugin->getDataFolder() . "blockdata/";
		if(!is_dir($directory)){
			/** @noinspection MkdirRaceConditionInspection */
			mkdir($directory);
		}

		$this->database = new LevelDB($directory . $world->getFolderName(), [
			"compression" => LEVELDB_SNAPPY_COMPRESSION,
			"block_size" => 64 * 1024
		]);
	}

	public function getWorld() : World{
		return $this->world;
	}

	public function getBlockDataAt(int $x, int $y, int $z) : ?BlockData{
		return $this->chunks[World::chunkHash($x >> 4, $z >> 4)]->getBlockDataAt($x, $y, $z);
	}

	public function setBlockDataAt(int $x, int $y, int $z, ?BlockData $data) : void{
		$this->chunks[World::chunkHash($x >> 4, $z >> 4)]->setBlockDataAt($x, $y, $z, $data);
	}

	public function loadChunk(int $chunkX, int $chunkZ) : void{
		$this->chunks[World::chunkHash($chunkX, $chunkZ)] = new BlockDataChunk($this->database, $this->serializer);
	}

	public function unloadChunk(int $chunkX, int $chunkZ, bool $save = true) : void{
		$hash = World::chunkHash($chunkX, $chunkZ);
		if(isset($this->chunks[$hash])){
			if($save){
				$this->chunks[$hash]->save();
			}
			unset($this->chunks[$hash]);
		}
	}

	public function save() : void{
		foreach($this->chunks as $chunk){
			$chunk->save();
		}
	}

	public function close() : void{
		$save = $this->world->getAutoSave();
		foreach($this->chunks as $hash => $chunk){
			World::getXZ($hash, $chunkX, $chunkZ);
			$this->unloadChunk($chunkX, $chunkZ, $save);
		}
		unset($this->database);
	}
}