<?php

namespace App\Entity;

use App\Entity\Team;
use App\Entity\TypePlayer;

class TeamStatistics
{
    private Team $team;
    private array $players;
    private array $positionStats;
    private int $timeGoalkeeper;
    private int $timeDefenders;
    private int $timeMidfielders;
    private int $timeAttackers;

    public function __construct(Team $team)
    {
        $this->team = $team;
        $this->players = $this->team->getPlayers();
        $this->timeGoalkeeper = 0;
        $this->timetimeDefenders = 0;
        $this->timeMidfielders = 0;
        $this->timeAttackers = 0;
        $this->_setPositionStats();
    }
    
    public function getPositionStats():array
    {
        return $this->positionStats;
    }

    public function getTimeGoalkeeper():int
    {
        return $this->timeGoalkeeper;
    }

    public function getTimeDefenders():int
    {
        return $this->timeDefenders;
    }

    public function getTimeMidfielders():int
    {
        return $this->timeMidfielders;
    }

    public function getTimeAttackers():int
    {
        return $this->timeAttackers;
    }

    private function _getFullTimeAllPlayers():int
    {
        $players = $this->team->getPlayers();
        $time = 0;

        foreach($players as $player){
            $time += $player->getPlayTime();
        }
        return $time;
    }

    private function _getTimePositions()
    {
        $timePositions = [];

        foreach($this->players as $player){
            $type = $player->getType();
            $time = $player->getPlaytime();

            if(!isset($timePositions[$type]) && $time != 0)
                $timePositions[$type] = 0;
            $timePositions[$type] += $time;
        }
        return $timePositions;
    }

    private function _setPositionStats():void
    {
        $fullTime = $this->_getFullTimeAllPlayers();
        $timePositions = $this->_getTimePositions();
        $stats = [];
        foreach($timePositions as $position => $time){
            $percent = $time / $fullTime * 100;
                switch($position){
                   case "В":
                        $this->timeGoalkeeper = $percent;
                        break;
                    case "З":
                        $this->timeDefenders = $percent;
                        break;
                    case "П":
                        $this->timeMidfielders = $percent;
                        break;
                    case "Н":
                        $this->timeAttackers = $percent;
                        break;
                }
        }
    }

}

?>
