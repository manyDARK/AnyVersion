<?php

declare(strict_types=1);

namespace AnyVersion;

use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

class AnyVersion extends PluginBase implements Listener
{
	/** @var bool */
	private $showMessage;

	/** @var bool */
	private $useClientFilter;

	/** @var int[] */
	private $clientFilter;

	public function onEnable(): void
	{
		$this->saveDefaultConfig();
		$this->showMessage = $this->getConfig()->get("write-messages-in-log", true);
		$this->useClientFilter = $this->getConfig()->getNested("client-filter.enable", true);
		$this->clientFilter = array_fill_keys($this->getConfig()->getNested("client-filter.allowed-protocols", []), true);
		$this->getServer()->getLogger()->notice("Server protocol version: " . ProtocolInfo::CURRENT_PROTOCOL);
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onDataPacketReceiveEvent(DataPacketReceiveEvent $event): void
	{
		$packet = $event->getPacket();
		if ($packet instanceof LoginPacket) {
			if ($packet->protocol === ProtocolInfo::CURRENT_PROTOCOL) {
				$this->log($packet->username, "is using correct protocol version");
				return;
			}

			if (!in_array($packet->protocol, $this->clientFilter) or !$this->useClientFilter) {
				$this->log($packet->username, "use incorrect protocol version (" . $packet->protocol . ")");
				return;
			}

			$this->log(TextFormat::YELLOW . $packet->username . " use protocol version " . $packet->protocol . " (server version: " . ProtocolInfo::CURRENT_PROTOCOL . ")");
			$this->log(TextFormat::RED . "Warning! " . TextFormat::YELLOW . "Using outdated/outrunning client could damage your server.");
			$this->log(TextFormat::YELLOW . "Use it on your own risk");

			$packet->protocol = ProtocolInfo::CURRENT_PROTOCOL;
		}
	}

	private function log(string ...$message): void
	{
		if ($this->showMessage) {
			$this->getLogger()->info(implode(" ", $message));
		}
	}
}

