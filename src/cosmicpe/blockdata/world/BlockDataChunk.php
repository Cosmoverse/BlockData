<?php

declare(strict_types=1);

namespace cosmicpe\blockdata\world;

use cosmicpe\blockdata\BlockData;
use cosmicpe\blockdata\BlockDataFactory;
use LevelDB;
use LevelDBWriteBatch;
use pocketmine\nbt\BaseNbtSerializer;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\TreeRoot;
use pocketmine\world\World;
use function array_key_exists;

final class BlockDataChunk{

	/** @var LevelDB */
	private $database;

	/** @var BigEndianNbtSerializer */
	private $serializer;

	/** @var BlockData[]|null[] */
	private $block_cache = [];

	/** @var BlockData[]|null[] */
	private $update_queue = [];

	public function __construct(LevelDB $database, BaseNbtSerializer $serializer){
		$this->database = $database;
		$this->serializer = $serializer;
	}

	public function getBlockDataAt(int $x, int $y, int $z) : ?BlockData{
		if(!array_key_exists($hash = World::blockHash($x, $y, $z), $this->block_cache)){
			$buffer = $this->database->get("b" . $hash);
			if($buffer !== false){
				$this->block_cache[$hash] = BlockDataFactory::nbtDeserialize($this->serializer->read($buffer)->mustGetCompoundTag());
			}
		}

		return $this->block_cache[$hash] ?? $this->block_cache[$hash] = null;
	}

	public function setBlockDataAt(int $x, int $y, int $z, ?BlockData $data) : void{
		$this->block_cache[$hash = World::blockHash($x, $y, $z)] = $data;
		$this->update_queue[$hash] = $data;
	}

	public function save() : void{
		if(count($this->update_queue) > 0){
			$batch = new LevelDBWriteBatch();
			foreach($this->update_queue as $hash => $data){
				$data !== null ? $batch->put("b" . $hash, $this->serializer->write(new TreeRoot(BlockDataFactory::nbtSerialize($data)))) : $batch->delete("b" . $hash);
			}
			$this->database->write($batch);
			$this->update_queue = [];
		}
	}
}