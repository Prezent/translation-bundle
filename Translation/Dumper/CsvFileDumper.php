<?php

namespace Prezent\TranslationBundle\Translation\Dumper;

use Symfony\Component\Translation\Dumper\CsvFileDumper as BaseFileDumper;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * CsvFileDumper that returns its generated files
 *
 * @author Robert-Jan Bijl <robert-jan@prezent.nl>
 */
class CsvFileDumper extends BaseFileDumper
{
    /**
     * {@inheritDoc}
     */
    public function dump(MessageCatalogue $messages, $options = array())
    {
        if (!array_key_exists('path', $options)) {
            throw new \InvalidArgumentException('The file dumper needs a path option.');
        }

        // save a file for each domain
        $generatedFiles = array();
        $bundleName = isset($options['bundleName']) ? $options['bundleName'] : '';

        foreach ($messages->getDomains() as $domain) {
            $fileName = !empty($bundleName)
                ? sprintf('%s.%s.%s.%s', $bundleName, $domain, $messages->getLocale(), $this->getExtension())
                : sprintf('%s.%s.%s', $domain, $messages->getLocale(), $this->getExtension());

            $fullpath = sprintf('%s/%s', $options['path'], $fileName);

            // save file
            file_put_contents($fullpath, $this->format($messages, $domain));
            $generatedFiles[] = $fullpath;
        }

        return $generatedFiles;
    }
}
