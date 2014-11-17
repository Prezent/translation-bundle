<?php

namespace Prezent\TranslationBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Translation\Dumper\CsvFileDumper;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * Export translations
 *
 * @author Sander Marechal <sander@prezent.nl>
 */
class TranslationExportCommand extends ContainerAwareCommand
{
    /**
     * @{inheritDoc}
     */
    protected function configure()
    {
        $this->setName('translation:export')
             ->addArgument('bundle', InputArgument::REQUIRED, 'The bundle to export')
            ->addArgument('locale', InputArgument::REQUIRED, 'The locale to export')
             ->addArgument('dir', InputArgument::REQUIRED, 'Directory to export to')
             ->setDescription('Export translations to CSV')
             ->setHelp('Export translations to CSV')
        ;
    }

    /**
     * @{inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \Symfony\Component\HttpKernel\Bundle\BundleInterface $bundle */
        $bundle = $this->getApplication()->getKernel()->getBundle($input->getArgument('bundle'));
        $catalogue = new MessageCatalogue($input->getArgument('locale'));

        $loader = $this->getContainer()->get('translation.loader');
        $loader->loadMessages($bundle->getPath() . '/Resources/translations', $catalogue);

        $dumper = new CsvFileDumper();
        $dumper->dump(
            $catalogue,
            array(
                'path' => $input->getArgument('dir')
            )
        );
    }
}
