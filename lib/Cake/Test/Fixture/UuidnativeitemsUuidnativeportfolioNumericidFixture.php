<?php
/**
 * Short description for file.
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
 * @package       Cake.Test.Fixture
 * @since         CakePHP(tm) v 1.2.0.4667
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * UuidnativeitemsUuidnativeportfolioNumericidFixture
 *
 * @package       Cake.Test.Fixture
 */
#[\AllowDynamicProperties]
class UuidnativeitemsUuidnativeportfolioNumericidFixture extends CakeTestFixture {

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'length' => 10, 'key' => 'primary'),
		'uuidnativeitem_id' => array('type' => 'uuid', 'null' => false),
		'uuidnativeportfolio_id' => array('type' => 'uuid', 'null' => false)
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('uuidnativeitem_id' => '481fc6d0-b920-43e0-a40d-6d1740cf8569', 'uuidnativeportfolio_id' => '4806e091-6940-4d2b-b227-303740cf8569'),
		array('uuidnativeitem_id' => '48298a29-81c0-4c26-a7fb-413140cf8569', 'uuidnativeportfolio_id' => '480af662-eb8c-47d3-886b-230540cf8569'),
		array('uuidnativeitem_id' => '482b7756-8da0-419a-b21f-27da40cf8569', 'uuidnativeportfolio_id' => '4806e091-6940-4d2b-b227-303740cf8569'),
		array('uuidnativeitem_id' => '482cfd4b-0e7c-4ea3-9582-4cec40cf8569', 'uuidnativeportfolio_id' => '4806e091-6940-4d2b-b227-303740cf8569')
	);
}
