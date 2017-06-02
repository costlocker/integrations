<?php

namespace Costlocker\Integrations\Basecamp;

use Symfony\Component\HttpFoundation\Request;

class GetProjects
{
    private $basecampFactory;

    public function __construct(BasecampFactory $b)
    {
        $this->basecampFactory = $b;
    }

    public function __invoke(Request $r)
    {
        $accountId = $r->query->get('account');
        $basecamp = $this->basecampFactory->__invoke($accountId);

        return $basecamp->getProjects();
    }
}
