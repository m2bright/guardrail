<?php namespace BambooHR\Guardrail\Checks;

/**
 * Guardrail.  Copyright (c) 2016-2017, Jonathan Gardiner and BambooHR.
 * Apache 2.0 License
 */

use BambooHR\Guardrail\Abstractions\MethodInterface;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Trait_;
use BambooHR\Guardrail\Output\OutputInterface;
use BambooHR\Guardrail\Scope;
use BambooHR\Guardrail\SymbolTable\SymbolTable;
use BambooHR\Guardrail\TypeInferrer;
use BambooHR\Guardrail\Util;

/**
 * Class MethodCall
 *
 * @package BambooHR\Guardrail\Checks
 */
class MethodCall extends BaseCheck {

	/** @var TypeInferrer */
	private $inferenceEngine;

	/**
	 * MethodCall constructor.
	 *
	 * @param SymbolTable     $symbolTable Instance of the SymbolTable
	 * @param OutputInterface $doc         Instance of OutputInterface
	 */
	public function __construct(SymbolTable $symbolTable, OutputInterface $doc) {
		parent::__construct($symbolTable, $doc);
		$this->inferenceEngine = new TypeInferrer($symbolTable);
	}

	/**
	 * getCheckNodeTypes
	 *
	 * @return array
	 */
	public function getCheckNodeTypes() {
		return [\PhpParser\Node\Expr\MethodCall::class];
	}

	/**
	 * run
	 *
	 * @param string         $fileName The name of the file we are parsing
	 * @param Node           $node     Instance of the Node
	 * @param ClassLike|null $inside   Instance of the ClassLike (the class we are parsing) [optional]
	 * @param Scope|null     $scope    Instance of the Scope (all variables in the current state) [optional]
	 *
	 * @return mixed
	 */
	public function run($fileName, Node $node, ClassLike $inside=null, Scope $scope=null) {

		if ($node instanceof Expr\MethodCall) {

			if ($inside instanceof Trait_) {
				// Traits should be converted into methods in the class, so that we can check them in context.
				return;
			}
			if ($node->name instanceof Expr) {
				$this->emitError($fileName, $node, ErrorConstants::TYPE_VARIABLE_FUNCTION_NAME, "Variable function name detected");
				return;
			}
			$methodName = strval($node->name);

			$varName = "{expr}";
			$className = "";
			if ($node->var instanceof Variable && $node->var->name == "this" && !$inside) {
				$this->emitError($fileName, $node, ErrorConstants::TYPE_SCOPE_ERROR, "Can't use \$this outside of a class");
				return;
			}
			if ($scope) {
				$className = $this->inferenceEngine->inferType($inside, $node->var, $scope);
			}
			if ($className != "" && $className[0] != "!") {
				if (!$this->symbolTable->isDefinedClass($className)) {
					$this->emitError($fileName, $node, ErrorConstants::TYPE_UNKNOWN_CLASS, "Unknown class $className in method call to $methodName()");
					return;
				}
				//echo $fileName." ".$node->getLine(). " : Looking up $className->$methodName\n";
				$method = Util::findAbstractedSignature($className, $methodName, $this->symbolTable);
				if ($method) {
					$this->checkMethod($fileName, $node, $className, $scope, $method);
				} else {
					// If there is a magic __call method, then we can't know if it will handle these calls.
					if (
						!Util::findAbstractedMethod($className, "__call", $this->symbolTable) &&
						!$this->symbolTable->isParentClassOrInterface("iteratoriterator", $className)
					) {
						$this->emitError($fileName, $node, ErrorConstants::TYPE_UNKNOWN_METHOD, "Call to unknown method of $className::$methodName");
					}
				}
			}
		}
	}

	/**
	 * checkMethod
	 *
	 * @param string          $fileName The name of the file
	 * @param string          $node     The node
	 * @param string          $inside   The inside method
	 * @param Scope           $scope    Instance of Scope
	 * @param MethodInterface $method   Instance of MethodInterface
	 *
	 * @return void
	 */
	protected function checkMethod($fileName, $node, $inside, Scope $scope, MethodInterface $method) {
		if ($method->isStatic()) {
			$this->emitError($fileName, $node, ErrorConstants::TYPE_INCORRECT_DYNAMIC_CALL, "Call to static method of $inside::" . $method->getName() . " non-statically");
			return;
		}
		$params = $method->getParameters();
		$minimumArgs = $method->getMinimumRequiredParameters();
		if (count($node->args) < $minimumArgs) {
			$this->emitError($fileName, $node, ErrorConstants::TYPE_SIGNATURE_COUNT, "Function call parameter count mismatch to method " . $method->getName() . " (passed " . count($node->args) . " requires $minimumArgs)");
		}
		if (count($node->args) > count($params) && !$method->isVariadic()) {
			$this->emitError($fileName, $node, ErrorConstants::TYPE_SIGNATURE_COUNT_EXCESS, "Too many parameters to non-variadic method " . $method->getName() . " (passed " . count($node->args) . " only takes " . count($params) . ")");
		}
		if ($method->isDeprecated()) {
			$errorType = $method->isInternal() ? ErrorConstants::TYPE_DEPRECATED_INTERNAL : ErrorConstants::TYPE_DEPRECATED_USER;
			$this->emitError($fileName, $node, $errorType, "Call to deprecated function " . $method->getName());
		}

		foreach ($node->args as $index => $arg) {

			if ($scope && $arg->value instanceof Variable && $index < count($params) ) {
				$variableName = $arg->value->name;
				$type = $scope->getVarType($variableName);
				if ($arg->unpack) {
					// Check if they called with ...$array.  If so, make sure $array is of type undefined or array
					if (strcasecmp($type, "array") != 0 && $type != Scope::UNDEFINED && $type != Scope::MIXED_TYPE) {
						$this->emitError($fileName, $node, ErrorConstants::TYPE_SIGNATURE_TYPE, "Splat (...) operator requires an array.  Passing $type from \$$variableName.");
					}
				} else if ($params[$index]->getType() != "") {
					// They called with a simple $foo, see if the $type for Foo matches
					$expectedType = $params[$index]->getType();
					if (!in_array($type, [Scope::SCALAR_TYPE, Scope::MIXED_TYPE, Scope::UNDEFINED]) && $type != "" && !$this->symbolTable->isParentClassOrInterface($expectedType, $type)) {
						$this->emitError($fileName, $node, ErrorConstants::TYPE_SIGNATURE_TYPE, "Variable passed to method " . $inside . "->" . $node->name . "() parameter \$$variableName must be a $expectedType, passing $type");
					}
				}
			}
		}
	}
}
