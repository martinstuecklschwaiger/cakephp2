<?php
/**
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
 * Generates code coverage reports in HTML from data obtained from PHPUnit
 *
 * @package       Cake.TestSuite.Coverage
 */
#[\AllowDynamicProperties]
class HtmlCoverageReport extends BaseCoverageReport {

/**
 * Holds the total number of processed rows.
 *
 * @var int
 */
	protected $_total = 0;

/**
 * Holds the total number of covered rows.
 *
 * @var int
 */
	protected $_covered = 0;

/**
 * Generates report HTML to display.
 *
 * @return string Compiled HTML report.
 */
	public function report() {
		$pathFilter = $this->getPathFilter();
		$coverageData = $this->filterCoverageDataByPath($pathFilter);
		if (empty($coverageData)) {
			return '<h3>No files to generate coverage for</h3>';
		}
		$output = $this->coverageScript();
		$output .= <<<HTML
		<h3>Code coverage results
		<a href="#" onclick="coverage_toggle_all()" class="coverage-toggle">Toggle all files</a>
		</h3>
HTML;
		foreach ($coverageData as $file => $coverageData) {
			$fileData = file($file);
			$output .= $this->generateDiff($file, $fileData, $coverageData);
		}

		$percentCovered = 100;
		if ($this->_total > 0) {
			$percentCovered = round(100 * $this->_covered / $this->_total, 2);
		}
		$output .= '<div class="total">Overall coverage: <span class="coverage">' . $percentCovered . '%</span></div>';
		return $output;
	}

/**
 * Generates an HTML diff for $file based on $coverageData.
 *
 * Handles both PHPUnit3.5 and 3.6 formats.
 *
 * 3.5 uses -1 for uncovered, and -2 for dead.
 * 3.6 uses array() for uncovered and null for dead.
 *
 * @param string $filename Name of the file having coverage generated
 * @param array $fileLines File data as an array. See file() for how to get one of these.
 * @param array $coverageData Array of coverage data to use to generate HTML diffs with
 * @return string HTML diff.
 */
	public function generateDiff($filename, $fileLines, $coverageData) {
		$output = '';
		$diff = array();

		list($covered, $total) = $this->_calculateCoveredLines($fileLines, $coverageData);
		$this->_covered += $covered;
		$this->_total += $total;

		//shift line numbers forward one;
		array_unshift($fileLines, ' ');
		unset($fileLines[0]);

		foreach ($fileLines as $lineno => $line) {
			$class = 'ignored';
			$coveringTests = array();
			if (!empty($coverageData[$lineno]) && is_array($coverageData[$lineno])) {
				$coveringTests = array();
				foreach ($coverageData[$lineno] as $test) {
					$class = (is_array($test) && isset($test['id'])) ? $test['id'] : $test;
					$testReflection = new ReflectionClass(current(explode('::', (string) $class)));
					$this->_testNames[] = $this->_guessSubjectName($testReflection);
					$coveringTests[] = $class;
				}
				$class = 'covered';
			} elseif (isset($coverageData[$lineno]) && ($coverageData[$lineno] === -1 || $coverageData[$lineno] === array())) {
				$class = 'uncovered';
			} elseif (array_key_exists($lineno, $coverageData) && ($coverageData[$lineno] === -2 || $coverageData[$lineno] === null)) {
				$class .= ' dead';
			}
			$diff[] = $this->_paintLine($line, $lineno, $class, $coveringTests);
		}

		$percentCovered = 100;
		if ($total > 0) {
			$percentCovered = round(100 * $covered / $total, 2);
		}
		$output .= $this->coverageHeader($filename, $percentCovered);
		$output .= implode("", $diff);
		$output .= $this->coverageFooter();
		return $output;
	}

/**
 * Guess the class name the test was for based on the test case filename.
 *
 * @param ReflectionClass $testReflection The class to reflect
 * @return string Possible test subject name.
 */
	protected function _guessSubjectName($testReflection) {
		$basename = basename($testReflection->getFilename());
		if (strpos($basename, '.test') !== false) {
			list($subject, ) = explode('.', $basename, 2);
			return $subject;
		}
		$subject = str_replace('Test.php', '', $basename);
		return $subject;
	}

/**
 * Renders the HTML for a single line in the HTML diff.
 *
 * @param string $line The line content.
 * @param int $linenumber The line number
 * @param string $class The classname to use.
 * @param array $coveringTests The tests covering the line.
 * @return string
 */
	protected function _paintLine($line, $linenumber, $class, $coveringTests) {
		$coveredBy = '';
		if (!empty($coveringTests)) {
			$coveredBy = "Covered by:\n";
			foreach ($coveringTests as $test) {
				$coveredBy .= $test . "\n";
			}
		}

		return sprintf(
			'<div class="code-line %s" title="%s"><span class="line-num">%s</span><span class="content">%s</span></div>',
			$class,
			$coveredBy,
			$linenumber,
			htmlspecialchars($line)
		);
	}

/**
 * generate some javascript for the coverage report.
 *
 * @return string
 */
	public function coverageScript() {
		return <<<HTML
		<script type="text/javascript">
		function coverage_show_hide(selector) {
			var element = document.getElementById(selector);
			element.style.display = (element.style.display === 'none') ? '' : 'none';
		}
		function coverage_toggle_all() {
			var divs = document.querySelectorAll('div.coverage-container');
			var i = divs.length;
			while (i--) {
				if (divs[i] && divs[i].className.indexOf('primary') == -1) {
					divs[i].style.display = (divs[i].style.display === 'none') ? '' : 'none';
				}
			}
		}
		</script>
HTML;
	}

/**
 * Generate an HTML snippet for coverage headers
 *
 * @param string $filename The file name being covered
 * @param string $percent The percentage covered
 * @return string
 */
	public function coverageHeader($filename, $percent) {
		$hash = md5($filename);
		$filename = basename($filename);
		list($file) = explode('.', $filename);
		$display = in_array($file, $this->_testNames) ? 'block' : 'none';
		$primary = $display === 'block' ? 'primary' : '';
		return <<<HTML
	<div class="coverage-container $primary" style="display:$display;">
	<h4>
		<a href="#coverage-$filename-$hash" onclick="coverage_show_hide('coverage-$filename-$hash');">
			$filename Code coverage: $percent%
		</a>
	</h4>
	<div class="code-coverage-results" id="coverage-$filename-$hash" style="display:none;">
	<pre>
HTML;
	}

/**
 * Generate an HTML snippet for coverage footers
 *
 * @return void
 */
	public function coverageFooter() {
		return "</pre></div></div>";
	}

}
