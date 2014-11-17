<?php

namespace Prezent\TranslationBundle\Translation\Dumper;

use Symfony\Component\Translation\Dumper\YamlFileDumper as BaseFileDumper;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Yaml\Yaml;

/**
 * YamlFileDumper generates nested yaml files from a message catalogue.
 *
 * The nesting parser is courtesy of ZF2
 * https://raw.github.com/zendframework/Component_ZendConfig/master/Reader/Ini.php
 * Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * http://framework.zend.com/license/new-bsd New BSD License
 *
 * @author Sander Marechal <sander@prezent.nl>
 */
class YamlFileDumper extends BaseFileDumper
{
    /**
     * @var int
     */
    protected $inline = 2;

    /**
     * @var int
     */
    protected $indent = 4;

    /**
     * {@inheritDoc}
     */
    protected function format(MessageCatalogue $messages, $domain)
    {
        $nested = $this->process($messages->all($domain));
        return Yaml::dump($nested, $this->inline, $this->indent);
    }

    /**
     * Process data from the parsed ini file.
     *
     * @param  array $translations
     * @return array
     */
    private function process(array $translations)
    {
        $data = array();

        foreach ($translations as $section => $value) {
            if (is_array($value)) {
                if (strpos($section, '.') !== false) {
                    $sections = explode('.', $section);
                    $data = array_merge_recursive($data, $this->buildNestedSection($sections, $value));
                } else {
                    $data[$section] = $this->processSection($value);
                }
            } else {
                $this->processKey($section, $value, $data);
            }
        }

        return $data;
    }

    /**
     * Process a nested section
     *
     * @param array $sections
     * @param mixed $value
     * @return array
     */
    private function buildNestedSection($sections, $value)
    {
        if (count($sections) == 0) {
            return $this->processSection($value);
        }

        $nestedSection = array();

        $first = array_shift($sections);
        $nestedSection[$first] = $this->buildNestedSection($sections, $value);

        return $nestedSection;
    }

    /**
     * Process a section.
     *
     * @param  array $section
     * @return array
     */
    protected function processSection(array $section)
    {
        $data = array();

        foreach ($section as $key => $value) {
            $this->processKey($key, $value, $data);
        }

        return $data;
    }

    /**
     * Process a key.
     *
     * @param  string $key
     * @param  string $value
     * @param  array  $data
     * @return array
     * @throws \RuntimeException
     */
    protected function processKey($key, $value, array &$data)
    {
        if (strpos($key, '.') === false) {
            $data[$key] = $value;
            return;
        }

        $pieces = explode('.', $key, 2);

        if (!strlen($pieces[0]) || !strlen($pieces[1])) {
            throw new \RuntimeException(sprintf('Invalid key "%s"', $key));
        } elseif (!isset($data[$pieces[0]])) {
            if ($pieces[0] === '0' && !empty($data)) {
                $data = array($pieces[0] => $data);
            } else {
                $data[$pieces[0]] = array();
            }
        } elseif (!is_array($data[$pieces[0]])) {
            $data[$key] = $value;
            return;
        }

        $this->processKey($pieces[1], $value, $data[$pieces[0]]);
    }

    /**
     * Setter for indent
     *
     * @param int $indent
     * @return self
     */
    public function setIndent($indent)
    {
        $this->indent = $indent;
        return $this;
    }

    /**
     * Setter for inline
     *
     * @param int $inline
     * @return self
     */
    public function setInline($inline)
    {
        $this->inline = $inline;
        return $this;
    }
}
