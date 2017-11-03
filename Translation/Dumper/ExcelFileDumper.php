<?php

namespace Prezent\TranslationBundle\Translation\Dumper;

use Prezent\TranslationBundle\Excel\TranslationExporter;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * ExcelExporter
 *
 * @author  Robert-Jan Bijl <robert-jan@prezent.nl>
 */
class ExcelFileDumper
{
    /**
     * @param MessageCatalogue[] $catalogues
     * @param array $options
     * @return array
     */
    public function dump(array $catalogues, $options = [])
    {
        if (!array_key_exists('path', $options)) {
            throw new \InvalidArgumentException('The file dumper needs a path option.');
        }

        $path = $options['path'];
        list($exporter, $sheetDefinitions) = $this->createExporter($catalogues, $path);

        $j = 0;
        foreach ($catalogues as $catalogue) {
            /** @var MessageCatalogue $messages */
            $messages = $catalogue['catalogue'];
            foreach ($messages->getDomains() as $domain) {
                // start the line with the full name of the sheet
                $row = [$sheetDefinitions[$j]];
                $exporter->writeRow($row, $j);

                // create the header row
                $row = ['key', $messages->getLocale()];
                $exporter->writeRow($row, $j);

                // format the data and write to file
                $data = $this->format($messages, $domain);
                foreach ($data as $row) {
                    $exporter->writeRow($row, $j);
                }

                $j++;
            }
        }

        $fileName = sprintf('%s-translations.xlsx', date('Y-m-d'));
        list ($generatedPath, $generatedFileName) = $exporter->generateFile($fileName);

        return $generatedPath;
    }

    /**
     * Create the excel exporter, populate with a sheet per domain
     *
     * @param MessageCatalogue[] $catalogues
     * @param string $path
     * @return array
     */
    private function createExporter(array $catalogues, $path)
    {
        $sheetDefinitions = [];
        $i = 0;

        // create a file with a worksheet for each domain
        foreach ($catalogues as $catalogue) {
            $bundleName = $catalogue['bundle'];
            /** @var MessageCatalogue $messages */
            $messages = $catalogue['catalogue'];
            foreach ($messages->getDomains() as $domain) {
                $sheetDefinitions[$i] = !empty($bundleName)
                    ? sprintf('%s.%s.%s', $bundleName, $domain, $messages->getLocale())
                    : sprintf('%s.%s', $domain, $messages->getLocale())
                ;
                $i++;
            }
        }

        $exporter = new TranslationExporter($path, $sheetDefinitions);

        return [$exporter, $sheetDefinitions];
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