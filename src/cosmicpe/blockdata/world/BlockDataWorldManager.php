<?php

declare(strict_types=1);

namespace cosmicpe\blockdata\world;

use BadMethodCallException;
use pocketmine\plugin\Plugin;
use pocketmine\world\World;

final class BlockDataWorldManager{

	public static function create(Plugin $plugin) : BlockDataWorldManager{
		static $created = [];
		if(isset($created[$name = $plugin->getName()])){
			throw new BadMethodCallException("Tried to create BlockDataWorldManager twice as " . $name);
		}

		$created[$name] = true;
		$instance = new self($plugin);
		$plugin->getServer()->getPluginManager()->registerEvents(new BlockDataWorldListener($plugin, $instance), $plugin);
		return $instance;
	}

	/** @var Plugin */
	private $plugin;

	/** @var BlockDataWorld[] */
	private $worlds = [];

	private function __construct(Plugin $plugin){
		$this->plugin = $plugin;
	}

	public function isLoaded(World $world) : bool{
		return isset($this->worlds[$world->getId()]);
	}

	public function load(World $world) : void{
		$this->worlds[$world->getId()] = new BlockDataWorld($this->plugin, $world);
	}

	public function unload(World $world) : void{
		$this->worlds[$id = $world->getId()]->close();
		unset($this->worlds[$id]);
	}

	public function unloadAll() : void{
		foreach($this->worlds as $instance){
			$this->unload($instance->getWorld());
		}
	}

	public function get(World $world) : BlockDataWorld{
		return $this->worlds[$world->getId()];
	}

	public function save(World $world) : void{
		$this->worlds[$world->getId()]->save();
	}
}