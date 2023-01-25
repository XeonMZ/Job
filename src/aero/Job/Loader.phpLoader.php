<?php

	/*
	* The original EconomyJob is written by OneBone
	* Used the FormAPI Library virion by jojoe77777
	* Added Ui and advanced by aero
	* Discord: AeroDEV#
	* Github: https://github.com/XeonMZ/Job
	* Poggit: https://poggit.pmmp.io/p/Job
    */
	
	/** Codes Help
	* onEnable, get datas | line 67
	* readResource | line 82
	* onDisable, save datas | line 94
	* getMessage | line 105
	* onBlockBreak, event | line 117 
	* onBlockPlace, event | line 172
	* onMobDeath, event | line 227
	* onPlayerDeath, event | line 287
	* onCommand | line 350
	* FormJob | line 383
	* FormJobsList | line 448
	* FormJobJoin | line 485
	* FormJobInfo | line 519
	*/

namespace aero\Job;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\entity\Animal;
use pocketmine\entity\Monster;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\utils\TextFormat;
use pocketmine\player\Player;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;

use jojoe77777\FormAPI;
use jojoe77777\FormAPI\SimpleForm;

use onebone\economyapi\EconomyAPI;

class Loader extends PluginBase implements Listener{

	/** @var Config */
	private $jobs;
	/** @var Config */
	private $player;
	/** @var Config */
	private $messages;

	/** @var  EconomyAPI */
	private $api;

	/** @var Loader */
	private static $instance;

	public function onEnable() : void{
		@mkdir($this->getDataFolder());
		$this->saveDefaultConfig();
		$this->saveResource("jobs.yml");
		$this->saveResource("messages.yml");
		$this->jobs = new Config($this->getDataFolder() . "jobs.yml", Config::YAML);
		$this->messages = new Config($this->getDataFolder() . "messages.yml", Config::YAML);
		$this->player = new Config($this->getDataFolder() . "players.yml", Config::YAML);

		$this->getServer()->getPluginManager()->registerEvents($this, $this);

		$this->api = EconomyAPI::getInstance();
		self::$instance = $this;
	}

	private function readResource($res){
		$path = $this->getFile()."resources/".$res;
		$resource = $this->getResource($res);
		if(!is_resource($resource)){
			$this->getLogger()->debug("Tried to load unknown resource ".TextFormat::AQUA.$res.TextFormat::RESET);
			return false;
		}
		$content = stream_get_contents($resource);
		@fclose($content);
		return $content;
	}

	public function onDisable() : void{
		$this->player->save();
	}
	
	/**
	* Get Job messages
	*
	* @param string $id
	*
	* @return string | bool
	*/
	public function getMessage($id){
		if($this->messages->exists($id)){
			return $this->messages->get($id);
		}
		return false;
	}

	/**
	 * @param BlockBreakEvent $event
	 */
	public function onBlockBreak(BlockBreakEvent $event){
		$player = $event->getPlayer();
		$block = $event->getBlock();
		if($event->isCancelled()){
		    return;
		}
		
		if(!$this->player->exists($player->getName())) return;
		$job = $this->jobs->get($this->player->get($player->getName())["JobID"]);
		if($job !== false){
			$player_data = $this->player->get($player->getName());
			$job = $this->jobs->get($player_data["JobID"]);
			if($player_data["Mode"] == "Goal"){
				if(isset($job["Mode"]["Salary"]) and isset($job["Mission"][$block->getID().":".$block->getMeta().":Break"])){
					$nothing = $job["Mission"][$block->getID().":".$block->getMeta().":Break"];
					$progress = $player_data["Progress"];
					$goal = $player_data["Goal"];
					if ($player->hasPermission("job.progress.break") or $player->hasPermission("Job.*")) {
						$progress++;
						$this->player->set($player->getName(), ["JobID" => $player_data["JobID"], "Job" => $player_data["Job"], "Mode" => "Goal", "Progress" => $progress, "Goal" => $job["Mode"]["Goal"]]);
						
						if ($progress >= $goal){
							$salary = $job["Mode"]["Salary"]; 
							$this->api->addMoney($player, $salary);
							$player->sendPopup($this->getMessage("salary-popup-1") . $salary . EconomyAPI::getInstance()->getMonetaryUnit() . $this->getMessage("salary-popup-2"));
							$player->sendMessage("§7[§6Job§7] " . $this->getMessage("salary-retire-message") . $player_data["Job"]);
							$this->player->remove($player->getName());
							$this->player->save();
						}else{
							$player->sendPopup($this->getMessage("progress-popup") . $progress . " / " . $goal);
							$this->player->save();
						}
					}else{
						$player->sendMessage("§7[§6Job§7] " . $this->getMessage("progress-break-noperm-message"));
					}
				}
			}else{
				if(isset($job["Mission"][$block->getID().":".$block->getMeta().":Break"])){
					$money = $job["Mission"][$block->getID().":".$block->getMeta().":Break"];
					if ($player->hasPermission("job.earn.break") or $player->hasPermission("job.*")) {
						if($money > 0){
							$this->api->addMoney($player, $money);
							$player->sendPopup($this->getMessage("earn-popup-1") . $money . EconomyAPI::getInstance()->getMonetaryUnit() . $this->getMessage("earn-popup-2"));
						}else{
							$this->api->reduceMoney($player, $money);
						}
					}else{
						$player->sendMessage("§7[§6Job§7] " . $this->getMessage("break-noperm-message"));
					}
				}
			}
		}
	}

