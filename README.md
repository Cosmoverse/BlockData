# BlockData
A PocketMine-MP virion that lets plugins set arbitrary data to blocks.

### What's so special about BlockData?
1. Multiple plugins can store data in the same block without overriding each other's data.
2. BlockData never reads the database unless `BlockDataWorld::getBlockDataAt()` is called. So the block data isn't automatically cached when a chunk is loaded.
3. yeahh..

### Why don't you just store data in tiles?
Storing data in tiles is not a problem as long as the number of loaded tiles can be kept moderated. When a chunk loads, the server reads all tiles from the database and caches them.
If you were to store block data for each block in a chunk using tiles, that's 65536 tile instances in the RAM!
To avoid this, BlockData only loads data of blocks when explicitly requested to.

### Are there any drawbacks?
1. Since the virion has a strict policy on caching, a fresh call to `BlockDataWorld::getBlockDataAt()` would read the database synchronously.
If this worries you, BlockData is backed by LevelDB and uses the [snappy](http://google.github.io/snappy/) compression. A call to `LevelDB::get()` hardly takes a millisecond anyway.
BlockData instances once created by the virion are cached until the chunk unloads.
2. There's an inconsistency in deleting block => deleting data. This is due to the fact that blocks can be set by several different ways but there aren't the same number of ways to directly listen for block changes.
Blocks can be changed using `World::setBlockAt()`, `World::setChunk()` or even when the plugin using this virion is disabled and the plugin will never know that the block was deleted, so the BlockData in such cases will exist well after the block has been deleted.
3. Because BlockData doesn't autoload with chunks (unlike tiles), you can't efficiently write BlockData that's always ticking.

### Developer Docs
Install with the Virion 3 standard:
```bash
$ composer require cosmicpe/blockdata
```

The first thing your plugin will have to do to gain access to this virion's API is request a `BlockDataWorldManager` instance.
`BlockDataWorldManager` maps `BlockDataWorld`s to pocketmine's worlds. `BlockDataWorld` provides an API to get and set `BlockData`.
```php
final class MyPlugin extends PluginBase{

	/** @var BlockDataWorldManager */
	private $manager;
	
	protected function onEnable() : void{
		$this->manager = BlockDataWorldManager::create($this);
	}
}
```

Now lets create a BlockData class! BlockData is backed by nbt. (Honestly, might switch over to JSON but not sure if it's worth sacrificing binary-safe data storage).
```php
class BlockHistoryData extends BlockData{ // stores when block was placed and by whom.

	public static function nbtDeserialize(CompoundTag $nbt) : BlockData{
		return new BlockHistoryData($nbt->getString("placer"), $nbt->getLong("timestamp"));
	}
	
	/** @var string */
	private $placer;

	/** @var int */
	private $timestamp;
	
	public function __construct(string $placer, ?int $timestamp = null){
		$this->placer = $placer;
		$this->timestamp = $timestamp ?? time();
	}
	
	public function getPlacer() : string{
		return $this->placer;
	}
	
	public function getTimestamp() : int{
		return $this->timestamp;
	}
	
	public function nbtSerialize() : CompoundTag{
		return CompoundTag::create()
			->setString("placer", $this->placer)
			->setLong("timestamp", $this->timestamp);
	}
}
```

And map it to a string identifier.
```php
const BLOCK_HISTORY_DATA = "blockhistory";
BlockDataFactory::register(self::BLOCK_HISTORY_DATA, BlockHistoryData::class);
```

Wew, now all that's remaining is event handling!
```php
public function onBlockPlace(BlockPlaceEvent $event) : void{
	$block = $event->getBlock();
	$pos = $block->getPos();
	$data = new BlockHistoryData($event->getPlayer()->getName());
	$this->manager->getWorld($pos->getWorld())->setBlockDataAt($pos->x, $pos->y, $pos->z, $data);
}

public function onPlayerInteract(PlayerInteractEvent $event) : void{
	if($event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK && $event->getItem()->getId() === ItemIds::STICK){
		$block = $event->getBlock();
		$pos = $block->getPos();
		
		$data = $this->manager->get($pos->getWorld())->getBlockDataAt($pos->x, $pos->y, $pos->z);
		if($data instanceof BlockHistoryData){
			$event->getPlayer()->sendMessage(TextFormat::LIGHT_PURPLE . "This block was placed by " . TextFormat::WHITE . $data->getPlacer() . TextFormat::LIGHT_PURPLE . " on " . TextFormat::WHITE . gmdate("d-m-Y H:i:s", $data->getTimestamp()));
		}
	}
}
```

Also check out [BlockData-Example-Plugin](https://github.com/Cosmoverse/BlockData-Example-Plugin).
