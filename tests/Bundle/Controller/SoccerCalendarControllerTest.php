<?php

/*
 * (c) Jeroen van den Enden <info@endroid.nl>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Endroid\QrCode\Tests\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class SoccerCalendarControllerTest extends WebTestCase
{
    public function testIndexAction()
    {
        $client = static::createClient();
        $client->request('GET', $client->getContainer()->get('router')->generate('endroid_soccer_calendar_index'));

        $response = $client->getResponse();
        echo $response->getContent();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testTeamAction()
    {
        $client = static::createClient();
        $client->request('GET', $client->getContainer()->get('router')->generate('endroid_soccer_calendar_team', ['name' => 'ajax']));

        $response = $client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertTrue(strpos($response->getContent(), 'BEGIN:VCALENDAR') === 0);
    }
}
