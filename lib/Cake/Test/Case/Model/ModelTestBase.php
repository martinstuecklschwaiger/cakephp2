<?php
/**
 * ModelTest file
 *
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Model
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Model', 'Model');
App::uses('AppModel', 'Model');

require_once dirname(__FILE__) . DS . 'models.php';

/**
 * ModelBaseTest
 *
 * @package       Cake.Test.Case.Model
 */
#[\AllowDynamicProperties]
abstract class BaseModelTest extends CakeTestCase {

/**
 * autoFixtures property
 *
 * @var bool
 */
	public $autoFixtures = false;

/**
 * Whether backup global state for each test method or not
 *
 * @var bool
 */
	public $backupGlobals = false;

/**
 * fixtures property
 *
 * @var array
 */
	public $fixtures = array(
		'core.category', 'core.category_thread', 'core.user', 'core.my_category', 'core.my_product',
		'core.my_user', 'core.my_categories_my_users', 'core.my_categories_my_products',
		'core.article', 'core.featured', 'core.article_featureds_tags', 'core.article_featured',
		'core.numeric_article', 'core.tag', 'core.articles_tag', 'core.comment',
		'core.attachment', 'core.apple', 'core.sample', 'core.another_article', 'core.item',
		'core.advertisement', 'core.home', 'core.post', 'core.author', 'core.bid', 'core.portfolio',
		'core.product', 'core.project', 'core.thread', 'core.message', 'core.items_portfolio',
		'core.syfile', 'core.image', 'core.device_type', 'core.device_type_category',
		'core.feature_set', 'core.exterior_type_category', 'core.document', 'core.device',
		'core.document_directory', 'core.primary_model', 'core.secondary_model', 'core.something',
		'core.something_else', 'core.join_thing', 'core.join_a', 'core.join_b', 'core.join_c',
		'core.join_a_b', 'core.join_a_c', 'core.uuid', 'core.uuid_native', 'core.data_test', 'core.posts_tag',
		'core.the_paper_monkies', 'core.person', 'core.underscore_field', 'core.node',
		'core.dependency', 'core.story', 'core.stories_tag', 'core.cd', 'core.book', 'core.basket',
		'core.overall_favorite', 'core.account', 'core.content', 'core.content_account',
		'core.film_file', 'core.test_plugin_article', 'core.test_plugin_comment', 'core.uuiditem',
		'core.counter_cache_user', 'core.counter_cache_post',
		'core.counter_cache_user_nonstandard_primary_key',
		'core.counter_cache_post_nonstandard_primary_key', 'core.uuidportfolio',
		'core.uuiditems_uuidportfolio', 'core.uuiditems_uuidportfolio_numericid', 'core.fruit',
		'core.fruits_uuid_tag', 'core.uuid_tag', 'core.product_update_all', 'core.group_update_all',
		'core.player', 'core.guild', 'core.guilds_player', 'core.armor', 'core.armors_player',
		'core.bidding', 'core.bidding_message', 'core.site', 'core.domain', 'core.domains_site',
		'core.uuidnativeitem', 'core.uuidnativeportfolio', 'core.uuidnativeitems_uuidnativeportfolio',
		'core.uuidnativeitems_uuidnativeportfolio_numericid',
		'core.translated_article', 'core.translate_article',
	);

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->debug = Configure::read('debug');
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		Configure::write('debug', $this->debug);
		ClassRegistry::flush();
	}
}
