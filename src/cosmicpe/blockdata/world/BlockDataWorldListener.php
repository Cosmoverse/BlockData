<?php

declare(strict_types=1);

namespace cosmicpe\blockdata\world;

use pocketmine\event\Listener;
use pocketmine\event\plugin\PluginDisableEvent;
use pocketmine\event\world\ChunkLoadEvent;
use pocketmine\event\world\ChunkUnloadEvent;
use pocketmine\event\world\WorldLoadEvent;
use pocketmine\event\world\WorldSaveEvent;
use pocketmine\event\world\WorldUnloadEvent;
use pocketmine\plugin\Plugin;
use pocketmine\Server;

final class BlockDataWorldListener implements Listener{

	/** @var Plugin */
	private $plugin;

	/** @var BlockDataWorldManager */
	private $manager;

	public function __construct(Plugin $plugin, BlockDataWorldManager $manager){
		$this->plugin = $plugin;
		$this->manager = $manager;
		foreach(Server::getInstance()->getWorldManager()->getWorlds() as $world){
			$this->manager->load($world);
		}
	}

	public function onPluginDisable(PluginDisableEvent $event) : void{
		if($event->getPlugin() === $this->plugin){
			$this->manager->unloadAll();
		}
	}

	/**
	 * @param WorldLoadEvent $event
	 * @priority MONITOR
	 */
	public function onWorldLoad(WorldLoadEvent $event) : void{
		$this->manager->load($event->getWorld());
	}

	/**
	 * @param WorldSaveEvent $event
	 * @priority MONITOR
	 */
	public function onWorldSave(WorldSaveEvent $event) : void{
		$world = $event->getWorld();
		if($world->getAutoSave() && $this->manager->isLoaded($world)){
			$this->manager->save($world);
		}
	}

	/**
	 * @param WorldUnloadEvent $event
	 * @priority MONITOR
	 */
	public function onWorldUnload(WorldUnloadEvent $event) : void{
		$this->manager->unload($event->getWorld());
	}

	/**
	 * @param ChunkLoadEvent $event
	 * @priority MONITOR
	 */
	public function onChunkLoad(ChunkLoadEvent $event) : void{
		$this->manager->get($event->getWorld())->loadChunk($event->getChunkX(), $event->getChunkZ());
	}

	/**
	 * @param ChunkUnloadEvent $event
	 * @priority MONITOR
	 */
	public function onChunkUnload(ChunkUnloadEvent $event) : void{
		$world = $event->getWorld();
		if($this->manager->isLoaded($world)){
			$this->manager->get($world)->unloadChunk($event->getChunkX(), $event->getChunkZ());
		}
	}
}