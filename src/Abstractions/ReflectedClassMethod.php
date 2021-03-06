<?php namespace BambooHR\Guardrail\Abstractions;

/**
 * Guardrail.  Copyright (c) 2016-2017, Jonathan Gardiner and BambooHR.
 * Apache 2.0 License
 */

/**
 * Class ReflectedClassMethod
 *
 * @package BambooHR\Guardrail\Abstractions
 */
class ReflectedClassMethod implements MethodInterface {

	/**
	 * @var \ReflectionMethod
	 */
	private $refl;

	/**
	 * ReflectedClassMethod constructor.
	 *
	 * @param \ReflectionMethod $refl Instance of ReflectionMethod
	 */
	public function __construct(\ReflectionMethod $refl) {
		$this->refl = $refl;
	}

	/**
	 * isStatic
	 *
	 * @return bool
	 */
	public function isStatic() {
		return $this->refl->isStatic();
	}

	/**
	 * isDeprecated
	 *
	 * @return bool
	 */
	public function isDeprecated() {
		return $this->refl->isDeprecated();
	}

	/**
	 * isInternal
	 *
	 * @return bool
	 */
	public function isInternal() {
		return true;
	}

	/**
	 * getReturnType
	 *
	 * @return string
	 */
	public function getReturnType() {
		return "";
	}

	/**
	 * getDocBlockReturnType
	 *
	 * @return string
	 */
	public function getDocBlockReturnType() {
		return "";
	}

	/**
	 * isAbstract
	 *
	 * @return bool
	 */
	public function isAbstract() {
		return $this->refl->isAbstract();
	}

	/**
	 * getAccessLevel
	 *
	 * @return string
	 */
	public function getAccessLevel() {
		if ($this->refl->isPrivate()) {
			return "private";
		}
		if ($this->refl->isPublic()) {
			return "public";
		}
		if ($this->refl->isProtected()) {
			return "protected";
		}
	}

	/**
	 * getMinimumRequiredParameters
	 *
	 * @return int
	 */
	public function getMinimumRequiredParameters() {
		if (
			strcasecmp($this->refl->getName(), "running") &&
			strcasecmp($this->refl->getDeclaringClass()->name, "phar") == 0
		) {
			return 0;
		} else {
			return $this->refl->getNumberOfRequiredParameters();
		}
	}

	/**
	 * getParameters
	 *
	 * @return array
	 */
	public function getParameters() {
		$ret = [];
		$params = $this->refl->getParameters();
		/** @var \ReflectionParameter $param */
		foreach ($params as $param) {
			$type = $param->getClass() ? $param->getClass()->name : '';
			$ret[] = new FunctionLikeParameter( $type, $param->name, $param->isOptional(), $param->isPassedByReference());
		}
		return $ret;
	}

	/**
	 * getName
	 *
	 * @return string
	 */
	public function getName() {
		return $this->refl->getName();
	}

	/**
	 * getStartingLine
	 *
	 * @return int
	 */
	public function getStartingLine() {
		return 0;
	}

	/**
	 * isVariadic
	 *
	 * @return bool
	 */
	public function isVariadic() {
		if (method_exists($this->refl, "isVariadic")) {
			return $this->refl->isVariadic();
		} else {
			return true; // We assume internal functions are variadic so that we don't get bombarded with warnings.
		}
	}
}