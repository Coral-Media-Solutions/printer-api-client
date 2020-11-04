<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class HotXmlLogCommand extends ApiClientCommand
{
    protected static $defaultName = 'wasatch:hot-xml-log';

    protected function configure()
    {
        $this
            ->setDescription('Submit log data to API server.')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->authenticationToken = $this->getAuthenticationToken();

        $apiParams = $this->params->get('API');

        $response = $this->httpClient->request('GET', $apiParams['API_URL'] . '/security/users', [
            'headers' => ['Authorization' => 'Bearer ' . $this->authenticationToken]
        ]);

        $io->success('Authorized Successfully');

        return Command::SUCCESS;
    }
}
