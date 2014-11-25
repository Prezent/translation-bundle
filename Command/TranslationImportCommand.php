<?php

namespace Prezent\TranslationBundle\Command;

use Prezent\TranslationBundle\Translation\Dumper\YamlFileDumper;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * Import translations
 *
 * @author Sander Marechal <sander@prezent.nl>
 */
class TranslationImportCommand extends ContainerAwareCommand
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('translation:import')
             ->addArgument('bundle', InputArgument::REQUIRED, 'The bundle to import to')
             ->addArgument('locale', InputArgument::REQUIRED, 'The locale to import')
             ->addArgument('dir', InputArgument::REQUIRED, 'Directory to read from')
             ->addOption('inline', 'i', InputOption::VALUE_OPTIONAL, 'The level where you switch to inline YAML', 2)
             ->addOption(
                 'indent',
                 'd',
                 InputOption::VALUE_OPTIONAL,
                 'The amount of spaces to use for indentation of nested nodes',
                 4
             )->setDescription('Import translations from CSV')
             ->setHelp('Import translations from CSV')
        ;
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \Symfony\Component\HttpKernel\Bundle\BundleInterface $bundle */
        $bundle = $this->getApplication()->getKernel()->getBundle($input->getArgument('bundle'));
        $catalogue = new MessageCatalogue($input->getArgument('locale'));

        /** @var \Symfony\Bundle\FrameworkBundle\Translation\TranslationLoader $loader */
        $loader = $this->getContainer()->get('translation.loader');
        $loader->loadMessages($input->getArgument('dir'), $catalogue);

        $dumper = new YamlFileDumper();
        $dumper->setIndent($input->getOption('indent'));
        $dumper->setInline($input->getOption('inline'));

        $dumper->dump(
            $catalogue,
            array(
                'path' => $bundle->getPath() . '/Resources/translations',
            )
        );
    }
}
