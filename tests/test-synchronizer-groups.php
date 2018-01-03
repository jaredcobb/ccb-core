<?php
/**
 * Class Test_Synchronizer_Groups
 *
 * @package CCB_Core
 */

/**
 * Test the synchronization of groups
 */
class Test_Synchronizer_Groups extends WP_UnitTestCase {

	private $utils;

	public function setUp() {
		parent::setUp();
		$this->utils = new Test_Utils();
		$this->synchronizer = CCB_Core_Synchronizer::instance();
		$this->synchronizer->map = $this->utils->synchronizer_get_groups_map();
	}

	/**
	 * Test the insert of groups when the database is empty.
	 */
	public function test_update_content_insert_groups() {
		// Testing the $result is a successful insert.
		$result = $this->synchronizer->update_content(
			$this->utils->api_mock_response( 'group_profiles_sample.xml' ),
			$this->synchronizer->map['ccb_core_group'],
			'ccb_core_group'
		);

		$expected_result = [
			'success' => true,
			'insert_update' => [
				'success' => true,
				'processed' => 4,
				'message' => '',
			],
			'delete' => [
				'success' => true,
				'processed' => 0,
			],
		];

		$this->assertEqualSets( $expected_result, $result );
	}

	/**
	 * Test when some groups have been updated in CCB.
	 */
	public function test_update_content_update_groups() {
		// Insert some posts.
		$result = $this->synchronizer->update_content(
			$this->utils->api_mock_response( 'group_profiles_sample.xml' ),
			$this->synchronizer->map['ccb_core_group'],
			'ccb_core_group'
		);

		// Testing the $result has some successful updates.
		$result = $this->synchronizer->update_content(
			$this->utils->api_mock_response( 'group_profiles_some_updated_sample.xml' ),
			$this->synchronizer->map['ccb_core_group'],
			'ccb_core_group'
		);

		$expected_result = [
			'success' => true,
			'insert_update' => [
				'success' => true,
				'processed' => 2,
				'message' => '',
			],
			'delete' => [
				'success' => true,
				'processed' => 0,
			],
		];

		$this->assertEqualSets( $expected_result, $result );

	}

	/**
	 * Test when some groups have been unlisted in CCB.
	 */
	public function test_update_content_unlisted_groups() {
		// Insert some posts.
		$result = $this->synchronizer->update_content(
			$this->utils->api_mock_response( 'group_profiles_sample.xml' ),
			$this->synchronizer->map['ccb_core_group'],
			'ccb_core_group'
		);

		// Testing the $result has deleted some unlisted posts.
		$result = $this->synchronizer->update_content(
			$this->utils->api_mock_response( 'group_profiles_some_unlisted_sample.xml' ),
			$this->synchronizer->map['ccb_core_group'],
			'ccb_core_group'
		);

		$expected_result = [
			'success' => true,
			'insert_update' => [
				'success' => true,
				'processed' => 0,
			],
			'delete' => [
				'success' => true,
				'processed' => 2,
				'message' => '',
			],
		];

		$this->assertEqualSets( $expected_result, $result );

	}

	/**
	 * Test when some groups have been inactivated in CCB.
	 */
	public function test_update_content_inactivated_groups() {
		// Insert some posts.
		$result = $this->synchronizer->update_content(
			$this->utils->api_mock_response( 'group_profiles_sample.xml' ),
			$this->synchronizer->map['ccb_core_group'],
			'ccb_core_group'
		);

		// Testing the $result has deleted some unlisted posts.
		$result = $this->synchronizer->update_content(
			$this->utils->api_mock_response( 'group_profiles_some_inactivated_sample.xml' ),
			$this->synchronizer->map['ccb_core_group'],
			'ccb_core_group'
		);

		$expected_result = [
			'success' => true,
			'insert_update' => [
				'success' => true,
				'processed' => 0,
			],
			'delete' => [
				'success' => true,
				'processed' => 2,
				'message' => '',
			],
		];

		$this->assertEqualSets( $expected_result, $result );

	}

	public function tearDown() {
		parent::tearDown();
	}

}
