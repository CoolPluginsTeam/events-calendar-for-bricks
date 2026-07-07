<?php
/**
 * ECBB_Parts_Repeater_Controls service.
 *
 * @package ECBB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ECBB_Parts_Repeater_Controls', false ) ) {

	final class ECBB_Parts_Repeater_Controls {

		public static function ecbb_register_controls( $element ) {
		$event_category_options = ECBB_Query_Controls::ecbb_get_event_category_options();

		ECBB_Layout_Controls::ecbb_register_layout_controls( $element );
		ECBB_Query_Controls::ecbb_register_query_controls( $element, $event_category_options );
		ECBB_Query_Controls::ecbb_register_messages_controls( $element );
		ECBB_Card_Style_Controls::ecbb_register_style_controls( $element );
		self::ecbb_register_parts_repeaters( $element );
	}

		private static function ecbb_register_parts_repeaters( $element ) {
		$part_repeater_fields_style1 = ECBB_Part_Fields::ecbb_part_fields_for_style1();
		$part_repeater_fields_style2 = ECBB_Part_Fields::ecbb_part_fields_for_style2();
		$part_repeater_fields_grid   = ECBB_Part_Fields::ecbb_part_fields(
			class_exists( 'ECBB_Styles', false ) ? \ECBB_Styles::ecbb_part_options_grid() : null
		);

		$element->controls['parts_style1'] = self::ecbb_parts_repeater_config(
			'',
			[
				[ 'layout_template', '=', 'list' ],
				[ 'list_item_style', '=', 'style-1' ],
			],
			class_exists( 'ECBB_List_1', false ) ? \ECBB_List_1::ecbb_default_parts() : [],
			$part_repeater_fields_style1
		);

		$element->controls['parts_style2'] = self::ecbb_parts_repeater_config(
			'',
			[
				[ 'layout_template', '=', 'list' ],
				[ 'list_item_style', '=', 'style-2' ],
			],
			class_exists( 'ECBB_List_2', false ) ? \ECBB_List_2::ecbb_default_parts() : [],
			$part_repeater_fields_style2
		);

		$element->controls['parts_grid'] = self::ecbb_parts_repeater_config(
			'',
			[ 'layout_template', '=', 'grid' ],
			class_exists( 'ECBB_Grid', false ) ? \ECBB_Grid::ecbb_default_parts() : [],
			$part_repeater_fields_grid
		);
	}

		private static function ecbb_parts_repeater_config( $label, $required, array $default, array $fields ) {
		return array_merge(
			[
				'tab'           => 'content',
				'group'         => 'elements',
				'type'          => 'repeater',
				'selector'      => 'fieldId',
				'description'   => esc_html__( 'Drag rows to reorder. Expand a row to edit content and style.', 'events-calendar-for-bricks' ),
				'titleProperty' => 'part',
				'placeholder'   => esc_html__( 'Add event part', 'events-calendar-for-bricks' ),
				'rerender'      => true,
			],
			[
				'label'    => $label,
				'required' => $required,
				'default'  => $default,
				'fields'   => $fields,
			]
		);
	}

	}
}
