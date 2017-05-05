<?php

/*
 * (c) Jeroen van den Enden <info@endroid.nl>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Endroid\SoccerCalendar\Bundle\Controller;

use DateInterval;
use DateTime;
use DateTimeZone;
use Goutte\Client;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\DomCrawler\Crawler;

class SoccerCalendarController extends Controller
{
    /**
     * @Route("/")
     * @Template()
     */
    public function indexAction()
    {
        $competitions = [];

        $competitionUrls = [
            'Netherlands' => 'https://www.vi.nl/competities/nederland/eredivisie/2016-2017/stand',
            'Spain' => 'http://www.vi.nl/competities/stand/primera-division-20162017-spanje.htm',
            'England' => 'http://www.vi.nl/competities/stand/premier-league-20162017-engeland.htm',
            'Germany' => 'http://www.vi.nl/competities/stand/bundesliga-20162017-duitsland.htm',
            'France' => 'http://www.vi.nl/competities/stand/ligue-1-20162017-frankrijk.htm',
            'Italy' => 'http://www.vi.nl/competities/stand/serie-a-20162017-italie.htm',
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
                'url' => $this->generateUrl('endroid_play_calendar_team', ['name' => strtolower($teamName)]),
            ];
            $teams[] = $team;
        });

        return $teams;
    }

    /**
     * @Route("/{name}.ics", requirements={"name" = ".+"})
     */
    public function teamAction($name)
    {
        $events = [];

        $url = 'https://www.vi.nl/teams/nederland/'.$name.'/wedstrijden';

        $client = new Client();
        $client->followRedirects(false);
        $client->getCookieJar()->set(new Cookie('BCPermissionLevel', 'PERSONAL'));

        $crawler = $client->request('GET', $url);
        $crawler->filter('.c-match-overview')->each(function (Crawler $overview) use (&$events) {
            $overview->filter('.matchItemRow')->each(function (Crawler $match) use (&$events) {
                $scoreHome = $match->filter('.c-fixture__team--home')->text();
                $scoreAway = $match->filter('.c-fixture__team--away')->text();
                $time = $match->filter('.c-fixture__status')->text();
                $link = $match->filter('.c-match-overview__link')->attr('href');
                $date = $match->filter('h3')->html();
                if ($time == '') {
                    $score = $scoreHome.' - '.$scoreAway;
                    $time = '00:00';
                }

                $allDay = $time == '';
                $date = DateTime::createFromFormat('Y-d-m H:i', date('Y').'-'.$date.' '.$time, new DateTimeZone('Europe/Amsterdam'));
                $now = new DateTime('now');

                /*
                 * Possible variations
                 *
                 * A. Time 20:45 (item is always in future)
                 * B. Score 1 - 2 (item is always in past or today)
                 */

                if ($score == '') {
                    // In future: either this year or next year
                    if ($date->format('Ymd') < $now->format('Ymd')) {
                        $date->add(new DateInterval('P1Y'));
                    }
                } else {
                    // In past: either this year or previous year
                    // Will not change from now so store result to avoid new requests
                    $matchUrl = $match->filter('.matchScore a')->attr('href');
                    $year = $this->getYearForMatchUrl($matchUrl);
                    while ($date->format('Y') > $year) {
                        $date->sub(new DateInterval('P1Y'));
                    }
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
                    'home' => $this->cleanTeamName($match->filter('.homeClub a')->html()),
                    'away' => $this->cleanTeamName($match->filter('.awayClub a')->html()),
                    'timeStart' => $timeStart,
                    'timeEnd' => $timeEnd,
                    'allDay' => $allDay,
                    'score' => $score,
                    'description' => $link,
                    'address' => '',
                ];

                $events[] = $event;
            });
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

    /**
     * Retrieves the year from the given match URL.
     *
     * @param $url
     *
     * @return mixed
     */
    protected function getYearForMatchUrl($url)
    {
        /** @var AbstractAdapter $cache */
        $cache = $this->get('cache.app');

        $matchId = $this->getMatchIdFromUrl($url);
        $yearCache = $cache->getItem($matchId);

        if ($yearCache->isHit()) {
            return $yearCache->get();
        }

        $html = @file_get_contents($url.'&loadMatch=true');
        preg_match_all('#date_utc="([0-9]{4})#i', $html, $matches);
        $year = $matches[1][0];

        $yearCache->set($matchId, $year);
        $cache->save($yearCache);

        return $year;
    }

    /**
     * Retrieves the match ID from the given URL.
     *
     * @param $url
     *
     * @return mixed
     */
    protected function getMatchIdFromUrl($url)
    {
        $matchId = preg_replace('#^[^0-9]+#i', '', $url);

        return $matchId;
    }
}
