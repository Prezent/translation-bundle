<?php

namespace Prezent\TranslationBundle\Translation\Dumper;

use Prezent\ExcelExporter\Exporter as ExcelExporter;
use Symfony\Component\Translation\Dumper\DumperInterface;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * ExcelExporter
 *
 * @author  Robert-Jan Bijl <robert-jan@prezent.nl>
 */
class ExcelFileDumper implements DumperInterface
{
    /**
     * {@inheritdoc}
     */
    public function dump(MessageCatalogue $messages, $options = array())
    {
        if (!array_key_exists('path', $options)) {
            throw new \InvalidArgumentException('The file dumper needs a path option.');
        }

        $path = $options['path'];
        $bundleName = isset($options['bundleName']) ? $options['bundleName'] : '';
        $generatedFiles = array();

        // save a file for each domain
        foreach ($messages->getDomains() as $domain) {
            $fileName = !empty($bundleName)
                ? sprintf('%s.%s.%s.xlsx', $bundleName, $domain, $messages->getLocale())
                : sprintf('%s.%s.xlsx', $domain, $messages->getLocale());

            // create the exporter file
            $exporter = new ExcelExporter($path);

            // create the header row
            $row = array('key', $messages->getLocale());
            $exporter->writeRow($row);

            // format the data and write to file
            $data = $this->format($messages, $domain);
            foreach ($data as $row) {
                $exporter->writeRow($row);
            }

            list ($generatedPath, $generatedFileName) = $exporter->generateFile($fileName);
            $generatedFiles[] = $generatedPath;
        }

        return $generatedFiles;
    }

    /**
     * @param MessageCatalogue $messages
     * @param string           $domain
     * @return array
     */
    protected function format(MessageCatalogue $messages, $domain)
    {
        $data = array();
        foreach ($messages->all($domain) as $key => $value) {
            $data[] = array($key, $value);
        }

        return $data;
    }
}