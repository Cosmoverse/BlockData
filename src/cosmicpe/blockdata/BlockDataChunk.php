<?php

declare(strict_types=1);

namespace cosmicpe\blockdata;

use LevelDB;
use LevelDBWriteBatch;
use pocketmine\nbt\BaseNbtSerializer;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\TreeRoot;
use pocketmine\world\World;

final class BlockDataChunk{

	/** @var LevelDB */
	private $database;

	/** @var BigEndianNbtSerializer */
	private $serializer;

	/** @var BlockData[]|null[] */
	private $block_cache = [];

	public function __construct(LevelDB $database, BaseNbtSerializer $serializer){
		$this->database = $database;
		$this->serializer = $serializer;
	}

	public function getBlockDataAt(int $x, int $y, int $z) : ?BlockData{
		if(isset($this->block_cache[$hash = World::blockHash($x, $y, $z)])){
			return $this->block_cache[$hash];
		}

		$buffer = $this->database->get("b" . $hash);
		if($buffer === false){
			return $this->block_cache[$hash] = null;
		}

		return $this->block_cache[$hash] = BlockDataFactory::nbtDeserialize($this->serializer->read($buffer)->mustGetCompoundTag());
	}

	public function setBlockDataAt(int $x, int $y, int $z, ?BlockData $data) : void{
		$this->block_cache[World::blockHash($x, $y, $z)] = $data;
	}

	public function unload() : void{
		if(count($this->block_cache) > 0){
			$batch = new LevelDBWriteBatch();
			foreach($this->block_cache as $hash => $data){
				$data !== null ? $batch->put("b" . $hash, $this->serializer->write(new TreeRoot(BlockDataFactory::nbtSerialize($data)))) : $batch->delete("b" . $hash);
			}
			$this->database->write($batch);
		}
	}
}