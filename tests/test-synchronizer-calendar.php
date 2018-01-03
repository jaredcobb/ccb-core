<?php
/**
 * Class Test_Synchronizer_Calendar
 *
 * @package CCB_Core
 */

/**
 * Test the synchronization of calendar events
 */
class Test_Synchronizer_Calendar extends WP_UnitTestCase {

	private $utils;

	public function setUp() {
		parent::setUp();
		$this->utils = new Test_Utils();
		$this->synchronizer = CCB_Core_Synchronizer::instance();
		$this->synchronizer->map = $this->utils->synchronizer_get_calendar_map();
	}

	/**
	 * Test the insert of events when the database is empty.
	 */
	public function test_update_content_insert_events() {
		// Testing the $result is a successful insert.
		$result = $this->synchronizer->update_content(
			$this->utils->api_mock_response( 'public_calendar_listing_sample.xml' ),
			$this->synchronizer->map['ccb_core_calendar'],
			'ccb_core_calendar'
		);

		$expected_result = [
			'success' => true,
			'insert_update' => [
				'success' => true,
				'processed' => 10,
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
	 * Test when some events have been updated in CCB.
	 */
	public function test_update_content_update_events() {
		// Initial insert of events
		$result = $this->synchronizer->update_content(
			$this->utils->api_mock_response( 'public_calendar_listing_sample.xml' ),
			$this->synchronizer->map['ccb_core_calendar'],
			'ccb_core_calendar'
		);

		// Testing whether updated events from CCB get updated in WordPress.
		$result = $this->synchronizer->update_content(
			$this->utils->api_mock_response( 'public_calendar_listing_some_updated_sample.xml' ),
			$this->synchronizer->map['ccb_core_calendar'],
			'ccb_core_calendar'
		);

		// Events do not have a unique identifier from CCB, so updates
		// are actually 3 inserts and 3 deletes.
		$expected_result = [
			'success' => true,
			'insert_update' => [
				'success' => true,
				'processed' => 3,
				'message' => '',
			],
			'delete' => [
				'success' => true,
				'processed' => 3,
				'message' => '',
			],
		];

		$this->assertEqualSets( $expected_result, $result );
	}

	/**
	 * Test when some events have been deleted in CCB.
	 */
	public function test_update_content_deleted_events() {
		// Initial insert of events
		$result = $this->synchronizer->update_content(
			$this->utils->api_mock_response( 'public_calendar_listing_sample.xml' ),
			$this->synchronizer->map['ccb_core_calendar'],
			'ccb_core_calendar'
		);

		// Testing whether deleted events from CCB get deleted in WordPress.
		$result = $this->synchronizer->update_content(
			$this->utils->api_mock_response( 'public_calendar_listing_some_deleted_sample.xml' ),
			$this->synchronizer->map['ccb_core_calendar'],
			'ccb_core_calendar'
		);

		$expected_result = [
			'success' => true,
			'insert_update' => [
				'success' => true,
				'processed' => 0,
			],
			'delete' => [
				'success' => true,
				'processed' => 3,
				'message' => '',
			],
		];

		$this->assertEqualSets( $expected_result, $result );
	}

	/**
	 * Test when some events are new in CCB.
	 */
	public function test_update_content_new_events() {
		// Initial insert of events
		$result = $this->synchronizer->update_content(
			$this->utils->api_mock_response( 'public_calendar_listing_sample.xml' ),
			$this->synchronizer->map['ccb_core_calendar'],
			'ccb_core_calendar'
		);

		// Testing whether new events from CCB get inserted in WordPress.
		$result = $this->synchronizer->update_content(
			$this->utils->api_mock_response( 'public_calendar_listing_some_new_sample.xml' ),
			$this->synchronizer->map['ccb_core_calendar'],
			'ccb_core_calendar'
		);

		$expected_result = [
			'success' => true,
			'insert_update' => [
				'success' => true,
				'processed' => 3,
				'message' => '',
			],
			'delete' => [
				'success' => true,
				'processed' => 0,
			],
		];

		$this->assertEqualSets( $expected_result, $result );
	}

	public function tearDown() {
		parent::tearDown();
	}

}
