<?php

/*
 * (c) Jeroen van den Enden <info@endroid.nl>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Endroid\SoccerCalendar\Tests\Factory;

use Endroid\SoccerCalendar\Factory\CalendarFactory;
use Endroid\SoccerData\Entity\Team;
use PHPUnit\Framework\TestCase;

class CalendarFactoryTest extends TestCase
{
    public function testCreate()
    {
        $team = new Team(null, 'Ajax');

        $calendarFactory = new CalendarFactory();
        $calendar = $calendarFactory->createTeamCalendar($team);

        var_dump($calendar);
        die;
    }
}
