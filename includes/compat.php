<?php
/**
 * Legacy function aliases (ecbb_events_widget_* → ecbb_*).
 *
 * @package ECBB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'ecbb_events_widget_apply_event_cost_currency' ) ) {
	function ecbb_events_widget_apply_event_cost_currency( ...$args ) {
		return ecbb_apply_event_cost_currency( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_border_declarations' ) ) {
	function ecbb_events_widget_border_declarations( ...$args ) {
		return ecbb_border_declarations( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_border_radius_to_css' ) ) {
	function ecbb_events_widget_border_radius_to_css( ...$args ) {
		return ecbb_border_radius_to_css( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_border_to_css' ) ) {
	function ecbb_events_widget_border_to_css( ...$args ) {
		return ecbb_border_to_css( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_build_gap_responsive_css' ) ) {
	function ecbb_events_widget_build_gap_responsive_css( ...$args ) {
		return ecbb_build_gap_responsive_css( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_build_grid_cols_responsive_css' ) ) {
	function ecbb_events_widget_build_grid_cols_responsive_css( ...$args ) {
		return ecbb_build_grid_cols_responsive_css( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_build_inline_style_attr' ) ) {
	function ecbb_events_widget_build_inline_style_attr( ...$args ) {
		return ecbb_build_inline_style_attr( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_build_parts_scoped_css' ) ) {
	function ecbb_events_widget_build_parts_scoped_css( ...$args ) {
		return ecbb_build_parts_scoped_css( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_button_declarations' ) ) {
	function ecbb_events_widget_button_declarations( ...$args ) {
		return ecbb_button_declarations( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_button_part_slugs' ) ) {
	function ecbb_events_widget_button_part_slugs( ...$args ) {
		return ecbb_button_part_slugs( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_cost_token_is_free' ) ) {
	function ecbb_events_widget_cost_token_is_free( ...$args ) {
		return ecbb_cost_token_is_free( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_date_format_preset_options' ) ) {
	function ecbb_events_widget_date_format_preset_options( ...$args ) {
		return ecbb_date_format_preset_options( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_date_preset_formats' ) ) {
	function ecbb_events_widget_date_preset_formats( ...$args ) {
		return ecbb_date_preset_formats( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_date_preset_php_format' ) ) {
	function ecbb_events_widget_date_preset_php_format( ...$args ) {
		return ecbb_date_preset_php_format( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_element_set_controls' ) ) {
	function ecbb_events_widget_element_set_controls( ...$args ) {
		return ecbb_element_set_controls( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_event_cost_currency_options' ) ) {
	function ecbb_events_widget_event_cost_currency_options( ...$args ) {
		return ecbb_event_cost_currency_options( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_event_cost_currency_symbol' ) ) {
	function ecbb_events_widget_event_cost_currency_symbol( ...$args ) {
		return ecbb_event_cost_currency_symbol( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_event_part_detail_plain' ) ) {
	function ecbb_events_widget_event_part_detail_plain( ...$args ) {
		return ecbb_event_part_detail_plain( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_fetch_events_for_display' ) ) {
	function ecbb_events_widget_fetch_events_for_display( ...$args ) {
		return ecbb_fetch_events_for_display( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_format_cost_token_with_currency' ) ) {
	function ecbb_events_widget_format_cost_token_with_currency( ...$args ) {
		return ecbb_format_cost_token_with_currency( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_format_event_cost_display' ) ) {
	function ecbb_events_widget_format_event_cost_display( ...$args ) {
		return ecbb_format_event_cost_display( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_get_repeater_fields' ) ) {
	function ecbb_events_widget_get_repeater_fields( ...$args ) {
		return ecbb_get_repeater_fields( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_grid_date_block_html' ) ) {
	function ecbb_events_widget_grid_date_block_html( ...$args ) {
		return ecbb_grid_date_block_html( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_grid_default_parts_rows' ) ) {
	function ecbb_events_widget_grid_default_parts_rows( ...$args ) {
		return ecbb_grid_default_parts_rows( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_grid_item_inner_markup' ) ) {
	function ecbb_events_widget_grid_item_inner_markup( ...$args ) {
		return ecbb_grid_item_inner_markup( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_grid_normalize_parts' ) ) {
	function ecbb_events_widget_grid_normalize_parts( ...$args ) {
		return ecbb_grid_normalize_parts( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_grid_part_skipped_in_body' ) ) {
	function ecbb_events_widget_grid_part_skipped_in_body( ...$args ) {
		return ecbb_grid_part_skipped_in_body( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_grid_part_slug_stack' ) ) {
	function ecbb_events_widget_grid_part_slug_stack( ...$args ) {
		return ecbb_grid_part_slug_stack( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_grid_should_reset_parts' ) ) {
	function ecbb_events_widget_grid_should_reset_parts( ...$args ) {
		return ecbb_grid_should_reset_parts( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_grid_static_image_html' ) ) {
	function ecbb_events_widget_grid_static_image_html( ...$args ) {
		return ecbb_grid_static_image_html( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_hover_animation_scope' ) ) {
	function ecbb_events_widget_hover_animation_scope( ...$args ) {
		return ecbb_hover_animation_scope( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_hover_child_link_part_slugs' ) ) {
	function ecbb_events_widget_hover_child_link_part_slugs( ...$args ) {
		return ecbb_hover_child_link_part_slugs( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_hover_interaction_selectors' ) ) {
	function ecbb_events_widget_hover_interaction_selectors( ...$args ) {
		return ecbb_hover_interaction_selectors( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_hover_toggle_on_values' ) ) {
	function ecbb_events_widget_hover_toggle_on_values( ...$args ) {
		return ecbb_hover_toggle_on_values( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_hover_toggle_value_is_on' ) ) {
	function ecbb_events_widget_hover_toggle_value_is_on( ...$args ) {
		return ecbb_hover_toggle_value_is_on( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_is_truthy_setting' ) ) {
	function ecbb_events_widget_is_truthy_setting( ...$args ) {
		return ecbb_is_truthy_setting( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_load_more_batch_size' ) ) {
	function ecbb_events_widget_load_more_batch_size( ...$args ) {
		return ecbb_load_more_batch_size( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_load_more_enabled' ) ) {
	function ecbb_events_widget_load_more_enabled( ...$args ) {
		return ecbb_load_more_enabled( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_migrate_event_cost_currency_into_repeaters' ) ) {
	function ecbb_events_widget_migrate_event_cost_currency_into_repeaters( ...$args ) {
		return ecbb_migrate_event_cost_currency_into_repeaters( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_normalize_css_size' ) ) {
	function ecbb_events_widget_normalize_css_size( ...$args ) {
		return ecbb_normalize_css_size( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_normalize_element_parts_hover_toggles' ) ) {
	function ecbb_events_widget_normalize_element_parts_hover_toggles( ...$args ) {
		return ecbb_normalize_element_parts_hover_toggles( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_normalize_part_hover_toggle_row' ) ) {
	function ecbb_events_widget_normalize_part_hover_toggle_row( ...$args ) {
		return ecbb_normalize_part_hover_toggle_row( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_normalize_part_item' ) ) {
	function ecbb_events_widget_normalize_part_item( ...$args ) {
		return ecbb_normalize_part_item( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_normalize_parts_repeater_rows_hover' ) ) {
	function ecbb_events_widget_normalize_parts_repeater_rows_hover( ...$args ) {
		return ecbb_normalize_parts_repeater_rows_hover( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_organizer_full_details_plain' ) ) {
	function ecbb_events_widget_organizer_full_details_plain( ...$args ) {
		return ecbb_organizer_full_details_plain( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_organizer_name_plain' ) ) {
	function ecbb_events_widget_organizer_name_plain( ...$args ) {
		return ecbb_organizer_name_plain( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_organizer_part_plain_text' ) ) {
	function ecbb_events_widget_organizer_part_plain_text( ...$args ) {
		return ecbb_organizer_part_plain_text( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_organizer_part_uses_full_details' ) ) {
	function ecbb_events_widget_organizer_part_uses_full_details( ...$args ) {
		return ecbb_organizer_part_uses_full_details( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_part_button_inner_selectors' ) ) {
	function ecbb_events_widget_part_button_inner_selectors( ...$args ) {
		return ecbb_part_button_inner_selectors( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_part_chip_surface_part_slugs' ) ) {
	function ecbb_events_widget_part_chip_surface_part_slugs( ...$args ) {
		return ecbb_part_chip_surface_part_slugs( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_part_chip_surface_selectors' ) ) {
	function ecbb_events_widget_part_chip_surface_selectors( ...$args ) {
		return ecbb_part_chip_surface_selectors( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_part_field_id' ) ) {
	function ecbb_events_widget_part_field_id( ...$args ) {
		return ecbb_part_field_id( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_part_field_id_attr' ) ) {
	function ecbb_events_widget_part_field_id_attr( ...$args ) {
		return ecbb_part_field_id_attr( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_part_idx_class' ) ) {
	function ecbb_events_widget_part_idx_class( ...$args ) {
		return ecbb_part_idx_class( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_part_select_options' ) ) {
	function ecbb_events_widget_part_select_options( ...$args ) {
		return ecbb_part_select_options( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_part_title_inner_selectors' ) ) {
	function ecbb_events_widget_part_title_inner_selectors( ...$args ) {
		return ecbb_part_title_inner_selectors( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_part_typography_selectors' ) ) {
	function ecbb_events_widget_part_typography_selectors( ...$args ) {
		return ecbb_part_typography_selectors( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_part_wrap_classes' ) ) {
	function ecbb_events_widget_part_wrap_classes( ...$args ) {
		return ecbb_part_wrap_classes( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_part_wrapper_attrs' ) ) {
	function ecbb_events_widget_part_wrapper_attrs( ...$args ) {
		return ecbb_part_wrapper_attrs( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_parts_array_is_effectively_empty' ) ) {
	function ecbb_events_widget_parts_array_is_effectively_empty( ...$args ) {
		return ecbb_parts_array_is_effectively_empty( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_parts_has_part' ) ) {
	function ecbb_events_widget_parts_has_part( ...$args ) {
		return ecbb_parts_has_part( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_parts_rows_assign_ids' ) ) {
	function ecbb_events_widget_parts_rows_assign_ids( ...$args ) {
		return ecbb_parts_rows_assign_ids( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_parts_rows_clean' ) ) {
	function ecbb_events_widget_parts_rows_clean( ...$args ) {
		return ecbb_parts_rows_clean( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_parts_slug_stack' ) ) {
	function ecbb_events_widget_parts_slug_stack( ...$args ) {
		return ecbb_parts_slug_stack( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_parts_stack_matches_defaults' ) ) {
	function ecbb_events_widget_parts_stack_matches_defaults( ...$args ) {
		return ecbb_parts_stack_matches_defaults( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_query_build_date_meta_query' ) ) {
	function ecbb_events_widget_query_build_date_meta_query( ...$args ) {
		return ecbb_query_build_date_meta_query( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_query_build_tax_query' ) ) {
	function ecbb_events_widget_query_build_tax_query( ...$args ) {
		return ecbb_query_build_tax_query( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_query_category_slugs' ) ) {
	function ecbb_events_widget_query_category_slugs( ...$args ) {
		return ecbb_query_category_slugs( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_query_event_time_mode' ) ) {
	function ecbb_events_widget_query_event_time_mode( ...$args ) {
		return ecbb_query_event_time_mode( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_query_range_bounds' ) ) {
	function ecbb_events_widget_query_range_bounds( ...$args ) {
		return ecbb_query_range_bounds( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_query_resolve_event_time_type' ) ) {
	function ecbb_events_widget_query_resolve_event_time_type( ...$args ) {
		return ecbb_query_resolve_event_time_type( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_query_tribe_args' ) ) {
	function ecbb_events_widget_query_tribe_args( ...$args ) {
		return ecbb_query_tribe_args( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_read_responsive_number' ) ) {
	function ecbb_events_widget_read_responsive_number( ...$args ) {
		return ecbb_read_responsive_number( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_read_responsive_spacing' ) ) {
	function ecbb_events_widget_read_responsive_spacing( ...$args ) {
		return ecbb_read_responsive_spacing( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_render_load_more_markup' ) ) {
	function ecbb_events_widget_render_load_more_markup( ...$args ) {
		return ecbb_render_load_more_markup( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_render_settings' ) ) {
	function ecbb_events_widget_render_settings( ...$args ) {
		return ecbb_render_settings( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_repeater_btn_border_control_keys' ) ) {
	function ecbb_events_widget_repeater_btn_border_control_keys( ...$args ) {
		return ecbb_repeater_btn_border_control_keys( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_repeater_btn_style_control_keys' ) ) {
	function ecbb_events_widget_repeater_btn_style_control_keys( ...$args ) {
		return ecbb_repeater_btn_style_control_keys( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_repeater_button_inner_css_selector' ) ) {
	function ecbb_events_widget_repeater_button_inner_css_selector( ...$args ) {
		return ecbb_repeater_button_inner_css_selector( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_repeater_button_part_slugs' ) ) {
	function ecbb_events_widget_repeater_button_part_slugs( ...$args ) {
		return ecbb_repeater_button_part_slugs( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_repeater_control_css' ) ) {
	function ecbb_events_widget_repeater_control_css( ...$args ) {
		return ecbb_repeater_control_css( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_repeater_hover_background_part_slugs' ) ) {
	function ecbb_events_widget_repeater_hover_background_part_slugs( ...$args ) {
		return ecbb_repeater_hover_background_part_slugs( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_repeater_hover_control_keys' ) ) {
	function ecbb_events_widget_repeater_hover_control_keys( ...$args ) {
		return ecbb_repeater_hover_control_keys( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_repeater_hover_part_slugs' ) ) {
	function ecbb_events_widget_repeater_hover_part_slugs( ...$args ) {
		return ecbb_repeater_hover_part_slugs( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_repeater_hover_text_decoration_part_slugs' ) ) {
	function ecbb_events_widget_repeater_hover_text_decoration_part_slugs( ...$args ) {
		return ecbb_repeater_hover_text_decoration_part_slugs( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_repeater_interactive_hover_part_slugs' ) ) {
	function ecbb_events_widget_repeater_interactive_hover_part_slugs( ...$args ) {
		return ecbb_repeater_interactive_hover_part_slugs( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_repeater_required_btn_border_group' ) ) {
	function ecbb_events_widget_repeater_required_btn_border_group( ...$args ) {
		return ecbb_repeater_required_btn_border_group( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_repeater_required_btn_style_group' ) ) {
	function ecbb_events_widget_repeater_required_btn_style_group( ...$args ) {
		return ecbb_repeater_required_btn_style_group( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_repeater_required_btn_style_on' ) ) {
	function ecbb_events_widget_repeater_required_btn_style_on( ...$args ) {
		return ecbb_repeater_required_btn_style_on( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_repeater_required_hover_background' ) ) {
	function ecbb_events_widget_repeater_required_hover_background( ...$args ) {
		return ecbb_repeater_required_hover_background( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_repeater_required_hover_details' ) ) {
	function ecbb_events_widget_repeater_required_hover_details( ...$args ) {
		return ecbb_repeater_required_hover_details( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_repeater_required_hover_on' ) ) {
	function ecbb_events_widget_repeater_required_hover_on( ...$args ) {
		return ecbb_repeater_required_hover_on( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_repeater_required_hover_style_group' ) ) {
	function ecbb_events_widget_repeater_required_hover_style_group( ...$args ) {
		return ecbb_repeater_required_hover_style_group( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_repeater_required_hover_text_decoration' ) ) {
	function ecbb_events_widget_repeater_required_hover_text_decoration( ...$args ) {
		return ecbb_repeater_required_hover_text_decoration( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_repeater_required_hover_toggle_visible' ) ) {
	function ecbb_events_widget_repeater_required_hover_toggle_visible( ...$args ) {
		return ecbb_repeater_required_hover_toggle_visible( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_repeater_required_inner_background' ) ) {
	function ecbb_events_widget_repeater_required_inner_background( ...$args ) {
		return ecbb_repeater_required_inner_background( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_repeater_required_interactive_hover' ) ) {
	function ecbb_events_widget_repeater_required_interactive_hover( ...$args ) {
		return ecbb_repeater_required_interactive_hover( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_repeater_typography_control_css' ) ) {
	function ecbb_events_widget_repeater_typography_control_css( ...$args ) {
		return ecbb_repeater_typography_control_css( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_repeater_typography_css_selector' ) ) {
	function ecbb_events_widget_repeater_typography_css_selector( ...$args ) {
		return ecbb_repeater_typography_css_selector( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_resolve_event_cost_currency' ) ) {
	function ecbb_events_widget_resolve_event_cost_currency( ...$args ) {
		return ecbb_resolve_event_cost_currency( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_resolve_event_parts_for_context' ) ) {
	function ecbb_events_widget_resolve_event_parts_for_context( ...$args ) {
		return ecbb_resolve_event_parts_for_context( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_resolve_grid_cols_vars' ) ) {
	function ecbb_events_widget_resolve_grid_cols_vars( ...$args ) {
		return ecbb_resolve_grid_cols_vars( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_resolve_item_gap_css' ) ) {
	function ecbb_events_widget_resolve_item_gap_css( ...$args ) {
		return ecbb_resolve_item_gap_css( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_resolve_item_gap_unit' ) ) {
	function ecbb_events_widget_resolve_item_gap_unit( ...$args ) {
		return ecbb_resolve_item_gap_unit( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_resolve_item_gap_vars' ) ) {
	function ecbb_events_widget_resolve_item_gap_vars( ...$args ) {
		return ecbb_resolve_item_gap_vars( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_responsive_pick' ) ) {
	function ecbb_events_widget_responsive_pick( ...$args ) {
		return ecbb_responsive_pick( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_sanitize_border_shorthand' ) ) {
	function ecbb_events_widget_sanitize_border_shorthand( ...$args ) {
		return ecbb_sanitize_border_shorthand( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_sanitize_css_font_family' ) ) {
	function ecbb_events_widget_sanitize_css_font_family( ...$args ) {
		return ecbb_sanitize_css_font_family( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_sanitize_css_size_shorthand' ) ) {
	function ecbb_events_widget_sanitize_css_size_shorthand( ...$args ) {
		return ecbb_sanitize_css_size_shorthand( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_sanitize_css_size_value' ) ) {
	function ecbb_events_widget_sanitize_css_size_value( ...$args ) {
		return ecbb_sanitize_css_size_value( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_sanitize_event_cost_currency' ) ) {
	function ecbb_events_widget_sanitize_event_cost_currency( ...$args ) {
		return ecbb_sanitize_event_cost_currency( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_sanitize_load_more_settings' ) ) {
	function ecbb_events_widget_sanitize_load_more_settings( ...$args ) {
		return ecbb_sanitize_load_more_settings( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_sanitize_typography_value' ) ) {
	function ecbb_events_widget_sanitize_typography_value( ...$args ) {
		return ecbb_sanitize_typography_value( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_spacing_to_css' ) ) {
	function ecbb_events_widget_spacing_to_css( ...$args ) {
		return ecbb_spacing_to_css( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_strip_cost_currency_symbols' ) ) {
	function ecbb_events_widget_strip_cost_currency_symbols( ...$args ) {
		return ecbb_strip_cost_currency_symbols( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_style_breakpoints' ) ) {
	function ecbb_events_widget_style_breakpoints( ...$args ) {
		return ecbb_style_breakpoints( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_terms_list_html' ) ) {
	function ecbb_events_widget_terms_list_html( ...$args ) {
		return ecbb_terms_list_html( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_typography_color_from_item' ) ) {
	function ecbb_events_widget_typography_color_from_item( ...$args ) {
		return ecbb_typography_color_from_item( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_typography_declarations' ) ) {
	function ecbb_events_widget_typography_declarations( ...$args ) {
		return ecbb_typography_declarations( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_venue_full_address_plain' ) ) {
	function ecbb_events_widget_venue_full_address_plain( ...$args ) {
		return ecbb_venue_full_address_plain( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_venue_id_for_event' ) ) {
	function ecbb_events_widget_venue_id_for_event( ...$args ) {
		return ecbb_venue_id_for_event( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_venue_name_and_address_plain' ) ) {
	function ecbb_events_widget_venue_name_and_address_plain( ...$args ) {
		return ecbb_venue_name_and_address_plain( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_venue_name_and_state_plain' ) ) {
	function ecbb_events_widget_venue_name_and_state_plain( ...$args ) {
		return ecbb_venue_name_and_state_plain( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_venue_name_plain' ) ) {
	function ecbb_events_widget_venue_name_plain( ...$args ) {
		return ecbb_venue_name_plain( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_venue_part_plain_text' ) ) {
	function ecbb_events_widget_venue_part_plain_text( ...$args ) {
		return ecbb_venue_part_plain_text( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_venue_part_uses_full_details' ) ) {
	function ecbb_events_widget_venue_part_uses_full_details( ...$args ) {
		return ecbb_venue_part_uses_full_details( ...$args );
	}
}

if ( ! function_exists( 'ecbb_events_widget_venue_resolved_display' ) ) {
	function ecbb_events_widget_venue_resolved_display( ...$args ) {
		return ecbb_venue_resolved_display( ...$args );
	}
}

