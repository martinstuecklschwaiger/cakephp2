<?php
/**
 * Generates code coverage reports in Simple plain text from data obtained from PHPUnit
 *
 * PHP5
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       Cake.TestSuite.Coverage
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('BaseCoverageReport', 'TestSuite/Coverage');

/**
 * Generates code coverage reports in Simple plain text from data obtained from PHPUnit
 *
 * @package       Cake.TestSuite.Coverage
 */
#[\AllowDynamicProperties]
class TextCoverageReport extends BaseCoverageReport {

/**
 * Generates report text to display.
 *
 * @return string compiled plain text report.
 */
	public function report() {
		$pathFilter = $this->getPathFilter();
		$coverageData = $this->filterCoverageDataByPath($pathFilter);
		if (empty($coverageData)) {
			return 'No files to generate coverage for';
		}
		$output = "\nCoverage Report:\n\n";
		foreach ($coverageData as $file => $coverageData) {
			$fileData = file($file);
			$output .= $this->generateDiff($file, $fileData, $coverageData);
		}
		return $output;
	}

/**
 * Generates a 'diff' report for a file.
 * Since diffs are too big for plain text reports a simple file => % covered is done.
 *
 * @param string $filename Name of the file having coverage generated
 * @param array $fileLines File data as an array. See file() for how to get one of these.
 * @param array $coverageData Array of coverage data to use to generate HTML diffs with
 * @return string
 */
	public function generateDiff($filename, $fileLines, $coverageData) {
		list($covered, $total) = $this->_calculateCoveredLines($fileLines, $coverageData);
		$percentCovered = round(100 * $covered / $total, 2);
		return "$filename : $percentCovered%\n";
	}

}
