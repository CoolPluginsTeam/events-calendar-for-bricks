<?php
/**
 * Event venue, organizer, and detail-field data.
 *
 * @package ECBB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ECBB_Event_Data', false ) ) {

	final class ECBB_Event_Data {

		/** @var array<int,int> */
		private static $venue_id_cache = [];

		/** @var array<int,int> */
		private static $organizer_id_cache = [];

		/** @var array<int,array{start:string,end:string}> */
		private static $date_cache = [];

		private static function venue_tribe_meta_map() {
			return [
				'venue_street'  => [ 'tribe_get_address', '_VenueAddress' ],
				'venue_city'    => [ 'tribe_get_city', '_VenueCity' ],
				'venue_zip'     => [ 'tribe_get_zip', '_VenueZip' ],
				'venue_country' => [ 'tribe_get_country', '_VenueCountry' ],
				'venue_phone'   => [ 'tribe_get_phone', '_VenuePhone' ],
			];
		}

		private static function ecbb_resolve_venue_tribe_meta( $event_id, $tribe_fn, $meta_key ) {
			if ( is_string( $tribe_fn ) && $tribe_fn !== '' && function_exists( $tribe_fn ) ) {
				$t = trim( (string) call_user_func( $tribe_fn, $event_id ) );
				if ( $t !== '' ) {
					return $t;
				}
			}
			$vid = self::ecbb_event_meta_venue_id( $event_id );
			if ( $vid < 1 ) {
				return '';
			}
			return trim( (string) get_post_meta( $vid, $meta_key, true ) );
		}

		/** Detail part slug → callable resolver. */
		private static function detail_resolvers() {
			static $map = null;
			if ( is_array( $map ) ) {
				return $map;
			}

			$map = [
				'venue_full_address'  => [ self::class, 'ecbb_resolve_detail_venue_full_address' ],
				'venue_state'         => [ self::class, 'ecbb_resolve_detail_venue_state' ],
				'venue_website'       => [ self::class, 'ecbb_resolve_detail_venue_website' ],
				'event_map_link'      => [ self::class, 'ecbb_resolve_detail_event_map_link' ],
				'event_website'       => [ self::class, 'ecbb_resolve_detail_event_website' ],
				'event_phone'         => [ self::class, 'ecbb_resolve_detail_event_phone' ],
				'organizer_email'     => [ self::class, 'ecbb_resolve_detail_organizer_email' ],
				'organizer_phone'     => [ self::class, 'ecbb_resolve_detail_organizer_phone' ],
				'organizer_website'   => [ self::class, 'ecbb_resolve_detail_organizer_website' ],
			];

			foreach ( self::venue_tribe_meta_map() as $slug => $cfg ) {
				$map[ $slug ] = static function ( $event_id ) use ( $cfg ) {
					return self::ecbb_resolve_venue_tribe_meta( $event_id, $cfg[0], $cfg[1] );
				};
			}

			return $map;
		}

		private static function ecbb_resolve_detail_venue_full_address( $event_id ) {
			return self::ecbb_venue_full_address_text( $event_id );
		}

		private static function ecbb_resolve_detail_venue_state( $event_id ) {
			if ( function_exists( 'tribe_get_province' ) ) {
				$t = trim( (string) \tribe_get_province( $event_id ) );
				if ( $t !== '' ) {
					return $t;
				}
			}
			if ( function_exists( 'tribe_get_state' ) ) {
				$t = trim( (string) \tribe_get_state( $event_id ) );
				if ( $t !== '' ) {
					return $t;
				}
			}
			$vid = self::ecbb_event_meta_venue_id( $event_id );
			if ( $vid < 1 ) {
				return '';
			}
			$s = get_post_meta( $vid, '_VenueStateProvince', true );
			if ( $s === '' || $s === null ) {
				$s = get_post_meta( $vid, '_VenueState', true );
			}
			return trim( (string) $s );
		}

		private static function ecbb_resolve_detail_venue_website( $event_id ) {
			if ( function_exists( 'tribe_get_venue_website_url' ) ) {
				$t = trim( (string) \tribe_get_venue_website_url( $event_id ) );
				if ( $t !== '' ) {
					return $t;
				}
			}
			$vid = self::ecbb_event_meta_venue_id( $event_id );
			if ( $vid > 0 ) {
				$t = trim( (string) get_post_meta( $vid, '_VenueURL', true ) );
				if ( $t !== '' ) {
					return $t;
				}
			}
			return '';
		}

		private static function ecbb_resolve_detail_event_map_link( $event_id ) {
			if ( function_exists( 'tribe_get_map_link_url' ) ) {
				return trim( (string) \tribe_get_map_link_url( $event_id ) );
			}
			if ( function_exists( 'tribe_get_map_link' ) ) {
				$raw = (string) \tribe_get_map_link( $event_id );
				if ( preg_match( '/href=[\"\\\']([^\"\\\']+)[\"\\\']/', $raw, $m ) ) {
					return trim( $m[1] );
				}
			}
			return '';
		}

		private static function ecbb_resolve_detail_event_website( $event_id ) {
			if ( function_exists( 'tribe_get_event_website_url' ) ) {
				$t = trim( (string) \tribe_get_event_website_url( $event_id ) );
				if ( $t !== '' ) {
					return $t;
				}
			}
			$m = get_post_meta( $event_id, '_EventUrl', true );
			return $m ? trim( (string) $m ) : '';
		}

		private static function ecbb_resolve_detail_event_phone( $event_id ) {
			$m = get_post_meta( $event_id, '_EventPhone', true );
			return $m ? trim( wp_strip_all_tags( (string) $m ) ) : '';
		}

		private static function ecbb_resolve_detail_organizer_email( $event_id ) {
			if ( function_exists( 'tribe_get_organizer_email' ) ) {
				$t = trim( (string) \tribe_get_organizer_email( $event_id ) );
				if ( $t !== '' ) {
					return $t;
				}
			}
			$oid = self::ecbb_event_meta_organizer_id( $event_id );
			if ( $oid > 0 ) {
				$t = trim( (string) get_post_meta( $oid, '_OrganizerEmail', true ) );
				if ( $t !== '' ) {
					return $t;
				}
			}
			return '';
		}

		private static function ecbb_resolve_detail_organizer_phone( $event_id ) {
			if ( function_exists( 'tribe_get_organizer_phone' ) ) {
				$t = trim( (string) \tribe_get_organizer_phone( $event_id ) );
				if ( $t !== '' ) {
					return $t;
				}
			}
			$oid = self::ecbb_event_meta_organizer_id( $event_id );
			if ( $oid > 0 ) {
				$t = trim( (string) get_post_meta( $oid, '_OrganizerPhone', true ) );
				if ( $t !== '' ) {
					return $t;
				}
			}
			return '';
		}

		private static function ecbb_resolve_detail_organizer_website( $event_id ) {
			if ( function_exists( 'tribe_get_organizer_website_url' ) ) {
				$t = trim( (string) \tribe_get_organizer_website_url( $event_id ) );
				if ( $t !== '' ) {
					return $t;
				}
			}
			$oid = self::ecbb_event_meta_organizer_id( $event_id );
			if ( $oid > 0 ) {
				$t = trim( (string) get_post_meta( $oid, '_OrganizerWebsite', true ) );
				if ( $t !== '' ) {
					return $t;
				}
			}
			return '';
		}

		/** Plain-text value for a detail part slug (caller escapes for HTML). */
		public static function ecbb_part_detail_text( $event_id, $part ) {
			$event_id = (int) $event_id;
			$part     = (string) $part;
			if ( $event_id < 1 ) {
				return '';
			}

			$resolvers = self::detail_resolvers();
			if ( ! isset( $resolvers[ $part ] ) ) {
				return '';
			}

			return (string) call_user_func( $resolvers[ $part ], $event_id );
		}

		/** Cached `_EventStartDate` / `_EventEndDate` per event per request. */
		public static function ecbb_event_meta_dates( $event_id ) {
			$event_id = (int) $event_id;
			if ( $event_id < 1 ) {
				return [ 'start' => '', 'end' => '' ];
			}
			if ( ! array_key_exists( $event_id, self::$date_cache ) ) {
				self::$date_cache[ $event_id ] = [
					'start' => (string) get_post_meta( $event_id, '_EventStartDate', true ),
					'end'   => (string) get_post_meta( $event_id, '_EventEndDate', true ),
				];
			}
			return self::$date_cache[ $event_id ];
		}

		/** Raw `_EventStartDate` string. */
		public static function ecbb_event_start_date_raw( $event_id ) {
			return self::ecbb_event_meta_dates( $event_id )['start'];
		}

		/** Raw `_EventEndDate` string. */
		public static function ecbb_event_end_date_raw( $event_id ) {
			return self::ecbb_event_meta_dates( $event_id )['end'];
		}

		/** Cached `_EventVenueID` (0 when missing). */
		private static function ecbb_event_meta_venue_id( $event_id ) {
			$event_id = (int) $event_id;
			if ( $event_id < 1 ) {
				return 0;
			}
			if ( ! array_key_exists( $event_id, self::$venue_id_cache ) ) {
				self::$venue_id_cache[ $event_id ] = (int) get_post_meta( $event_id, '_EventVenueID', true );
			}
			return self::$venue_id_cache[ $event_id ];
		}

		/** Cached `_EventOrganizerID` (0 when missing). */
		private static function ecbb_event_meta_organizer_id( $event_id ) {
			$event_id = (int) $event_id;
			if ( $event_id < 1 ) {
				return 0;
			}
			if ( ! array_key_exists( $event_id, self::$organizer_id_cache ) ) {
				self::$organizer_id_cache[ $event_id ] = (int) get_post_meta( $event_id, '_EventOrganizerID', true );
			}
			return self::$organizer_id_cache[ $event_id ];
		}

		public static function ecbb_venue_id( $event_id ) {
			$event_id = (int) $event_id;
			if ( $event_id < 1 ) {
				return 0;
			}
			if ( function_exists( 'tribe_get_venue_id' ) ) {
				$venue_id = (int) \tribe_get_venue_id( $event_id );
				if ( $venue_id > 0 ) {
					return $venue_id;
				}
			}
			return self::ecbb_event_meta_venue_id( $event_id );
		}

		public static function ecbb_venue_name( $event_id ) {
			$event_id = (int) $event_id;
			if ( $event_id < 1 ) {
				return '';
			}
			$venue = '';
			if ( function_exists( 'tribe_get_venue' ) ) {
				$venue = trim( (string) \tribe_get_venue( $event_id ) );
			}
			if ( $venue === '' ) {
				$venue_id = self::ecbb_event_meta_venue_id( $event_id );
				if ( $venue_id ) {
					$venue = trim( (string) get_the_title( $venue_id ) );
				}
			}
			return $venue;
		}

		private static function ecbb_resolve_tribe_full_address( array $ids, $strip_venue_name_for_event_id = 0 ) {
			if ( ! function_exists( 'tribe_get_full_address' ) ) {
				return '';
			}
			foreach ( $ids as $try_id ) {
				$raw = (string) \tribe_get_full_address( $try_id );
				$raw = preg_replace( '/<br\s*\/?>/i', ', ', $raw );
				$t   = trim( wp_strip_all_tags( html_entity_decode( $raw, ENT_QUOTES, 'UTF-8' ) ) );
				if ( $t === '' ) {
					continue;
				}
				if ( $strip_venue_name_for_event_id > 0 ) {
					$name = self::ecbb_venue_name( $strip_venue_name_for_event_id );
					if ( $name !== '' && strcasecmp( $t, $name ) === 0 ) {
						continue;
					}
					if ( $name !== '' && stripos( $t, $name ) === 0 ) {
						$t = trim( preg_replace( '/^' . preg_quote( $name, '/' ) . '\s*,\s*/i', '', $t ) );
					}
					if ( $t === '' ) {
						continue;
					}
				}
				return $t;
			}
			return '';
		}

		public static function ecbb_venue_address( $event_id ) {
			$event_id = (int) $event_id;
			if ( $event_id < 1 ) {
				return '';
			}
			$ids = [ $event_id ];
			$vid = self::ecbb_venue_id( $event_id );
			if ( $vid > 0 ) {
				array_unshift( $ids, $vid );
			}
			$ids = array_values( array_unique( $ids ) );

			$address = self::ecbb_resolve_tribe_full_address( $ids, $event_id );
			if ( $address !== '' ) {
				return $address;
			}

			if ( $vid > 0 ) {
				$state = get_post_meta( $vid, '_VenueStateProvince', true );
				if ( $state === '' || $state === null ) {
					$state = get_post_meta( $vid, '_VenueState', true );
				}
				$bits = array_filter(
					array_map(
						'trim',
						[
							(string) get_post_meta( $vid, '_VenueAddress', true ),
							(string) get_post_meta( $vid, '_VenueCity', true ),
							trim( (string) $state . ' ' . (string) get_post_meta( $vid, '_VenueZip', true ) ),
							(string) get_post_meta( $vid, '_VenueCountry', true ),
						]
					)
				);
				if ( $bits !== [] ) {
					return implode( ', ', $bits );
				}
			}

			return self::ecbb_venue_full_address_text( $event_id );
		}

		public static function ecbb_venue_name_addr( $event_id ) {
			$name    = self::ecbb_venue_name( $event_id );
			$address = self::ecbb_venue_address( $event_id );
			if ( $name === '' && $address === '' ) {
				return '';
			}
			if ( $name === '' ) {
				return $address;
			}
			if ( $address === '' ) {
				return $name;
			}
			return $name . ', ' . $address;
		}

		public static function ecbb_venue_name_state( $event_id ) {
			$name  = trim( (string) self::ecbb_venue_name( $event_id ) );
			$state = trim( (string) self::ecbb_part_detail_text( $event_id, 'venue_state' ) );
			if ( $name === '' && $state === '' ) {
				return '';
			}
			if ( $name === '' ) {
				return $state;
			}
			if ( $state === '' ) {
				return $name;
			}
			return $name . ', ' . $state;
		}

		public static function ecbb_venue_name_city( $event_id ) {
			$name = trim( (string) self::ecbb_venue_name( $event_id ) );
			$city = trim( (string) self::ecbb_part_detail_text( $event_id, 'venue_city' ) );
			if ( $name === '' && $city === '' ) {
				return '';
			}
			if ( $name === '' ) {
				return $city;
			}
			if ( $city === '' ) {
				return $name;
			}
			return $name . ', ' . $city;
		}

		public static function ecbb_venue_display_key( array $item, $skin = '' ) {
			$display = isset( $item['venue_display'] ) ? (string) $item['venue_display'] : '';
			if ( $display === '' || $display === 'name_and_address' ) {
				$skin = (string) $skin;
				if ( $skin === 'style1' || $skin === 'style2' ) {
					return 'name_and_city';
				}
				return 'name';
			}
			return $display;
		}

		public static function ecbb_venue_uses_full( array $item, $skin = '' ) {
			$display = self::ecbb_venue_display_key( $item, $skin );
			return in_array( $display, [ 'full_details', 'name_and_address' ], true );
		}

		public static function ecbb_venue_text( $event_id, array $item, $skin = '' ) {
			$event_id = (int) $event_id;
			if ( $event_id < 1 ) {
				return '';
			}
			if ( self::ecbb_venue_uses_full( $item, $skin ) ) {
				return self::ecbb_venue_name_addr( $event_id );
			}
			$display = self::ecbb_venue_display_key( $item, $skin );
			if ( $display === 'name_and_state' ) {
				return self::ecbb_venue_name_state( $event_id );
			}
			if ( $display === 'name_and_city' ) {
				return self::ecbb_venue_name_city( $event_id );
			}
			return self::ecbb_venue_name( $event_id );
		}

		private static function ecbb_venue_full_address_text( $event_id ) {
			$event_id = (int) $event_id;
			if ( $event_id < 1 ) {
				return '';
			}

			$address_ids = [ $event_id ];
			$venue_id    = self::ecbb_venue_id( $event_id );
			if ( $venue_id > 0 ) {
				array_unshift( $address_ids, $venue_id );
			}
			$address_ids = array_values( array_unique( $address_ids ) );

			$address = self::ecbb_resolve_tribe_full_address( $address_ids );
			if ( $address !== '' ) {
				return $address;
			}

			$bits = array_filter(
				array_map(
					'trim',
					[
						self::ecbb_part_detail_text( $event_id, 'venue_street' ),
						self::ecbb_part_detail_text( $event_id, 'venue_city' ),
						trim(
							self::ecbb_part_detail_text( $event_id, 'venue_state' )
							. ' '
							. self::ecbb_part_detail_text( $event_id, 'venue_zip' )
						),
						self::ecbb_part_detail_text( $event_id, 'venue_country' ),
					]
				)
			);

			return implode( ', ', $bits );
		}

		public static function ecbb_organizer_name( $event_id ) {
			$event_id = (int) $event_id;
			if ( $event_id < 1 ) {
				return '';
			}
			$name = '';
			if ( function_exists( 'tribe_get_organizer' ) ) {
				$name = trim( (string) \tribe_get_organizer( $event_id ) );
			}
			if ( $name === '' ) {
				$oid = self::ecbb_event_meta_organizer_id( $event_id );
				if ( $oid ) {
					$name = trim( (string) get_the_title( $oid ) );
				}
			}
			return $name;
		}

		public static function ecbb_organizer_full( $event_id ) {
			$bits = array_filter(
				array_map(
					'trim',
					[
						self::ecbb_organizer_name( $event_id ),
						self::ecbb_part_detail_text( $event_id, 'organizer_email' ),
						self::ecbb_part_detail_text( $event_id, 'organizer_phone' ),
						self::ecbb_part_detail_text( $event_id, 'organizer_website' ),
					]
				)
			);
			return implode( ', ', $bits );
		}

		public static function ecbb_organizer_uses_full( array $item ) {
			$display = isset( $item['organizer_display'] ) ? (string) $item['organizer_display'] : 'full_details';
			if ( $display === '' ) {
				$display = 'full_details';
			}
			return $display === 'full_details';
		}

		public static function ecbb_organizer_text( $event_id, array $item ) {
			$event_id = (int) $event_id;
			if ( $event_id < 1 ) {
				return '';
			}
			if ( self::ecbb_organizer_uses_full( $item ) ) {
				return self::ecbb_organizer_full( $event_id );
			}
			return self::ecbb_organizer_name( $event_id );
		}
	}
}