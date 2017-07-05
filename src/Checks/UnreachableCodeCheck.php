<?php namespace BambooHR\Guardrail\Checks;

use BambooHR\Guardrail\Scope;
use BambooHR\Guardrail\Util;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\If_;

/**
 * Class UnreachableCodeCheck
 *
 * @package BambooHR\Guardrail\Checks
 */
class UnreachableCodeCheck extends BaseCheck {

	/**
	 * getCheckNodeTypes
	 *
	 * @return string[]
	 */
	function getCheckNodeTypes() {
		return [ Function_::class ];
	}

	/**
	 * run
	 *
	 * @param string         $fileName The name of the file we are parsing
	 * @param Node           $node     Instance of the Node
	 * @param ClassLike|null $inside   Instance of the ClassLike (the class we are parsing) [optional]
	 * @param Scope|null     $scope    Instance of the Scope (all variables in the current state) [optional]
	 *
	 * @return void
	 */
	public function run($fileName, Node $node, ClassLike $inside = null, Scope $scope = null) {
		if ($node instanceof Function_) {
			$statements = $node->getStmts();
			$statement = $this->checkForUnreachableNode($statements);
			if (null !== $statement) {
				$this->emitError($fileName, $node, ErrorConstants::TYPE_UNREACHABLE_CODE, "Unreachable code was found on line " . $node->getLine());
				return;
			}
		}
	}

	/**
	 * checkForUnreachableNode
	 *
	 * @param array $statements An array of statements from the node
	 *
	 * @return mixed|null
	 */
	public function checkForUnreachableNode(array $statements) {
		$previous = array_shift($statements);
		foreach ($statements as $statement) {
			if (Util::allBranchesExit([$previous])) {
				return $statement;
			}
			$previous = $statement;
		}
		return null;
	}
}