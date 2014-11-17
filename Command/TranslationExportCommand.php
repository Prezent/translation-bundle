<?php

namespace Prezent\TranslationBundle\Command;

use Prezent\TranslationBundle\Translation\Dumper\ExcelFileDumper;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Translation\Dumper\CsvFileDumper;
use Symfony\Component\Translation\Dumper\DumperInterface;
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
             ->addOption('excel', 'x', InputOption::VALUE_NONE, 'Export as Excel file')
             ->setDescription('Export translations to CSV or Excel')
             ->setHelp('Export translations to CSV or Excel')
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

        /** @var \Symfony\Bundle\FrameworkBundle\Translation\TranslationLoader $loader */
        $loader = $this->getContainer()->get('translation.loader');
        $loader->loadMessages($bundle->getPath() . '/Resources/translations', $catalogue);

        // export in desired format
        if ($input->getOption('excel')) {
            // check if the PHPExcel library is installed
            if (!class_exists('PHPExcel')) {
                throw new \RuntimeException(
                    'PHPExcel library is not installed. Please do so if you want to export translations as Excel file.'
                );
            };
            $dumper = new ExcelFileDumper();
        } else {
            $dumper = new CsvFileDumper();
        }

        /** @var DumperInterface $dumper */
        $generatedFiles = $dumper->dump($catalogue, array('path' => realpath($input->getArgument('dir'))));

        // output the result, if the dumper returned it
        if ($generatedFiles) {
            foreach ($generatedFiles as $file) {
                $output->writeln(sprintf('Generated file \'%s\'', $file));
            }
        }
    }
}
