<?php

declare(strict_types=1);

/*
 * (c) Jeroen van den Enden <info@endroid.nl>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Endroid\SoccerCalendar\Factory;

use DateInterval;
use DateTime;
use Endroid\Calendar\Entity\Calendar;
use Endroid\Calendar\Entity\CalendarItem;
use Endroid\SoccerData\Entity\Match;
use Endroid\SoccerData\Entity\Team;

final class CalendarFactory
{
    public function createTeamCalendar(Team $team): Calendar
    {
        $calendar = new Calendar();

        foreach ($team->getMatches() as $match) {
            $calendarItem = new CalendarItem();
            $calendarItem->setTitle($this->getTitle($match));
            $calendarItem->setDateStart(clone $match->getDate());
            $calendarItem->setDateEnd($this->getDateEnd($match));
//            $calendarItem->setDescription($match->getId());
            $calendar->addCalendarItem($calendarItem);
        }

        return $calendar;
    }

    private function getTitle(Match $match): string
    {
        return $match->getTeamHome()->getName().' - '.$match->getTeamAway()->getName();
    }

    private function getDateEnd(Match $match): DateTime
    {
        $dateEnd = clone $match->getDate();

        if ('00:00' === $dateEnd->format('H:i')) {
            $dateEnd->add(new DateInterval('P1D'));
        } else {
            $dateEnd->add(new DateInterval('PT105M'));
        }

        return $dateEnd;
    }
}
