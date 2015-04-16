<?php
/**
*
* Board Announcements extension for the phpBB Forum Software package.
*
* @copyright (c) 2015 phpBB Limited <https://www.phpbb.com>
* @license GNU General Public License, version 2 (GPL-2.0)
*
*/

namespace anavaro\abannouncements\tests\controller;

require_once dirname(__FILE__) . '/../../../../../includes/functions.php';

class controller_test extends \phpbb_database_test_case
{
	/**
	* Define the extensions to be tested
	*
	* @return array vendor/name of extension(s) to test
	*/
	static protected function setup_extensions()
	{
		return array('anavaro/abannouncements');
	}

	protected $config;
	protected $db;
	protected $helper;
	protected $request;
	protected $user;

	/**
	* Get data set fixtures
	*/
	public function getDataSet()
	{
		return $this->createXMLDataSet(dirname(__FILE__) . '/fixtures/users.xml');
	}

	/**
	* Setup test environment
	*/
	public function setUp()
	{
		parent::setUp();

		$this->db = $this->new_dbal();
	}

	/**
	* Create our controller
	*/
	protected function get_controller($user_id, $is_registered, $mode, $ajax)
	{
		$user = $this->getMock('\phpbb\user', array(), array('\phpbb\datetime'));
		$user->data['announce_akn'] = '';
		$user->data['user_id'] = $user_id;
		$user->data['is_registered'] = $is_registered;

		$request = $this->getMock('\phpbb\request\request');
		$request->expects($this->any())
			->method('is_ajax')
			->will($this->returnValue($ajax)
		);
		$request->expects($this->any())
			->method('variable')
			->with($this->anything())
			->will($this->returnValueMap(array(
				array('hash', '', false, \phpbb\request\request_interface::REQUEST, generate_link_hash($mode))
			))
		);

		$this->controller_helper = $this->getMockBuilder('\phpbb\controller\helper')
			->disableOriginalConstructor()
			->getMock();
		
		return new \anavaro\abannouncements\controller\ajaxify(
			$this->controller_helper,
			$this->db,
			$request,
			$user,
			'phpbb_board_announce'
		);
	}

	/**
	 * Test data for the test_controller test
	 *
	 * @return array Test data
	 */
	public function controller_data()
	{
		return array(
			array(
				1,
				1, // Guest
				false, // Guest is not a registered user
				'close_boardannouncement',
				true,
				200,
				'{"success":true}', // True because a cookie was set
				1, // Status should remain 1 for guests
			),
			array(
				1,
				2, // Member
				true, // Member is a registered user
				'close_boardannouncement',
				true,
				200,
				'{"success":true}', // True because a cookie and status were set
				0, // Status should be changed to 0 for the member
			),
			array(
				1,
				0, // Invalid member
				true, // Set is_registered to true to test close_announcement() with invalid user_id
				'close_boardannouncement',
				true,
				200,
				'{"success":false}', // False because user did not exist
				0, // Status should return 0 due to user not existing
			),
		);
	}

	/**
	 * Test the controller response under normal conditions
	 *
	 * @dataProvider controller_data
	 */
	public function test_controller($announce_id, $user_id, $is_registered, $mode, $ajax, $status_code, $content, $expected)
	{
		$controller = $this->get_controller($user_id, $is_registered, $mode, $ajax);

		$response = $controller->close($announce_id);
		$this->assertInstanceOf('\Symfony\Component\HttpFoundation\JsonResponse', $response);
		$this->assertEquals($status_code, $response->getStatusCode());
		$this->assertEquals($content, $response->getContent());
		//$this->assertEquals($expected, $this->check_board_announcement_status($user_id));
	}


}