	/**
	 * @param BlockPlaceEvent $event
	 */
	public function onBlockPlace(BlockPlaceEvent $event){
		$player = $event->getPlayer();
		$block = $event->getBlock();
		if($event->isCancelled()){
		    return;
		}
		
		if(!$this->player->exists($player->getName())) return;
		$job = $this->jobs->get($this->player->getAll()[$player->getName()]["JobID"]);
		if($job !== false){
			$player_data = $this->player->get($player->getName());
			$job = $this->jobs->get($player_data["JobID"]);
			if($player_data["Mode"] == "Goal"){
				if(isset($job["Mode"]["Salary"]) and isset($job["Mission"][$block->getID().":".$block->getMeta().":Place"])){
					$nothing = $job["Mission"][$block->getID().":".$block->getMeta().":Place"];
					$progress = $player_data["Progress"];
					$goal = $player_data["Goal"];
					if ($player->hasPermission("job.progress.place") or $player->hasPermission("job.*")) {
						$progress++;
						$this->player->set($player->getName(), ["JobID" => $player_data["JobID"], "Job" => $player_data["Job"], "Mode" => "Goal", "Progress" => $progress, "Goal" => $job["Mode"]["Goal"]]);
						
						if ($progress >= $goal){
							$salary = $job["Mode"]["Salary"]; 
							$this->api->addMoney($player, $salary);
							$player->sendPopup($this->getMessage("salary-popup-1") . $salary . EconomyAPI::getInstance()->getMonetaryUnit() . $this->getMessage("salary-popup-2"));
							$player->sendMessage("§7[§6Job§7] " . $this->getMessage("salary-retire-message") . $player_data["Job"]);
							$this->player->remove($player->getName());
							$this->player->save();
						}else{
							$player->sendPopup($this->getMessage("progress-popup") . $progress . " / " . $goal);
							$this->player->save();
						}
					}else{
						$player->sendMessage("§7[§6Job§7] " . $this->getMessage("progress-place-noperm-message"));
					}
				}
			}else{
				if(isset($job["Mission"][$block->getID().":".$block->getMeta().":Place"])){
					$money = $job["Mission"][$block->getID().":".$block->getMeta().":Place"];
					if ($player->hasPermission("job.earn.place") or $player->hasPermission("job.*")) {
						if($money > 0){
							$this->api->addMoney($player, $money);
							$player->sendPopup($this->getMessage("earn-popup-1") . $money . EconomyAPI::getInstance()->getMonetaryUnit() . $this->getMessage("earn-popup-2"));
						}else{
							$this->api->reduceMoney($player, $money);
						}
					}else{
						$player->sendMessage("§7[§6Job§7] " . $this->getMessage("place-noperm-message"));
					}
				}
			}
		}
	}
	
