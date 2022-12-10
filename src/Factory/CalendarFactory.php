<?php

declare(strict_types=1);

namespace Endroid\SoccerCalendar\Factory;

use Endroid\Calendar\Model\Calendar;
use Endroid\Calendar\Model\CalendarItem;
use Endroid\SoccerData\Model\Team;
use Symfony\Component\Uid\Uuid;

final class CalendarFactory
{
    public function createTeamCalendar(Team $team): Calendar
    {
        $calendarItems = [];
        foreach ($team->getGames() as $game) {
            $calendarItems[] = new CalendarItem(
                strval(Uuid::v4()),
                $game->getTitle(),
                $game->id,
                $game->getDate(),
                $game->getDateEnd()
            );
        }

        return new Calendar($team->name, $calendarItems);
    }
}
