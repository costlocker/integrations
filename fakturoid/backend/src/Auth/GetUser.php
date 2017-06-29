<?php

namespace Costlocker\Integrations\Auth;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Costlocker\Integrations\Entities\CostlockerUser;

class GetUser
{
    private $session;
    private $entityManager;

    private $costlockerUser;

    public function __construct(SessionInterface $s, EntityManagerInterface $em)
    {
        $this->session = $s;
        $this->entityManager = $em;
    }

    public function __invoke()
    {
        return new JsonResponse([
            'fakturoid' => $this->session->get('fakturoid')['account'] ?? null,
            'costlocker' => $this->session->get('costlocker')['account'] ?? null,
            'csrfToken' => $this->session->get('csrfToken'),
        ]);
    }

    public function getCostlockerAccessToken()
    {
        return $this->session->get('costlocker')['accessToken']['access_token'];
    }

    public function getCostlockerUser()
    {
        if (!$this->costlockerUser) {
            $userId = $this->session->get('costlocker')['userId'] ?? 0;
            $user = $this->entityManager->getRepository(CostlockerUser::class)
                ->find($userId);
            $this->costlockerUser = $user;
        }
        return $this->costlockerUser ?: new CostlockerUser();
    }
}
