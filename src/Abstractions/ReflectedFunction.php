<?php

/**
 * Guardrail.  Copyright (c) 2016-2017, Jonathan Gardiner and BambooHR.
 * Apache 2.0 License
 */

namespace BambooHR\Guardrail\Abstractions;


use BambooHR\Guardrail\Abstractions\FunctionLikeInterface;
use BambooHR\Guardrail\Abstractions\FunctionLikeParameter;

class ReflectedFunction implements FunctionLikeInterface {
	private $refl;

	function __construct(\ReflectionFunction $refl) {
		$this->refl = $refl;
	}

	function isStatic() {
		return $this->refl->isStatic();
	}

	function isDeprecated() {
		return $this->refl->isDeprecated();
	}

	function isInternal() {
		return true;
	}

	function getReturnType() {
		return "";
	}

	function isAbstract() {
		return $this->refl->isAbstract();
	}

	function getDocBlockReturnType() {
		return "";
	}

	function getAccessLevel() {
		if($this->refl->isPrivate()) return "private";
		if($this->refl->isPublic()) return "public";
		if($this->refl->isProtected()) return "protected";
	}

	function getMinimumRequiredParameters() {
		$min = self::getOverriddenMinimumParams($this->refl->name);
		return $min>=0 ? $min : $this->refl->getNumberOfRequiredParameters();
	}

	private static function getOverriddenMinimumParams($name) {
		static $overrides = [
			"define"=>2,
			"implode"=>1,
			"strtok"=>1,
			"sprintf"=>1,
			"array_merge"=>1,
			"stream_set_timeout"=>2
		];
		$name = strtolower($name);

		return isset($overrides[$name]) ? $overrides[$name] : -1;
	}


	function getParameters() {
		$ret = [];
		$params = $this->refl->getParameters();
		/** @var \ReflectionParameter $param */
		foreach($params as $index=>$param) {
			$type = $param->getClass() ? $param->getClass()->name : '';
			$isPassedByReference = $param->isPassedByReference();
			if($this->getName()=="preg_match" && $index==2) {
				$isPassedByReference = true;
			}
			$ret[] = new FunctionLikeParameter( $type , $param->name, $param->isOptional(), $isPassedByReference);
		}
		return $ret;
	}

	function getName() {
		return $this->refl->getName();
	}

	function getStartingLine() {
		return 0;
	}

	function isVariadic() {
		if(method_exists($this->refl,"isVariadic")) {
			return $this->refl->isVariadic();
		} else {
			return true; // We assume internal functions are variadic so that we don't get bombarded with warnings.
		}
	}
}