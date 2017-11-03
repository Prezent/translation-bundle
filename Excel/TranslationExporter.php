<?php

namespace Prezent\TranslationBundle\Excel;

use Prezent\ExcelExporter\Exporter;

/**
 * Prezent\TranslationBundle\Excel\TranslationExporter
 *
 * @author Robert-Jan Bijl <robert-jan@prezent.nl>
 */
class TranslationExporter extends Exporter
{
    /**
     * @var array
     */
    public $sheetDefinitions = [];

    /**
     * TranslationExporter constructor.
     * @param string $tempPath
     * @param array $sheetDefinitions
     */
    public function __construct($tempPath, array $sheetDefinitions)
    {
        $this->sheetDefinitions = $sheetDefinitions;

        parent::__construct($tempPath);
    }

    /**
     * {@inheritdoc}
     */
    protected function createWorksheets()
    {
        foreach ($this->sheetDefinitions as $index => $sheetName) {
            $this->getFile()->createSheet($index)->setTitle(substr($sheetName, 0, 31));
        }

        return $this;
    }
}