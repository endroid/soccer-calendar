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
    public function testGenerateAction()
    {
        $client = static::createClient();
        $client->request('GET', $client->getContainer()->get('router')->generate('endroid_guide_index'));

        $response = $client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }
}
