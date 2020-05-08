<?php

namespace App\Service;

use App\Entity\Match;
use App\Entity\TypePlayer;
use App\Entity\TeamStatistics;
use App\Entity\Stadium;
use App\Entity\Team;

class MatchBuilder
{

    public function build(string $id, array $logs): Match
    {
        $event = $this->extractStartMatchEvent($logs);

        $dateTime = $this->buildMatchDateTime($event);
        $tournament = $this->extractTournament($event);
        $stadium = $this->buildStadium($event);
        $homeTeam = $this->buildHomeTeam($event);
        $awayTeam = $this->buildAwayTeam($event);
        $match = new Match($id, $dateTime, $tournament, $stadium, $homeTeam, $awayTeam);
        $this->processLogs($match, $logs);
        $this->setHomeTeamStats($homeTeam, $match);
        $this->setAwayTeamStats($awayTeam, $match);

        return $match;
    }

    private function extractStartMatchEvent(array $logs): array
    {
        foreach ($logs as $event) {
            if ($event['type'] !== 'startPeriod') {
                continue;
            }
            if (empty($event['details'])) {
                continue;
            }

            return $event;
        }

        throw new \Exception('Start match event not found.');
    }

    private function buildMatchDateTime(array $startMatchEvent): \DateTime
    {
        return new \DateTime($startMatchEvent['details']['dateTime']);
    }

    private function extractTournament(array $startMatchEvent): string
    {
        return $startMatchEvent['details']['tournament'];
    }

    private function buildStadium(array $startMatchEvent): Stadium
    {
        $stadiumInfo = $startMatchEvent['details']['stadium'];

        return new Stadium($stadiumInfo['country'], $stadiumInfo['city'], $stadiumInfo['stadium']);
    }

    private function buildHomeTeam(array $startMatchEvent): Team
    {
        return $this->buildTeam($startMatchEvent, 1);
    }

    private function buildAwayTeam(array $startMatchEvent): Team
    {
        return $this->buildTeam($startMatchEvent, 2);
    }

    private function buildTeam(array $event, string $teamNumber): Team
    {
        $teamInfo = $event['details']["team$teamNumber"];
        $players = [];
        $typeTeam = $this->getTypeTeam($teamNumber);
        foreach ($teamInfo['players'] as $playerInfo) {
            $players[] = new TypePlayer($playerInfo['number'], $playerInfo['name'], $playerInfo['position']);
        }

        return new Team($teamInfo['title'], $teamInfo['country'], $teamInfo['logo'], $players, $teamInfo['coach'], $typeTeam);
    }

    private function getTypeTeam(string $teamNumber): string
    {
        switch($teamNumber){
            case "1":
                return "home";
                break;
            case "2":
                return "away";
                break;
            default: 
                throw new \Exeption("this type team not exist, possible values: 1 or 2");
        }
    }

    private function processLogs(Match $match, array $logs): void
    {
        $period = 0;
        foreach ($logs as $event) {
            $minute = $event['time'];
            $details = $event['details'];
        
            switch ($event['type']) {
                case 'startPeriod':
                    $period++;

                    $players = $details['team1']['startPlayerNumbers'] ?? [];
                    if (count($players)) {
                        $this->goToStartPlayTeam($match->getHomeTeam(), $players);
                    }
                    $players = $details['team2']['startPlayerNumbers'] ?? [];
                    if (count($players)) {
                        $this->goToStartPlayTeam($match->getAwayTeam(), $players);
                    }
                    break;
                case 'finishPeriod':
                    if ($period === 2) {
                        $this->goToBenchAllPlayers($match->getHomeTeam(), $minute);
                        $this->goToBenchAllPlayers($match->getAwayTeam(), $minute);
                    }
                    break;
                case 'replacePlayer':
                    $team = $this->getTeamByName($match, $details['team']);
                    $team->getPlayer($details['inPlayerNumber'])->goToPlay($minute);
                    $team->getPlayer($details['outPlayerNumber'])->goToBench($minute);
                    break;
                case 'goal':
                    $playerNumber = $details["playerNumber"];
                    $team = $this->getTeamByName($match, $details['team']);
                    $team->addGoal();
                    $player = $team->getPlayer($playerNumber)
                                   ->addGoal();
                    break;
                case 'yellowCard':
                    $playerNumber = $details["playerNumber"];
                    $team = $this->getTeamByName($match, $details['team']);
                    $player = $team->getPlayer($playerNumber)
                                   ->addFall("yellowCard");
                    break;
                case 'redCard':
                    $playerNumber = $details["playerNumber"];
                    $team = $this->getTeamByName($match, $details['team']);
                    $player = $team->getPlayer($playerNumber)
                                   ->addFall("redCard");
                    break;
            }
            $match->addMessage(
                $this->buildMinuteString($period, $event),
                $event['description'],
                $this->buildMessageType($event)
            );
        }
    }

    private function buildMinuteString(int $period, array $event): string
    {
        $time = $event['time'];
        $periodEnd = $period == 1 ? 45 : 90;
        $additionalTime = $time - $periodEnd;
        return $additionalTime > 0 ? "$periodEnd + $additionalTime" : $time;
    }

    private function buildMessageType(array $event): string
    {
        switch ($event['type']) {
            case 'dangerousMoment':
                return Match::DANGEROUS_MOMENT_MESSAGE_TYPE;
            case 'yellowCard':
                return Match::YELLOW_CARD_MESSAGE_TYPE;
            case 'redCard':
                return Match::RED_CARD_MESSAGE_TYPE;
            case 'goal':
                return Match::GOAL_MESSAGE_TYPE;
            case 'replacePlayer':
                return Match::REPLACE_PLAYER_MESSAGE_TYPE;
            default:
                return Match::INFO_MESSAGE_TYPE;
        }
    }

    private function goToStartPlayTeam(Team $team, array $players): void
    {
        foreach ($players as $number) {
            $team->getPlayer($number)->goToPlay(0);
        }
    }

    private function goToBenchAllPlayers(Team $team, int $minute)
    {
        foreach ($team->getPlayersOnField() as $player) {
            $player->goToBench($minute);
        }
    }

    private function getTeamByName(Match $match, string $name): Team
    {
        if ($match->getHomeTeam()->getName() === $name) {
            return $match->getHomeTeam();
        }

        if ($match->getAwayTeam()->getName() === $name) {
            return $match->getAwayTeam();
        }

        throw new \Exception(
            sprintf(
                'Team with name "%s" not found. Available teams: "%s" and "%s".',
                $name,
                $match->getHomeTeam()->getName(),
                $match->getAwayTeam()->getName()
            )
        );
    }

    private function setHomeTeamStats(Team $team, Match $match):void
    {
        $teamStats = new TeamStatistics($team);
        $match->setPositonStatsForTeamHome($teamStats);
    }

    private function setAwayTeamStats(Team $team, Match $match):void
    {
        $teamStats = new TeamStatistics($team);
        $match->setPositonStatsForTeamAway($teamStats);
    }

}