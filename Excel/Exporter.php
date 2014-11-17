<?php

namespace Prezent\TranslationBundle\Excel;

/**
 * Exporter
 *
 * @author  Robert-Jan Bijl <robert-jan@prezent.nl>
 */
class Exporter
{
    /**
     * @var \PHPExcel
     */
    private $file;

    /**
     * @var string
     */
    private $currentColumn;

    /**
     * @var int
     */
    private $currentRow;

    /**
     * @var array
     */
    private $columns;

    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $fileName;

    /**
     * @var string
     */
    private $filePath;

    /**
     * @param string $path
     */
    public function __construct($path)
    {
        $this->path = $path;
        $this->init();
    }

    /**
     * Initialize the exporter
     *
     * @return bool
     */
    protected function init()
    {
        $this->columns = range('A', 'Z');

        $this->currentRow = 1;
        $this->currentColumn = reset($this->columns);
        $this->file = $this->createFile();

        return true;
    }

    /**
     * Write data to a row
     *
     * @param array $data
     * @param bool  $finalize
     * @param null  $sheetIndex
     * @throws \Exception
     */
    public function writeRow(array $data, $finalize = true, $sheetIndex = null)
    {
        // get the sheet to write in
        if (null === $sheetIndex) {
            $sheet = $this->file->getActiveSheet();
        } else {
            $sheet = $this->file->getSheet($sheetIndex);
            $this->file->setActiveSheetIndex($sheetIndex);
        }

        foreach ($data as $value) {
            $coordinate = sprintf('%s%d', $this->currentColumn, $this->currentRow);

            $sheet->setCellValue($coordinate, $value);
            $this->nextColumn();
        }

        if ($finalize) {
            $this->nextRow();
        }
    }

    /**
     * Generate the file, return its location
     *
     * @param string $filename
     * @return string
     */
    public function generateFile($filename)
    {
        return $this->writeFile($filename);
    }

    /**
     * Set the pointer to the next column
     *
     * @return bool
     */
    private function nextColumn()
    {
        $this->currentColumn = next($this->columns);

        return true;
    }

    /**
     * Set the pointer to the next row, by default reset to first column
     *
     * @param bool $reset
     * @return bool
     */
    private function nextRow($reset = true)
    {
        $this->currentRow += 1;
        if ($reset) {
            $this->currentColumn = reset($this->columns);
        }

        return true;
    }

    /**
     * Create the PHPExcel instance to work in
     *
     * @return \PHPExcel
     */
    private function createFile()
    {
        $cacheMethod = \PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
        $cacheSettings = array('memoryCacheSize' => '32MB');
        \PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);

        $file = new \PHPExcel();
        $this->createWorksheets($file);

        return $file;
    }

    /**
     * Create worksheets
     *
     * @param \PHPExcel $file
     * @return \PHPExcel
     */
    protected function createWorksheets(\PHPExcel $file)
    {
        // create one default sheet
        $file->createSheet();

        return $file;
    }

    /**
     * Create excel file and store in tmp dir
     *
     * @param string $filename
     * @param string $format
     * @param bool   $disconnect
     * @return string
     */
    private function writeFile($filename, $format = 'Excel2007', $disconnect = true)
    {
        $path = sprintf('%s/%s', $this->path, $filename);
        $objWriter = \PHPExcel_IOFactory::createWriter($this->file, $format);
        $objWriter->save($path);

        if ($disconnect) {
            $this->file->disconnectWorksheets();
            unset($this->file);
        }

        $this->fileName = $filename;
        $this->filePath = $path;

        return $path;
    }
}
