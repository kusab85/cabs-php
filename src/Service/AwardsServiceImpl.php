<?php

namespace LegacyFighter\Cabs\Service;

use LegacyFighter\Cabs\Common\Clock;
use LegacyFighter\Cabs\Config\AppProperties;
use LegacyFighter\Cabs\DTO\AwardsAccountDTO;
use LegacyFighter\Cabs\Entity\AwardsAccount;
use LegacyFighter\Cabs\Entity\Client;
use LegacyFighter\Cabs\Entity\Miles\AwardedMiles;
use LegacyFighter\Cabs\Entity\Miles\ConstantUntil;
use LegacyFighter\Cabs\Repository\AwardedMilesRepository;
use LegacyFighter\Cabs\Repository\AwardsAccountRepository;
use LegacyFighter\Cabs\Repository\ClientRepository;
use LegacyFighter\Cabs\Repository\TransitRepository;

class AwardsServiceImpl implements AwardsService
{
    private AwardsAccountRepository $accountRepository;
    private AwardedMilesRepository $milesRepository;
    private ClientRepository $clientRepository;
    private TransitRepository $transitRepository;
    private Clock $clock;
    private AppProperties $appProperties;

    public function __construct(
        AwardsAccountRepository $accountRepository,
        AwardedMilesRepository $milesRepository,
        ClientRepository $clientRepository,
        TransitRepository $transitRepository,
        Clock $clock,
        AppProperties $appProperties)
    {
        $this->accountRepository = $accountRepository;
        $this->milesRepository = $milesRepository;
        $this->clientRepository = $clientRepository;
        $this->transitRepository = $transitRepository;
        $this->clock = $clock;
        $this->appProperties = $appProperties;
    }

    public function findBy(int $clientId): AwardsAccountDTO
    {
        return AwardsAccountDTO::from($this->accountRepository->findByClient($this->clientRepository->getOne($clientId)));
    }

    public function registerToProgram(int $clientId): void
    {
        $client = $this->clientRepository->getOne($clientId);
        if($client === null) {
            throw new \InvalidArgumentException('Client does not exists, id = '.$clientId);
        }

        $this->accountRepository->save(AwardsAccount::notActiveAccount($client, $this->clock->now()));
    }

    public function activateAccount(int $clientId): void
    {
        $account = $this->accountRepository->findByClient($this->clientRepository->getOne($clientId));
        if($account === null) {
            throw new \InvalidArgumentException('Account does not exists, id = '.$clientId);
        }

        $account->activate();
        $this->accountRepository->save($account);
    }

    public function deactivateAccount(int $clientId): void
    {
        $account = $this->accountRepository->findByClient($this->clientRepository->getOne($clientId));
        if($account === null) {
            throw new \InvalidArgumentException('Account does not exists, id = '.$clientId);
        }

        $account->deactivate();
        $this->accountRepository->save($account);
    }

    public function registerMiles(int $clientId, int $transitId): ?AwardedMiles
    {
        $account = $this->accountRepository->findByClient($this->clientRepository->getOne($clientId));
        $transit = $this->transitRepository->getOne($transitId);
        if($transit === null) {
            throw new \InvalidArgumentException('Transit does not exists, id = '.$transitId);
        }

        $now = $this->clock->now();
        if($account === null || !$account->isActive()) {
            return null;
        } else {
            $expireAt = $now->modify(sprintf('+%s days', $this->appProperties->getMilesExpirationInDays()));
            $miles = $account->addExpiringMiles($this->appProperties->getDefaultMilesBonus(), $expireAt, $transit, $now);
            $this->accountRepository->save($account);
            return $miles;
        }
    }

    public function registerNonExpiringMiles(int $clientId, int $miles): AwardedMiles
    {
        $account = $this->accountRepository->findByClient($this->clientRepository->getOne($clientId));

        if($account === null) {
            throw new \InvalidArgumentException('Account does not exists, id = '.$clientId);
        } else {
            $_miles = $account->addNonExpiringMiles($miles, $this->clock->now());
            $this->accountRepository->save($account);
            return $_miles;
        }
    }

    public function removeMiles(int $clientId, int $miles): void
    {
        $client = $this->clientRepository->getOne($clientId);
        $account = $this->accountRepository->findByClient($client);

        if($account===null) {
            throw new \InvalidArgumentException('Account does not exists, id = '.$clientId);
        }

        $account->remove(
            $miles,
            $this->clock->now(),
            count($this->transitRepository->findByClient($client)),
            count($client->getClaims()),
            $client->getType(),
            $this->isSunday()
        );
    }

    public function calculateBalance(int $clientId): int
    {
        $client = $this->clientRepository->getOne($clientId);
        $account = $this->accountRepository->findByClient($client);
        return $account->calculateBalance($this->clock->now());
    }

    public function transferMiles(int $fromClientId, int $toClientId, int $miles): void
    {
        $fromClient = $this->clientRepository->getOne($fromClientId);
        $accountFrom = $this->accountRepository->findByClient($fromClient);
        $accountTo = $this->accountRepository->findByClient($this->clientRepository->getOne($toClientId));
        if($accountFrom === null) {
            throw new \InvalidArgumentException('Account does not exists, id = '.$fromClientId);
        }
        if($accountTo === null) {
            throw new \InvalidArgumentException('Account does not exists, id = '.$toClientId);
        }
        $accountFrom->moveMilesTo($accountTo, $miles, $this->clock->now());
        $this->accountRepository->save($accountFrom);
        $this->accountRepository->save($accountTo);
    }

    private function isSunday(): bool
    {
        return $this->clock->now()->format('l') === 'Sunday';
    }
}
