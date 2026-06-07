/**
 * Tehillim dynamic blocks — editor registration.
 *
 * No build step: written against the global wp.* packages. Each block is
 * server-rendered (ServerSideRender) and mirrors a plugin shortcode, with
 * inspector controls for its attributes.
 */
( function ( wp ) {
	'use strict';

	if ( ! wp || ! wp.blocks || ! wp.element ) {
		return;
	}

	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var __ = wp.i18n.__;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var components = wp.components;
	var ServerSideRender = wp.serverSideRender;

	/**
	 * Block catalogue. `fields` describe inspector controls:
	 *  - kind: 'number' (RangeControl/number) or 'text'
	 */
	var CATALOGUE = [
		{ name: 'global-stats', title: __( 'Tehillim — Live stats', 'tehillim-campaign-manager' ), icon: 'chart-bar', attributes: {}, fields: [] },
		{ name: 'campaigns', title: __( 'Tehillim — Campaigns grid', 'tehillim-campaign-manager' ), icon: 'grid-view', attributes: {}, fields: [] },
		{
			name: 'campaign',
			title: __( 'Tehillim — Single campaign', 'tehillim-campaign-manager' ),
			icon: 'book-alt',
			attributes: { id: { type: 'number', default: 0 } },
			fields: [ { key: 'id', kind: 'number', label: __( 'Campaign ID (0 = current)', 'tehillim-campaign-manager' ) } ]
		},
		{
			name: 'leaderboard',
			title: __( 'Tehillim — Leaderboard', 'tehillim-campaign-manager' ),
			icon: 'awards',
			attributes: { id: { type: 'number', default: 0 }, limit: { type: 'number', default: 10 } },
			fields: [
				{ key: 'id', kind: 'number', label: __( 'Campaign ID (0 = current)', 'tehillim-campaign-manager' ) },
				{ key: 'limit', kind: 'range', label: __( 'How many', 'tehillim-campaign-manager' ), min: 3, max: 25 }
			]
		},
		{
			name: 'activity',
			title: __( 'Tehillim — Activity feed', 'tehillim-campaign-manager' ),
			icon: 'list-view',
			attributes: { id: { type: 'number', default: 0 }, limit: { type: 'number', default: 12 } },
			fields: [
				{ key: 'id', kind: 'number', label: __( 'Campaign ID (0 = current)', 'tehillim-campaign-manager' ) },
				{ key: 'limit', kind: 'range', label: __( 'How many', 'tehillim-campaign-manager' ), min: 3, max: 30 }
			]
		},
		{
			name: 'segulot',
			title: __( 'Tehillim — Prayers / Segulot', 'tehillim-campaign-manager' ),
			icon: 'heart',
			attributes: { category: { type: 'string', default: '' } },
			fields: [ { key: 'category', kind: 'text', label: __( 'Category slug (optional)', 'tehillim-campaign-manager' ) } ]
		},
		{ name: 'subscribe', title: __( 'Tehillim — Daily subscribe', 'tehillim-campaign-manager' ), icon: 'email', attributes: {}, fields: [] },
		{ name: 'create-campaign', title: __( 'Tehillim — Create campaign form', 'tehillim-campaign-manager' ), icon: 'plus-alt', attributes: {}, fields: [] },
		{ name: 'my-campaigns', title: __( 'Tehillim — My campaigns', 'tehillim-campaign-manager' ), icon: 'id', attributes: {}, fields: [] },
		{ name: 'my-activity', title: __( 'Tehillim — My activity', 'tehillim-campaign-manager' ), icon: 'backup', attributes: {}, fields: [] },
		{ name: 'ambassadors', title: __( 'Tehillim — Ambassador dashboard', 'tehillim-campaign-manager' ), icon: 'groups', attributes: {}, fields: [] },
		{
			name: 'progress',
			title: __( 'Tehillim — Campaign progress', 'tehillim-campaign-manager' ),
			icon: 'chart-area',
			attributes: { id: { type: 'number', default: 0 } },
			fields: [ { key: 'id', kind: 'number', label: __( 'Campaign ID (0 = current)', 'tehillim-campaign-manager' ) } ]
		},
		{
			name: 'join',
			title: __( 'Tehillim — Join form', 'tehillim-campaign-manager' ),
			icon: 'edit',
			attributes: { id: { type: 'number', default: 0 } },
			fields: [ { key: 'id', kind: 'number', label: __( 'Campaign ID (0 = current)', 'tehillim-campaign-manager' ) } ]
		},
		{
			name: 'chapters',
			title: __( 'Tehillim — Chapters grid', 'tehillim-campaign-manager' ),
			icon: 'grid-view',
			attributes: { id: { type: 'number', default: 0 } },
			fields: [ { key: 'id', kind: 'number', label: __( 'Campaign ID (0 = current)', 'tehillim-campaign-manager' ) } ]
		},
		{
			name: 'invite',
			title: __( 'Tehillim — Ambassador invite', 'tehillim-campaign-manager' ),
			icon: 'share',
			attributes: { id: { type: 'number', default: 0 } },
			fields: [ { key: 'id', kind: 'number', label: __( 'Campaign ID (0 = current)', 'tehillim-campaign-manager' ) } ]
		}
	];

	function buildControl( field, props ) {
		var value = props.attributes[ field.key ];
		var onChange = function ( next ) {
			var patch = {};
			patch[ field.key ] = next;
			props.setAttributes( patch );
		};
		if ( field.kind === 'range' ) {
			return el( components.RangeControl, {
				key: field.key,
				label: field.label,
				value: value,
				min: field.min,
				max: field.max,
				onChange: onChange
			} );
		}
		if ( field.kind === 'number' ) {
			return el( components.TextControl, {
				key: field.key,
				type: 'number',
				label: field.label,
				value: value,
				onChange: function ( next ) {
					onChange( parseInt( next, 10 ) || 0 );
				}
			} );
		}
		return el( components.TextControl, {
			key: field.key,
			label: field.label,
			value: value,
			onChange: onChange
		} );
	}

	CATALOGUE.forEach( function ( block ) {
		wp.blocks.registerBlockType( 'tehillim/' + block.name, {
			apiVersion: 2,
			title: block.title,
			category: 'tehillim',
			icon: block.icon,
			supports: { html: false },
			attributes: block.attributes,
			edit: function ( props ) {
				var children = [];
				if ( block.fields.length ) {
					children.push(
						el(
							InspectorControls,
							{ key: 'inspector' },
							el(
								components.PanelBody,
								{ title: __( 'Settings', 'tehillim-campaign-manager' ), initialOpen: true },
								block.fields.map( function ( field ) {
									return buildControl( field, props );
								} )
							)
						)
					);
				}
				children.push(
					el( ServerSideRender, {
						key: 'ssr',
						block: 'tehillim/' + block.name,
						attributes: props.attributes
					} )
				);
				return el( Fragment, {}, children );
			},
			save: function () {
				return null;
			}
		} );
	} );
} )( window.wp );
