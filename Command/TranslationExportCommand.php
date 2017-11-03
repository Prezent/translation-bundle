<?php

namespace Prezent\TranslationBundle\Command;

use Prezent\TranslationBundle\Translation\Dumper\ExcelFileDumper;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
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
             )
             ->setDescription('Export translations to Excel')
             ->setHelp('Export translations to Excel')
        ;
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // select the bundles
        if ($bundleName = $input->getOption('bundle')) {
            $bundles = array($this->getApplication()->getKernel()->getBundle($bundleName));
        } else {
            $bundles = $this->getApplication()->getKernel()->getBundles();
        }

        /** @var \Symfony\Component\HttpKernel\Bundle\BundleInterface $bundle */
        $catalogues = [];

        foreach ($bundles as $bundle) {
            if ($catalogue = $this->exportTranslations(
                $bundle,
                $input->getArgument('locale')
            )) {
                $catalogues[] = $catalogue;
            };
        }

        $dumper = new ExcelFileDumper();
        $file = $dumper->dump($catalogues, ['path' => realpath($input->getArgument('dir'))]);

        $output->writeln(sprintf('Generated file \'%s\'', $file));
        exit(0);
    }

    /**
     * Export the translations from a given path
     *
     * @param BundleInterface $bundle
     * @param string          $locale
     * @return array
     */
    private function exportTranslations(BundleInterface $bundle, $locale)
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

        return ['bundle' => $bundle->getName(), 'catalogue' => $catalogue];
    }
}
