<?php

namespace App\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class WasatchJobsQueueManagerCommand extends Command
{
    protected static $defaultName = 'wasatch:jobs:queue-manager';

    protected $arguments;
    protected $options;
    protected $logger;

    public function __construct(LoggerInterface $logger, string $name = null)
    {
        parent::__construct($name);
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this
            ->setDescription('Enqueue files for Wasatch Hotfolder')
            ->addArgument(
                'source', InputArgument::REQUIRED,
                'Location containing files'
            )
            ->addArgument('destination', InputArgument::REQUIRED, 'Wasatch Hotfolder')
            ->addOption(
                'wasatch-file-limit', null, InputOption::VALUE_OPTIONAL,
                'File Limit', 5
            )
            ->addOption(
                'wasatch-file-extension', null, InputOption::VALUE_OPTIONAL,
                'Wasatch valid file extension', '*.xml'
            )
            ->addOption(
                'hosonsoft-path', null, InputOption::VALUE_OPTIONAL,
                'Hosonsoft PrintExp installation directory',
                'C:\Program Files (x86)\PrintExp_V5.6.2.56.R'
            )
            ->addOption(
                'hosonsoft-threshold', null, InputOption::VALUE_OPTIONAL,
                'Hosonsoft print jobs threshold', 2
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->arguments = [
            'source' => $input->getArgument('source'),
            'destination' => $input->getArgument('destination')
        ];
        $this->options = [
            'hosonsoft-path' => $input->getOption('hosonsoft-path'),
            'hosonsoft-threshold' => $input->getOption('hosonsoft-threshold'),
            'wasatch-file-extension' => $input->getOption('wasatch-file-extension'),
            'wasatch-file-limit' => $input->getOption('wasatch-file-limit')
        ];

        $this->logger->notice(
            sprintf('Wasatch Jobs Queue Manager started'),
            ['arguments' => $this->arguments, 'options' => $this->options]
        );

        if ($this->_checkPrintJobsThreshold() <= $this->options['hosonsoft-threshold']) {
            return $this->_moveFilesToWasatchHotfolder($io);
        } else {
            $io->warning('Files not moved, threshold not reached');
        }

        return Command::SUCCESS;
    }


    private function _checkPrintJobsThreshold()
    {
        $fileFinder = new Finder();
        return
            $fileFinder->in(
                $this->options['hosonsoft-path'] . DIRECTORY_SEPARATOR . 'temp')
                ->filter(
                    function (SplFileInfo $file) {
                        $fileFinder = new Finder();
                        if ($file->isDir() && $fileFinder->in($file->getRealPath())->files()->count() > 0) {
                            return true;
                        }
                        return false;
                    }
                )
                ->count();
    }

    private function _moveFilesToWasatchHotfolder(SymfonyStyle $io)
    {
        $fileFinder = new Finder();
        $fileSystem = new Filesystem();
        $filesToMove = $fileFinder->in($this->arguments['source'])->sortByName()
            ->files()->name($this->options['wasatch-file-extension']);

        if($filesToMove->count() > 0) {
            $i = 0;
            foreach ($filesToMove as $fileToMove) {
                if ($i >=$this->options['wasatch-file-limit'] ) {
                    break;
                }
                $fileSystem->rename(
                    $fileToMove->getRealPath(), $this->arguments['destination'] . '/' . $fileToMove->getFilename(), true
                );
                $this->logger->info(
                    sprintf(
                        'File %s moved from %s to %s',
                        $fileToMove->getFilename(),
                        $this->arguments['source'], $this->arguments['destination']
                    )
                );
                $i++;
            }
        }

        return Command::SUCCESS;
    }
}
