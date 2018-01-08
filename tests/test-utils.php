<?php
/**
 * Helper class containing sample data for the unit tests.
 *
 * @package CCB_Core
 */

/**
 * Helper class containing sample data for the unit tests.
 */
class Test_Utils {

	private $plugin_path;

	public function __construct() {
		$this->plugin_path = plugin_dir_path( __FILE__ );
	}

	public function synchronizer_get_groups_map( $include_image_link = false ) {
		return [
			'ccb_core_group' => [
				'service' => 'group_profiles',
				'data' => [
					'include_participants' => false,
					'include_image_link' => $include_image_link,
				],
				'nodes' => [ 'groups', 'group' ],
				'fields' => [
					'name' => 'post_title',
					'description' => 'post_content',
					'main_leader' => 'post_meta',
					'calendar_feed' => 'post_meta',
					'current_members' => 'post_meta',
					'group_capacity' => 'post_meta',
					'addresses' => 'post_meta',
				],
				'taxonomies' => [
					'hierarchical' => [
						'ccb_core_group_area' => 'area',
						'ccb_core_group_day' => 'meeting_day',
						'ccb_core_group_department' => 'department',
						'ccb_core_group_time' => 'meeting_time',
						'ccb_core_group_type' => 'group_type',
					],
					'nonhierarchical' => [
						'ccb_core_group_tag' => [ 'childcare_provided' => 'Childcare Provided' ],
					],
				],
			],
		];
	}

	public function synchronizer_get_calendar_map( $date_start = '', $date_end = '' ) {
		$date_start = ! empty( $date_start ) ? $date_start : date( 'Y-m-d', strtotime( '1 weeks ago' ) );
		$date_end = ! empty( $date_end ) ? $date_end : date( 'Y-m-d', strtotime( '+2 weeks' ) );
		return [
			'ccb_core_calendar' => [
				'service' => 'public_calendar_listing',
				'data' => [
					'date_start' => $date_start,
					'date_end' => $date_end,
				],
				'nodes' => [ 'items', 'item' ],
				'fields' => [
					'event_name' => 'post_title',
					'event_description' => 'post_content',
					'date' => 'post_meta',
					'start_time' => 'post_meta',
					'end_time' => 'post_meta',
					'event_duration' => 'post_meta',
					'location' => 'post_meta',
				],
				'taxonomies' => [
					'hierarchical' => [
						'ccb_core_calendar_event_type' => 'event_type',
						'ccb_core_calendar_group_name' => 'group_name',
						'ccb_core_calendar_grouping_name' => 'grouping_name',
					],
				],
			],
		];

	}

	public function api_mock_response( $xml_file, $http_success = true ) {
		$filepath = $this->plugin_path . 'xml/' . $xml_file;
		$code = $http_success ? 200 : 500;
		$status = ( false === strpos( $xml_file, 'fail' ) && $http_success ) ? 'SUCCESS' : 'ERROR';
		$message = ( false === strpos( $xml_file, 'fail' ) && $http_success ) ? '' : 'Generic unit test error message.';

		$result = [
			'code' => $code,
			'status' => $status,
			'message' => $message,
		];

		if ( file_exists( $filepath ) ) {
			$result['xml'] = file_get_contents( $filepath );
			libxml_use_internal_errors( true );
			$result['body'] = simplexml_load_string( $result['xml'] );
		}
		return $result;
	}
}
