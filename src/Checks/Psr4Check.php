<?php

namespace BambooHR\Guardrail\Checks;

use PhpParser\Node;
use BambooHR\Guardrail\Scope;

class Psr4Check extends BaseCheck {
	/**
	 * @return string[]
	 */
	function getCheckNodeTypes() {
		return [Node\Stmt\Class_::class, Node\Stmt\Interface_::class, Node\Stmt\Trait_::class];
	}

	/**
	 * @param Node\Name|null $name The node to grab the class/trait/interface name from.
	 * @return string
	 */
	private function getPsr4Path(Node\Name $name = null) {
		return $name ? $name->toString("/") . ".php" : "";
	}

	/**
	 * @param string                   $fileName Current filename
	 * @param Node                     $node     Current node
	 * @param Node\Stmt\ClassLike|null $inside   Current class
	 * @param Scope|null               $scope    Any relevant scope
	 * @return void
	 */
	function run($fileName, Node $node, Node\Stmt\ClassLike $inside = null, Scope $scope = null) {
		$name = "";
		if ($node instanceof Node\Stmt\Class_) {
			$name = $this->getPsr4Path($node->getAttribute("namespacedName"));
		} else {
			if ($node instanceof Node\Stmt\Interface_) {
				$name = $this->getPsr4Path($node->getAttribute("namespacedName"));
			} else if ($node instanceof Node\Stmt\Trait_) {
				$name = $this->getPsr4Path($node->getAttribute("namespacedName"));
			}
		}

		// All classes with a name, must follow PSR-4 naming.
		// (Anonymous classes obviously don't need to be in their own file.)
		if ($name != "" && (strpos($name, "/") === false || substr($fileName, -strlen($name)) != $name)) {
			$this->emitError($fileName, $node, ErrorConstants::TYPE_PSR4, "Class " . $node->name . " inside $fileName is not namespaced as a PSR-4 class");
		}
	}
}