	/**
	 * @param EntityDeathEvent $event
	 */
	public function onMobDeath(EntityDeathEvent $event){
		$entity = $event->getEntity();
		$cause = $entity->getLastDamageCause();
		
		if($cause instanceof EntityDamageByEntityEvent){
			$player = $cause->getDamager();
			if($player instanceof Player){
				if(!$this->player->exists($player->getName())) return;
				$job = $this->jobs->get($this->player->get($player->getName())["JobID"]);
				if($job !== false){
					$player_data = $this->player->get($player->getName());
					$job = $this->jobs->get($player_data["JobID"]);
					if(!$entity instanceof Player){
						if($player_data["Mode"] == "Goal"){
							if(isset($job["Mode"]["Salary"]) and isset($job["Mission"]["Hunter"])){
								$progress = $player_data["Progress"];
								$goal = $player_data["Goal"];
								if ($player->hasPermission("job.progress.hunter") or $player->hasPermission("job.*")) {
									$progress++;
									$this->player->set($player->getName(), ["JobID" => $player_data["JobID"], "Job" => $player_data["Job"], "Mode" => "Goal", "Progress" => $progress, "Goal" => $job["Mode"]["Goal"]]);
								
									if ($progress >= $goal){
										$salary = $job["Mode"]["Salary"]; 
										$this->api->addMoney($player, $salary);
										$player->sendPopup($this->getMessage("salary-popup-1") . $salary . EconomyAPI::getInstance()->getMonetaryUnit() . $this->getMessage("salary-popup-2"));
										$player->sendMessage("§7[§6Job§7] " . $this->getMessage("salary-retire-message") . $player_data["Job"]);
										$this->player->remove($player->getName());
										$this->player->save();
									}else{
										$player->sendPopup($this->getMessage("progress-popup") . $progress . " / " . $goal);
										$this->player->save();
									}
								}else{
									$player->sendMessage("§7[§6Job§7] " . $this->getMessage("progress-hunter-noperm-message"));
								}
							}
						}else{
							if(isset($job["Mission"]["Hunter"])){
								$money = $job["Mission"]["Hunter"];
								if ($player->hasPermission("job.earn.hunter") or $player->hasPermission("job.*")) {
									if($money > 0){
										$this->api->addMoney($player, $money);
										$player->sendPopup($this->getMessage("earn-popup-1") . $money . EconomyAPI::getInstance()->getMonetaryUnit() . $this->getMessage("earn-popup-2"));
									}else{
										$this->api->reduceMoney($player, $money);
									}
								}else{
									$player->sendMessage("§7[§6Job§7] " . $this->getMessage("hunter-noperm-message"));
								}
							}
						}
					}
				}
			}
		}
	}
	
	/**
	 * @param PlayerDeathEvent $event
	 */
	public function onPlayerDeath(PlayerDeathEvent $event){
		$entity = $event->getEntity();
		$cause = $entity->getLastDamageCause();
		if($cause instanceof EntityDamageByEntityEvent){
			$player = $cause->getDamager();
			if($player instanceof Player){
				if(!$this->player->exists($player->getName())) return;
				$job = $this->jobs->get($this->player->get($player->getName())["JobID"]);
				if($job !== false){
					$player_data = $this->player->get($player->getName());
					$job = $this->jobs->get($player_data["JobID"]);
					if($entity instanceof Player){
						if($player_data["Mode"] == "Goal"){
							if(isset($job["Mode"]["Salary"]) and isset($job["Mission"]["Murderer"])){
								$progress = $player_data["Progress"];
								$goal = $player_data["Goal"];
								if ($player->hasPermission("job.progress.murderer") or $player->hasPermission("job.*")) {
									$progress++;
									$this->player->set($player->getName(), ["JobID" => $player_data["JobID"], "Job" => $player_data["Job"], "Mode" => "Goal", "Progress" => $progress, "Goal" => $job["Mode"]["Goal"]]);
								
									if ($progress >= $goal){
										$salary = $job["Mode"]["Salary"]; 
										$this->api->addMoney($player, $salary);
										$player->sendPopup($this->getMessage("salary-popup-1") . $salary . EconomyAPI::getInstance()->getMonetaryUnit() . $this->getMessage("salary-popup-2"));
										$player->sendMessage("§7[§6Job§7] " . $this->getMessage("salary-retire-message") . $player_data["Job"]);
										$this->player->remove($player->getName());
										$this->player->save();
									}else{
										$player->sendPopup($this->getMessage("progress-popup") . $progress . " / " . $goal);
										$this->player->save();
									}
								}else{
									$player->sendMessage("§7[§6Job§7] " . $this->getMessage("progress-murderer-noperm-message"));
								}
							}
						}else{
							if(isset($job["Mission"]["Murderer"])){
								$money = $job["Mission"]["Murderer"];
								if ($player->hasPermission("jobui.earn.murderer") or $player->hasPermission("jobui.*")) {
									if($money > 0){
										$this->api->addMoney($player, $money);
										$player->sendPopup($this->getMessage("earn-popup-1") . $money . EconomyAPI::getInstance()->getMonetaryUnit() . $this->getMessage("earn-popup-2"));
									}else{
										$this->api->reduceMoney($player, $money);
									}
								}else{
									$player->sendMessage("§7[§6Job§7] " . $this->getMessage("murderer-noperm-message"));
								}
							}
						}
					}
				}
			}
		}
	}
	
