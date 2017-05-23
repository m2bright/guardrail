<?php namespace BambooHR\Guardrail;

/**
 * Class UtilTest
 */
class UtilTest  extends \PHPUnit_Framework_TestCase {

	/**
	 * testConfigDirectoriesAreValid
	 *
	 * @param string $baseDirectory The base directory
	 * @param array  $paths         The paths to check
	 * @param bool   $expected      Expected results
	 *
	 * @return void
	 * @dataProvider configData
	 * @rapid-unit Util:ConfigDirectoryValidation:Valid data will return true
	 */
	public function testConfigDirectoriesAreValid($baseDirectory, $paths, $expected) {
		$this->assertEquals($expected, Util::configDirectoriesAreValid($baseDirectory, $paths));
	}

	/**
	 * configData
	 *
	 * @return array
	 */
	public function configData() {
		return [
			[__DIR__ . '/../../', ['src/Checks', 'src/Output', '/tmp'], true],
			[__DIR__ . '/../../', ['tests/units', '/tmp'], true],
		];
	}

	/**
	 * testConfigDirectoriesAreNotValid
	 *
	 * @param string $baseDirectory The base directory
	 * @param array  $paths         The paths to check
	 * @param bool   $expected      Expected results
	 *
	 * @return void
	 * @dataProvider invalidData
	 * @rapid-unit Util:ConfigDirectoryValidation:Invalid data will return false
	 */
	public function testConfigDirectoriesAreNotValid($baseDirectory, $paths, $expected) {
		$this->assertEquals($expected, Util::configDirectoriesAreValid($baseDirectory, $paths));
	}

	/**
	 * invalidData
	 *
	 * @return array
	 */
	public function invalidData() {
		return [
			['/', ['app', 'vendor', '/usr/share/noDirShouldExistHere'], false],
			[__DIR__ . '/../../', ['src/PotluckPie', 'src/Output', '/tmp'], false],
			[0, ['tests/units', '/tmp'], false],
		];
	}

	/**
	 * testConfigDirectoriesAreValidThrowsException
	 *
	 * @param string $baseDirectory The base directory
	 * @param array  $paths         The path list (if it exists)
	 *
	 * @return void
	 * @dataProvider exceptionData
	 * @expectedException \InvalidArgumentException
	 * @rapid-unit Util:ConfigDirectoryValidation:Validation will throw exception for missing data.
	 */
	public function testConfigDirectoriesAreValidThrowsException($baseDirectory, $paths) {
		$this->assertFalse(Util::configDirectoriesAreValid($baseDirectory, $paths));
	}

	/**
	 * exceptionData
	 *
	 * @return array
	 */
	public function exceptionData() {
		return [
			[null, ''],
			[new \stdClass(), null],
			[0, []],
		];
	}

	/**
	 * testFullDirectoryPath
	 *
	 * @param string $baseDirectory The base path for the config
	 * @param string $path          The path in the config to check
	 * @param string $expected      The expected results
	 *
	 * @return void
	 * @dataProvider pathData
	 * @rapid-unit Util:FullDirectoryPath:Full directory path will return correctly with or without the ending slash
	 */
	public function testFullDirectoryPath($baseDirectory, $path, $expected) {
		$this->assertEquals($expected, Util::fullDirectoryPath($baseDirectory, $path));
	}

	/**
	 * pathData
	 *
	 * @return array
	 */
	public function pathData() {
		return [
			['/test/', 'src/tree', '/test/src/tree'],
			['/test', 'src/tree', '/test/src/tree'],
			['/test/', '/somewhere/else', '/somewhere/else'],
		];
	}
}