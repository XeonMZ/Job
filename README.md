# Job
* __!!! IMPORTANT !!! if you have installed this plugin before, remove the `plugin_data/Job` folder then the new Yaml files will be created.__
* __Simple Mode & Goal Mode Jobs are supported. By joining SimpleMode Jobs when you do 1 mission you will earn the specified money. By joining GoalMode Jobs when you complete the Goal you will earn the specified salary. You can change the Modes of jobs in `plugin_data/Job/jobs.yml`__
* __Customisable and Infinite Jobs are supported. You can edit or add jobs in `plugin_data/Job/jobs.yml`.__
* __Customisable Messages are supported. You can change them in `plugin_data/Job/messages.yml`__
* I changed the default permissions to "op" to prevent earning money in a "Build and Mine Protected World" like Lobby.
* You will learn how to let players Join Jobs and Earn Money only in a specified world, but before you need to install the PurePerms plugin.
### Default Jobs
* __Tree-Cutter:__ By joining this job, when you break any kind of logs with any directions you will earn 25$.
* __Miner:__ By joining this job, when you break Stone you will earn 25$, when you break Coal Ore you will earn 30$ and when you break Iron Ore you will earn 35$.
* __Hunter:__ By joining this job, when you kill a Mob(Animal or Monster) you will earn 30$.
* __Murderer:__ By joining this job, when you kill a Player you will earn 50$.
* __Tree-Cutter-Goal:__ By joining this job, when you break 20 Blocks of any kind of logs with any directions you will earn 500$.
* __Miner-Goal:__ By joining this job, when you break 20 Blocks of Stone, Coal ore or Iron ore you will earn 600$.
* __Hunter-Goal:__ By joining this job, when you kill 20 Mobs(Animal or Monster) you will earn 600$.
* __Murderer-Goal:__ By joining this job, when you kill 20 Players you will earn 1000$.
### Dependencies:
* [EconomyAPI by onebone](https://poggit.pmmp.io/p/EconomyAPI/) [[Download]](https://poggit.pmmp.io/r/34531/EconomyAPI.phar) [[GitHub]](https://github.com/poggit-orphanage/EconomyS/) __(Required)__
* [PurePerms by 64FF00](https://poggit.pmmp.io/p/PurePerms/) [[Download]](https://poggit.pmmp.io/r/70018/PurePerms.phar) [[GitHub]](https://github.com/poggit-orphanage/PurePerms/) (Optional)
* [MineReset by falkirks](https://poggit.pmmp.io/p/MineReset/) [[Download]](https://poggit.pmmp.io/r/40667/MineReset.phar) [[GitHub]](https://github.com/falkirks/MineReset/) (Optional)
* [PureEntitiesX by RevivalPMMP](https://poggit.pmmp.io/p/PureEntitiesX/) [[Download]](https://poggit.pmmp.io/r/93487/PureEntitiesX.phar) [[GitHub]](https://github.com/RevivalPMMP/PureEntitiesX/) (Optional)
### How to let players Join Jobs and Earn Money only in a specified world?
* As I said you need to install the PurePerms plugin.
* __*Before completing the next steps, make sure that `enable-multiworld-perms` has been set to `true` in `plugin_data/PurePerms/config.yml`.*__
* Open this file path `plugin_data/PurePerms/groups.yml`. Then the only thing that you should do is to add the world and permissions to the Group you want. I will give an example below:
```yaml
---
Guest:
  alias: gst
  isDefault: true
  inheritance: []
  permissions:
  worlds:
    Mine:
      isDefault: true
      permissions:
      - job.job.tree-cutter
      - job.job.miner
      - job.job.tree-cutter-goal
      - job.job.miner-goal
      - job.earn.break
      - job.progress.break
    Survival:
      isDefault: true
      permissions:
      - job.job.hunter
      - job.job.hunter-goal
      - job.earn.hunter
      - job.progress.hunter
    PvP: 
      isDefault: true
      permissions:
      - job.job.murderer
      - job.job.murderer-goal
      - job.earn.murderer
      - job.progress.murderer
    
...
```
* If a player is in the Guest group, he will be able to join "Tree-Cutter" & "Miner" & "Tree-Cutter-Goal" & "Miner-Goal" job only in the "Mine" world
* If a player is in the Guest group, he will be able to join "Hunter" & "Hunter-Goal" job only in the "Survival" world
* If a player is in the Guest group, he will be able to join "Murderer" & "Murderer-Goal" job only in the "PvP" world
* Also, he will be able to earn money or increase the progress of a job by a "Breaking" MissionType only in the "Mine" world
* Also, he will be able to earn money or increase the progress of a job by a "Hunter" MissionType only in the "Survival" world
* Also, he will be able to earn money or increase the progress of a job by a "Murderer" MissionType only in the "PvP" world

### To-Do list
* [X] Adding Customizable feature for jobs, so that you will be able to add more jobs
* [X] Adding Customizable feature for texts of the UI and every messages
* [X] Adding Customizable feature for Button names and Images for JobJoinUI
* [X] Adding "Hunter" & "Murderer" MissionType
* [X] Adding Job Modes(Simple Mode and Goal Mode)
### Permissions and Commands:
Permission | Command | Default | About
---------- | ------- | ------- | -----
job.* | - | op | Able to use all commands, all earning ways and join all of the jobs of JobUI plugin
job.job.* | - | op | Able to join all of the jobs
job.command.job | /job | true | Able to see the UI of the Jobs
job.command.retire | /retire | true | Able to be retired 
job.earn.break | - | op | Able to earn money by a Breaking Job
job.earn.place | - | op | Able to earn money by a Placing Job
job.earn.hunter | - | op | Able to earn money by a Killing Mobs Job
job.earn.murderer | - | op | Able to increase the progress of a job by a Killing Players Job
job.progress.break | - | op | Able to increase the progress of a job by a Breaking Job
job.progress.place | - | op | Able to increase the progress of a job by a Placing Job
job.progress.hunter | - | op | Able to increase the progress of a job by a Killing Mobs Job
job.progress.murderer | - | op | Able to increase the progress of a job by a Killing Players Job
* You can edit each Job permission in `plugin_data/Job/jobs.yml`
