<?php

/*
 * (c) Jeroen van den Enden <info@endroid.nl>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Endroid\SoccerCalendar\Bundle\SoccerCalendarBundle\Controller;

use DateInterval;
use DateTime;
use DateTimeZone;
use Goutte\Client;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\DomCrawler\Crawler;

class SoccerCalendarController extends Controller
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
        'Vr' => 'Fri'
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
        'dec' => 'dec'
    ];

    /**
     * @Route("/", name="endroid_soccer_calendar_index")
     * @Template()
     */
    public function indexAction()
    {
        $competitions = [];

        $competitionUrls = [
            'Netherlands' => 'https://www.vi.nl/competities/nederland/eredivisie/2016-2017/stand',
            'Spain' => 'https://www.vi.nl/competities/spanje/primera-division/2016-2017/stand',
            'England' => 'https://www.vi.nl/competities/engeland/premier-league/2016-2017/stand',
            'Germany' => 'https://www.vi.nl/competities/duitsland/bundesliga/2016-2017/stand',
            'France' => 'https://www.vi.nl/competities/frankrijk/ligue-1/2016-2017/stand',
            'Italy' => 'https://www.vi.nl/competities/italie/serie-a/2016-2017/stand',
        ];

        foreach ($competitionUrls as $name => $url) {
            $teams = $this->getTeams($url);

            $competition = [
                'name' => $name,
                'teams' => $teams,
            ];

            $competitions[] = $competition;
        }

        return ['competitions' => $competitions];
    }

    /**
     * @param $competitionUrl
     * @return array
     */
    protected function getTeams($competitionUrl)
    {
        $teams = [];

        $client = new Client();
        $client->followRedirects(false);
        $client->getCookieJar()->set(new Cookie('BCPermissionLevel', 'PERSONAL'));

        $crawler = $client->request('GET', $competitionUrl);
        $crawler->filter('.c-competition-overview div a')->each(function ($node) use (&$teams) {
            $teamName = $node->filter('.c-competition-overview__cell--hover')->text();
            $team = [
                'name' => $teamName,
                'url' => $this->generateUrl('endroid_soccer_calendar_team', ['name' => strtolower($teamName)]),
            ];
            $teams[] = $team;
        });

        return $teams;
    }

    /**
     * @Route("/{name}.ics", requirements={"name" = ".+"}, name="endroid_soccer_calendar_team")
     * @Template()
     */
    public function teamAction($name)
    {
        $events = [];

        $url = 'https://www.vi.nl/teams/nederland/'.$name.'/wedstrijden';

        $keys = array_combine(array_keys($this->days), array_keys($this->days)) + array_combine(array_keys($this->months), array_keys($this->months));
        $values = $this->days + $this->months;

        $client = new Client();
        $client->followRedirects(false);
        $client->getCookieJar()->set(new Cookie('BCPermissionLevel', 'PERSONAL'));

        $crawler = $client->request('GET', $url);
        $crawler->filter('.c-match-overview')->each(function (Crawler $match) use (&$events, $keys, $values, $url) {
            $teamHome = trim($match->filter('.c-fixture__team-name--home')->text());
            $teamAway = trim($match->filter('.c-fixture__team-name--away')->text());
            $scoreHome = intval($match->filter('.c-fixture__score--home')->text());
            $scoreAway = intval($match->filter('.c-fixture__score--away')->text());
            $time = trim($match->filter('.c-fixture__status')->text());

            $link = $url;
            $match->filter('.c-match-overview__link')->each(function (Crawler $node) use (&$link) {
                $link = $node->attr('href');
            });

            $date = str_replace($keys, $this->days + $this->months, $match->filter('h3')->html());

            $score = '';
            $allDay = false;
            if (!preg_match('#[0-9]{2}:[0-9]{2}#', $time)) {
                $allDay = true;
                $time = '00:00';
                $score = $scoreHome.' - '.$scoreAway;
            }

            // Use date from URL when present (so we don't have to guess the year)
            $dateFromLink = preg_replace('#.*/([0-9]{4}/[0-9]{2}/[0-9]{2})/.*#', '$1', $link);

            if ($dateFromLink != $link) {
                $date = DateTime::createFromFormat('Y/m/d H:i', $dateFromLink.' '.$time);
            } else {
                $date = DateTime::createFromFormat('D j M H:i', $date.' '.$time);
            }

            if ($allDay) {
                $timeStart = $date->format('Ymd\THis\Z');
                $date->add(new DateInterval('P1D'));
                $timeEnd = $date->format('Ymd\THis\Z');
            } else {
                $date->setTimeZone(new DateTimeZone('GMT'));
                $timeStart = $date->format('Ymd\THis\Z');
                $date->add(new DateInterval('PT105M'));
                $timeEnd = $date->format('Ymd\THis\Z');
            }

            $event = [
                'id' => uniqid(),
                'home' => $teamHome,
                'away' => $teamAway,
                'timeStart' => $timeStart,
                'timeEnd' => $timeEnd,
                'allDay' => $allDay,
                'score' => $score,
                'description' => $link
            ];

            $events[] = $event;
        });

        return [
            'name' => ucfirst($name),
            'events' => $events,
        ];
    }

    /**
     * Cleans the team name.
     *
     * @param $teamName
     *
     * @return mixed|string
     */
    protected function cleanTeamName($teamName)
    {
        $teamName = strip_tags($teamName);
        $teamName = preg_replace('#[\r\n\t ]+#', ' ', $teamName);
        $teamName = trim($teamName);

        return $teamName;
    }
}