	/**
	 * @return Loader
	*/
	public static function getInstance(){
		return static::$instance;
	}

	public function onCommand(CommandSender $sender, Command $command, $label, array $params) : bool{
		if($command->getName() === "job"){
			if(!$sender instanceof Player){
				$sender->sendMessage("Please run this command in-game.");
			}else{
				if ($sender->hasPermission("job.command.job") or $sender->hasPermission("job.*")) {
					$this->FormJob($sender);
				}else{
					$sender->sendMessage("§7[§6Job§7] " . "§cYou can't join a job in this world");
				}
			}
		}
		if($command->getName() === "retire"){
			if(!$sender instanceof Player){
				$sender->sendMessage("Please run this command in-game.");
			}else{
				if ($sender->hasPermission("job.command.retire") or $sender->hasPermission("job.*")){
					if($this->player->exists($sender->getName())){
						$job = $this->player->get($sender->getName())["Job"];
						$sender->sendMessage("§7[§6Job§7] " . $this->getMessage("retire-message") . $job);
						$this->player->remove($sender->getName());
						$this->player->save();
					}else{
						$sender->sendMessage("§7[§6Job§7] " . $this->getMessage("nojob-retire-message"));
					}
				}else{
					$sender->sendMessage("§7[§6Job§7] " . "§cYou can't get retired in this world");
				}
			}
		}
		return true;
	}

	public function FormJob($player){
		$form = new SimpleForm(function (Player $player, int $data = null){
			if($data === null){
				return true;
			}else{
				switch($data){
					case "0":
						$this->FormJobsList($player);
						break;
					
					case "1":
						$this->FormMyJobInfo($player);
						break;
					
					case "2":
						if($this->player->exists($player->getName())){
							if($this->player->get($player->getName())["Mode"] == "Goal"){
								$progress = $this->player->get($player->getName())["Progress"];
								$goal = $this->player->get($player->getName())["Goal"];
								$player->sendMessage("§7[§6Job§7] " . $this->getMessage("myjob-message") . $this->player->get($player->getName())["Job"]);
								$player->sendMessage("§7[§6Job§7] " . $this->getMessage("progress-myjob-message") . $progress . " / " . $goal);
							}else{
								$player->sendMessage("§7[§6Job§7] " . $this->getMessage("myjob-message") . $this->player->get($player->getName())["Job"]);
							}
						}else{
							$player->sendMessage("§7[§6Job§7] " . $this->getMessage("nojob-message"));
						}
					
						break;
					
					case "3":
						if($this->player->exists($player->getName())){
							$job = $this->player->get($player->getName())["Job"];
							$player->sendMessage("§7[§6Job§7] " . $this->getMessage("retire-message") . $job);
							$this->player->remove($player->getName());
							$this->player->save();
						}else{
							$player->sendMessage("§7[§6Job§7] " . $this->getMessage("nojob-retire-message"));
						}
						break;
				}
			}
		});
			
		$form->setTitle($this->getMessage("title-main"));
		if($this->player->exists($player->getName())){
			if($this->player->get($player->getName())["Mode"] == "Goal"){
				$progress = $this->player->get($player->getName())["Progress"];
				$goal = $this->player->get($player->getName())["Goal"];
				$form->setContent($this->getMessage("myjob-text-main") . $this->player->get($player->getName())["Job"] . "\n" . $this->getMessage("progress-myjob-text-main") . $progress . " / " . $goal);
			}else{
				$form->setContent($this->getMessage("myjob-text-main") . $this->player->get($player->getName())["Job"]);
			}
		}else{
			$form->setContent($this->getMessage("nojob-text-main"));
		}			
		$form->addButton($this->getMessage("jobjoin-button-main"));
		$form->addButton($this->getMessage("myjobinfo-button-main"));
		$form->addButton($this->getMessage("myjob-button-main"));
		$form->addButton($this->getMessage("retire-button-main"));
			
		$player->sendForm($form);
		return $form;
	}

