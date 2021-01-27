<?php

declare(strict_types=1);

/*
 * (c) Jeroen van den Enden <info@endroid.nl>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Endroid\SoccerCalendar\Tests\Factory;

use Endroid\Calendar\Model\Calendar;
use Endroid\Calendar\Writer\IcalWriter;
use Endroid\SoccerCalendar\Factory\CalendarFactory;
use Endroid\SoccerData\Entity\Competition;
use Endroid\SoccerData\Vi\Client;
use Endroid\SoccerData\Vi\Loader\CompetitionLoader;
use Endroid\SoccerData\Vi\Loader\GameLoader;
use Endroid\SoccerData\Vi\Loader\TeamLoader;
use PHPUnit\Framework\TestCase;

class CalendarFactoryTest extends TestCase
{
    /** @testdox Load competition, teams and create calendar */
    public function testLoadAndCreate()
    {
        $client = new Client();

        $competitionLoader = new CompetitionLoader($client);
        $competition = $competitionLoader->loadByName('Eredivisie');

        $this->assertInstanceOf(Competition::class, $competition);

        $teamLoader = new TeamLoader($client);
        $teams = $teamLoader->loadByCompetition($competition);

        $this->assertArrayHasKey('Ajax', $teams);

        $ajax = $teams['Ajax'];

        $matchLoader = new GameLoader($client, $teamLoader);
        $matchLoader->loadByTeam($ajax);

        $calendarFactory = new CalendarFactory();
        $calendar = $calendarFactory->createTeamCalendar($ajax);

        $this->assertInstanceOf(Calendar::class, $calendar);

        $icalWriter = new IcalWriter();
        $icalData = $icalWriter->writeToString($calendar, new \DateTimeImmutable(), new \DateTimeImmutable('+1 year'));

        $this->assertStringContainsString('BEGIN:VCALENDAR', $icalData);
        $this->assertStringContainsString('END:VCALENDAR', $icalData);
    }
}
