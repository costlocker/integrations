<?php

namespace Costlocker\Integrations\Fakturoid;

use Costlocker\Integrations\FakturoidClient;
use Costlocker\Integrations\Auth\GetUser;
use Costlocker\Integrations\Database\Database;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class CreateSubject
{
    private $client;
    private $getUser;
    private $database;

    public function __construct(FakturoidClient $c, GetUser $u, Database $dm)
    {
        $this->client = $c;
        $this->getUser = $u;
        $this->database = $dm;
    }

    public function __invoke(Request $r)
    {
        $account = $this->getUser->getFakturoidAccount();
        $response = $this->client->__invoke(
            '/subjects.json',
            [
                'name' => $r->request->get('name'),
            ]
        );

        if ($response->getStatusCode() != 201) {
            return new JsonResponse(['error' => (string) $response->getBody()], 400);
        }

        $subject = json_decode($response->getBody(), true);
        $account->addSubjects([$subject]);
        $this->database->persist($account);
        return new JsonResponse(['id' => $subject['id']]);
    }
}
