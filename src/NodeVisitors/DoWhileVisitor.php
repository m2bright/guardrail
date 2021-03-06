<?php namespace BambooHR\Guardrail\NodeVisitors;

/**
 * Guardrail.  Copyright (c) 2017, Jonathan Gardiner and BambooHR.
 * Apache 2.0 License
 */

use PhpParser\Node;
use BambooHR\Guardrail\DoWhileStatement;
use PhpParser\NodeVisitorAbstract;

/**
 * Class TraitImportingVisitor
 *
 * Replaces class Do_ with class DoWhileStatement.  The DoWhileStatement visits the "stmts" before the "cond" expression.
 * The latest commits to the official Do_ have the same behavior, but there are no releases with this change yet.
 */
class DoWhileVisitor extends NodeVisitorAbstract {

	/**
	 * leaveNode
	 *
	 * @param Node $node Instance of Node
	 *
	 * @return DoWhileStatement|null
	 */
	public function leaveNode(Node $node) {
		// The default do/while visits the condition before the statement list.
		// This causes undefined variable errors.  We correct it by replacing it with
		// a subclass that in the order we need.
		if ($node instanceOf Node\Stmt\Do_) {
			return DoWhileStatement::fromDo($node);
		}
		return null;
	}
}
