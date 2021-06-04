<?php

namespace App\Command;

use eIDASCertificate\TrustedList;
use eIDASCertificate\ParseException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EidasCaCommand extends Command
{
    protected static $defaultName = 'eidas:cert:update';
    protected static $defaultDescription = 'Update eidas client authority';

    /**
     * @var KernelInterface
     */
    private $kernel;

    public function __construct(KernelInterface $kernel, string $name = null)
    {
        $this->kernel = $kernel;
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $sslPath = implode(DIRECTORY_SEPARATOR, [$this->kernel->getProjectDir(), 'config', 'ssl']);

        foreach (['https://crc.bg/files/_en/TSL_BG.xml', 'https://ec.europa.eu/tools/lotl/eu-lotl.xml'] as $url) {

            try {
                $trustedList = new TrustedList(file_get_contents($url));
            } catch (ParseException $e) {
                $io->error($e->getMessage());
                return Command::FAILURE;
            }
        }

        $finder = new Finder();
        foreach ($finder->path($sslPath . DIRECTORY_SEPARATOR . 'cert')->name('*.xml') as $file)
        {
            @unlink($file);
        }

        foreach ($trustedList->getTLPointerPaths() as $tlName => $tlPointer) {
            $filename = hash('sha256', $tlName) . '.xml';
            $file = $sslPath . DIRECTORY_SEPARATOR . 'cert' . DIRECTORY_SEPARATOR . $filename;
            file_put_contents($file, file_get_contents($tlPointer['location']));
        }

        return Command::SUCCESS;
    }
}
