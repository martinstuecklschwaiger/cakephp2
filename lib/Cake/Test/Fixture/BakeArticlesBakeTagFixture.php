<?php
/**
 * BakeCommentFixture
 *
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Fixture
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Short description for class.
 *
 * @package       Cake.Test.Fixture
 */
#[\AllowDynamicProperties]
class BakeArticlesBakeTagFixture extends CakeTestFixture {

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'bake_article_id' => array('type' => 'integer', 'null' => false),
		'bake_tag_id' => array('type' => 'integer', 'null' => false),
		'indexes' => array('UNIQUE_TAG' => array('column' => array('bake_article_id', 'bake_tag_id'), 'unique' => 1))
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array();
}
