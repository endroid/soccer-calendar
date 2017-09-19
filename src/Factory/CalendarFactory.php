<?php

/*
 * (c) Jeroen van den Enden <info@endroid.nl>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Endroid\SoccerCalendar\Factory;

use DateInterval;
use DateTime;
use DateTimeZone;
use Endroid\Calendar\Entity\Calendar;
use Endroid\Calendar\Entity\CalendarItem;
use Goutte\Client;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\DomCrawler\Crawler;

class CalendarFactory
{
    /**
     * @var array
     */
    protected $days = [
        'Za' => 'sat',
        'Zo' => 'Sun',
        'Ma' => 'Mon',
        'Di' => 'Tue',
        'Wo' => 'Wed',
        'Do' => 'Thu',
        'Vr' => 'Fri',
    ];

    /**
     * @var array
     */
    protected $months = [
        'jan' => 'jan',
        'feb' => 'feb',
        'mrt' => 'mar',
        'apr' => 'apr',
        'mei' => 'may',
        'jun' => 'jun',
        'jul' => 'jul',
        'aug' => 'aug',
        'sep' => 'sep',
        'okt' => 'oct',
        'nov' => 'nov',
        'dec' => 'dec',
    ];

    /**
     * @param string $teamName
     *
     * @return Calendar
     */
    public function create($teamName)
    {
        $url = 'https://www.vi.nl/teams/nederland/'.$teamName.'/wedstrijden';

        $keys = array_combine(array_keys($this->days), array_keys($this->days)) + array_combine(array_keys($this->months), array_keys($this->months));
        $values = $this->days + $this->months;

        $client = new Client();
        $client->followRedirects(false);
        $client->getCookieJar()->set(new Cookie('BCPermissionLevel', 'PERSONAL'));

        $calendar = new Calendar();

        $crawler = $client->request('GET', $url);
        $crawler->filter('.c-match-overview')->each(function (Crawler $match) use ($calendar, $keys, $values, $url) {
            $teamHome = trim($match->filter('.c-fixture__team-name--home')->text());
            $teamAway = trim($match->filter('.c-fixture__team-name--away')->text());
            $scoreHome = intval($match->filter('.c-fixture__score--home')->text());
            $scoreAway = intval($match->filter('.c-fixture__score--away')->text());
            $time = trim($match->filter('.c-fixture__status')->text());
            $title = $teamHome.' - '.$teamAway;

            $link = $url;
            $match->filter('.c-match-overview__link')->each(function (Crawler $node) use (&$link) {
                $link = $node->attr('href');
            });

            $date = str_replace($keys, $this->days + $this->months, $match->filter('h3')->html());

            $allDay = false;
            if (!preg_match('#[0-9]{2}:[0-9]{2}#', $time)) {
                $allDay = true;
                $time = '00:00';
                $title .= ' ('.$scoreHome.' - '.$scoreAway.')';
            }

            $dateStart = DateTime::createFromFormat('D j M H:i', $date.' '.$time, new DateTimeZOne('Europe/Amsterdam'));

            $dateEnd = clone $dateStart;
            $dateEnd->add(new DateInterval($allDay ? 'P1D' : 'PT105M'));

            $calendarItem = new CalendarItem();
            $calendarItem->setTitle($title);
            $calendarItem->setDateStart($dateStart);
            $calendarItem->setDateEnd($dateEnd);

            $calendarItem->setDescription($link);
            $calendar->addCalendarItem($calendarItem);
        });

        return $calendar;
    }
}