	public function FormJobsList($player){
		$form = new SimpleForm(function (Player $player, int $data = null){
			if($data === null){
				return true;
			}else{
				$i = 0;
				foreach($this->jobs->getAll() as $name => $job){
					switch($data){
						case "$i":
							if ($player->hasPermission($job["Permission"]) or $player->hasPermission("job.job.*") or $player->hasPermission("job.*")){
								$this->FormJobJoin($player, $job, $name);
							}else{
								$player->sendMessage("§7[§6Job§7] " . $this->getMessage("jobjoin-noperm-message") . $name);
							}
							break;
					}
				$i++;
				}
				switch($data){
					case "$i":
						$this->FormJob($player);
						break;
				}
			}
		});
			
		$form->setTitle($this->getMessage("title-jobslist"));
		$form->setContent($this->getMessage("text-jobslist"));
		foreach($this->jobs->getAll() as $job){
			$form->addButton($job["Button-Name"], $job["Image-Type"], $job["Image"]);
		}
		$form->addButton($this->getMessage("return-to-mainui-button-jobslist"));
			
		$player->sendForm($form);
		return $form;
	}
	
	public function FormJobJoin($player, $job, $jobid){
		$form = new SimpleForm(function (Player $player, $data = null) use($job, $jobid){ // Thanks to https://github.com/h3xmor/ to help me for use($job, $jobid)
			if($data === null){
				return true;
			}else{
				switch($data){
					case "0":
						if ($job["Mode"]["Name"] == "Goal"){
							$this->player->set($player->getName(), ["JobID" => $jobid, "Job" => $job["Name"], "Mode" => "Goal", "Progress" => 0, "Goal" => $job["Mode"]["Goal"]]);
							$this->player->save();
						}else{
							$this->player->set($player->getName(), ["JobID" => $jobid, "Job" => $job["Name"], "Mode" => "Simple"]);
							$this->player->save();
						}
						$player_job = $this->player->get($player->getName())["Job"];
						$player->sendMessage("§7[§6Job§7] " . $this->getMessage("jobjoin-message") . $player_job);
						break;
					
					case "1":
						$this->FormJobsList($player);
						break;
				}
			}
		});
		
		$form->setTitle($this->getMessage("title-jobjoin"));
		$form->setContent($job["Info"]);
		$form->addButton($this->getMessage("join-button-jobjoin") . $job["Name"]);
		$form->addButton($this->getMessage("return-to-jobslistui-button-jobjoin"));
		
		$player->sendForm($form);
		return $form;
	}

	public function FormMyJobInfo($player){
		$form = new SimpleForm(function (Player $player, $data = null){					
			if($data === null){
				return true;
			}else{
				switch($data){
					case "0":
						break;
					case "1":
						$this->FormJob($player);
						break;
				}
			}
		});
		
		$form->setTitle($this->getMessage("title-myjobinfo"));
		if($this->player->exists($player->getName())){
			$job = $this->jobs->get($this->player->get($player->getName())["JobID"]);
			$form->setContent($job["Info"]);
		}else{
			$form->setContent($this->getMessage("nojob-text-myjobinfo"));
		}
		$form->addButton($this->getMessage("exit-button-myjobinfo"));
		$form->addButton($this->getMessage("return-to-mainui-button-myjobinfo"));
		
		$player->sendForm($form);
		return $form;
	}
}
