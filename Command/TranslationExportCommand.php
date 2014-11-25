<?php

namespace Prezent\TranslationBundle\Command;

use Prezent\TranslationBundle\Translation\Dumper\CsvFileDumper;
use Prezent\TranslationBundle\Translation\Dumper\ExcelFileDumper;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
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
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('translation:export')
             ->addArgument('locale', InputArgument::REQUIRED, 'The locale to export')
             ->addArgument('dir', InputArgument::REQUIRED, 'Directory to export to')
             ->addOption(
                 'bundle',
                 'b',
                 InputOption::VALUE_REQUIRED,
                 'The bundle to export. If empty, all bundles are exported'
             )->addOption('excel', 'x', InputOption::VALUE_NONE, 'Export as Excel file')
             ->setDescription('Export translations to CSV or Excel')
             ->setHelp('Export translations to CSV or Excel')
        ;
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $allFiles = array();
        $bundles = array();

        // select the bundles
        if ($bundleName = $input->getOption('bundle')) {
            $bundles = array($this->getApplication()->getKernel()->getBundle($bundleName));
        } else {
            $bundles = $this->getApplication()->getKernel()->getBundles();
        }

        /** @var \Symfony\Component\HttpKernel\Bundle\BundleInterface $bundle */
        foreach ($bundles as $bundle) {
            $generatedFiles = $this->exportTranslations(
                $bundle,
                $input->getArgument('locale'),
                realpath($input->getArgument('dir')),
                $input->getOption('excel')
            );

            $allFiles = array_merge($allFiles, $generatedFiles);
        }

        // output the result, if the dumper returned it
        foreach ($allFiles as $file) {
            $output->writeln(sprintf('Generated file \'%s\'', $file));
        }
    }

    /**
     * Export the translations from a given path
     *
     * @param BundleInterface $bundle
     * @param string          $locale
     * @param string          $outputDir
     * @param bool            $excel
     * @return array
     */
    private function exportTranslations(BundleInterface $bundle, $locale, $outputDir, $excel = false)
    {
        // if the bundle does not have a translation dir, continue to the next one
        $translationPath = sprintf('%s%s', $bundle->getPath(), '/Resources/translations');
        if (!is_dir($translationPath)) {
            return array();
        }

        // create a catalogue, and load the messages
        $catalogue = new MessageCatalogue($locale);
        /** @var \Symfony\Bundle\FrameworkBundle\Translation\TranslationLoader $loader */
        $loader = $this->getContainer()->get('translation.loader');
        $loader->loadMessages($translationPath, $catalogue);

        // export in desired format
        if ($excel) {
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
        return $dumper->dump($catalogue, array('path' => $outputDir, 'bundleName' => $bundle->getName()));
    }
}
