<?php

declare(strict_types=1);

/*
 * (c) Jeroen van den Enden <info@endroid.nl>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Endroid\SoccerCalendar\Factory;

use Endroid\Calendar\Model\Calendar;
use Endroid\Calendar\Model\CalendarItem;
use Endroid\SoccerData\Entity\Team;
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
                $game->getId(),
                $game->getDate(),
                $game->getDateEnd()
            );
        }

        return new Calendar($team->getName(), $calendarItems);
    }
}
