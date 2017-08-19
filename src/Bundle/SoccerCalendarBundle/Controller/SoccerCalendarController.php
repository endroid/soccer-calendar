<?php

/*
 * (c) Jeroen van den Enden <info@endroid.nl>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Endroid\SoccerCalendar\Bundle\SoccerCalendarBundle\Controller;

use Endroid\Calendar\Writer\IcalWriter;
use Goutte\Client;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Response;

class SoccerCalendarController extends Controller
{
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
     */
    public function teamAction($name)
    {
        $calendar = $this->get('endroid_soccer_calendar.factory.calendar_factory')->create($name);

        $writer = new IcalWriter();

        return new Response($writer->writeToString($calendar));
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
