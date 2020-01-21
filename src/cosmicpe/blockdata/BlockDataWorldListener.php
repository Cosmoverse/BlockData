<?php

declare(strict_types=1);

namespace cosmicpe\blockdata;

use pocketmine\event\Listener;
use pocketmine\event\plugin\PluginDisableEvent;
use pocketmine\event\world\ChunkLoadEvent;
use pocketmine\event\world\ChunkUnloadEvent;
use pocketmine\event\world\WorldLoadEvent;
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
		$chunk = $event->getChunk();
		$this->manager->get($event->getWorld())->loadChunk($chunk->getX(), $chunk->getZ());
	}

	/**
	 * @param ChunkUnloadEvent $event
	 * @priority MONITOR
	 */
	public function onChunkUnload(ChunkUnloadEvent $event) : void{
		$chunk = $event->getChunk();
		$this->manager->get($event->getWorld())->unloadChunk($chunk->getX(), $chunk->getZ());
	}
}