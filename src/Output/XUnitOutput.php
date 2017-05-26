<?php

/**
 * Guardrail.  Copyright (c) 2016-2017, Jonathan Gardiner and BambooHR.
 * Apache 2.0 License
 */

namespace BambooHR\Guardrail\Output;

use BambooHR\Guardrail\Output\OutputInterface;
use N98\JUnitXml;
use PhpParser\Node;
use Webmozart\Glob\Glob;


class XUnitOutput implements OutputInterface {

	/** @var \BambooHR\Guardrail\Config  */
	private $config;

	/** @var JUnitXml\TestSuiteElement[] */
	protected $suites;

	/** @var JUnitXml\Document  */
	protected $doc;

	private $files;

	private $emitErrors;

	private $emitList = [];

	private $counts = [];

	private $silenced = [];

	function __construct(\BambooHR\Guardrail\Config $config) {
		$this->doc = new JUnitXml\Document();
		$this->doc->formatOutput = true;
		$this->config = $config;
		$this->emitErrors = $config->getOutputLevel() == 1;
		$this->emitList = $config->getEmitList();
	}

	function getClass($className) {
		if (!isset($this->suites[$className])) {
			$suite = $this->doc->addTestSuite();
			$suite->setName($className);
			$this->suites[$className] = $suite;
		}
		return $this->suites[$className];

	}

	function incTests() {
		//$this->suite->addTestCase();
	}

	function getTypeCounts() {
		$count = [];
		$failures = $this->doc->getElementsByTagName("failure");
		foreach ($failures as $failure) {
			$type = $failure->getAttribute('type');
			$count[$type] = isset( $count[$type] ) ? $count[$type] + 1 : 1;
		}
		return $count;
	}

	static function emitPatternMatches($name, $pattern) {
		if (substr($pattern, -2) == '.*') {
			$start = substr($pattern, 0, -2);
			return (strpos($name, $start) === 0);
		} else {
			return $name == $pattern;
		}
	}


	function shouldEmit($fileName, $name) {
		if (isset($this->silenced[$name]) && $this->silenced[$name] > 0) {
			return false;
		}
		foreach ($this->emitList as $entry) {
			 if (
				is_array($entry) &&
				isset($entry['glob']) &&
				isset($entry['emit']) &&
				self::emitPatternMatches($name, $entry['emit']) &&
				Glob::match( "/" . $fileName, "/" . $entry['glob'])
			) {
				 if (isset($entry['ignore'])) {
					return !Glob::match("/" . $fileName, "/" . $entry['ignore']);
				} else {
					 return true;
				}
			} else if (is_string($entry) && self::emitPatternMatches($name, $entry)) {
				 return true;
			}
		}
		return false;
	}

	function silenceType($name) {
		if (!isset($this->silenced[$name])) {
			$this->silenced[$name] = 1;
		} else {
			$this->silenced[$name]++;
		}
	}

	function resumeType($name) {
		$this->silenced[$name]--;
	}

	function emitError($className, $fileName, $lineNumber, $name, $message="") {

		if (!$this->shouldEmit($fileName, $name)) {
			return;
		}
		$suite = $this->getClass($className);
		if (!isset($this->files[$className][$fileName])) {
			$case = $suite->addTestCase();
			$case->setName($fileName);
			$case->setClassname( $className );
			if (!isset($this->files[$className])) {
				$this->files[$className] = [];
			}
			$this->files[$className][$fileName] = $case;
		} else {
			$case = $this->files[$className][$fileName];
		}

		$message .= " on line " . $lineNumber;
		$case->addFailure($message, $name);
		if ($this->emitErrors) {
			echo "E";
		}
		if (!isset($this->counts[$name])) {
			$this->counts[$name] = 1;
		} else {
			++$this->counts[$name];
		}
		$this->outputExtraVerbose("ERROR: $fileName $lineNumber: $name: $message\n");
	}

	function output($verbose, $extraVerbose) {
		if ($this->config->getOutputLevel() == 1) {
			echo $verbose;
flush();
		} else if ($this->config->getOutputLevel() == 2) {
			echo $extraVerbose . "\n";
flush();
		}
	}

	function getCounts() {
		return $this->counts;
	}

	function outputVerbose($string) {
		if ($this->config->getOutputLevel() >= 1) {
			echo $string;
flush();
		}
	}

	function outputExtraVerbose($string) {
		if ($this->config->getOutputLevel() >= 2) {
			echo $string;
flush();
		}
	}

	function getErrorCount() {
		$failures = $this->doc->getElementsByTagName("failure");
		return $failures->length;
	}

	function renderResults() {
		if ($this->config->getOutputFile()) {
			$this->doc->save($this->config->getOutputFile());
		} else {
			echo $this->doc->saveXml();
		}
		//print_r($this->getTypeCounts());
	}

	function getErrorsByFile() {
		$fileCount = [];
		$failures = $this->doc->getElementsByTagName("failure");
		for ($i = 0; $i < $failures->length; ++$i) {
			$item = $failures->item($i);
			$name = $item->parentNode->attributes->getNamedItem("name")->textContent;
			$fileCount[$name]++;
		}
		return $fileCount;
	}
}