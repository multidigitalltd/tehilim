/**
 * Section editing: media picker and repeaters (add, remove, reorder).
 */
( function () {
	'use strict';

	var i18n = window.svAdmin || {};

	/* ------------------------------------------------------- media */

	document.querySelectorAll( '[data-sv-media]' ).forEach( function ( box ) {
		var input = box.querySelector( '[data-sv-media-value]' );
		var preview = box.querySelector( '.sv-media__preview' );
		var pick = box.querySelector( '[data-sv-media-pick]' );
		var clear = box.querySelector( '[data-sv-media-clear]' );
		var frame = null;

		if ( pick ) {
			pick.addEventListener( 'click', function () {
				if ( ! window.wp || ! window.wp.media ) {
					return;
				}
				if ( ! frame ) {
					frame = window.wp.media( {
						title: i18n.chooseImage || '',
						button: { text: i18n.useImage || '' },
						library: { type: 'image' },
						multiple: false
					} );
					frame.on( 'select', function () {
						var attachment = frame.state().get( 'selection' ).first().toJSON();
						var url = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;
						input.value = attachment.id;
						preview.innerHTML = '';
						var img = document.createElement( 'img' );
						img.src = url;
						img.alt = '';
						preview.appendChild( img );
					} );
				}
				frame.open();
			} );
		}

		if ( clear ) {
			clear.addEventListener( 'click', function () {
				input.value = '';
				preview.innerHTML = '';
			} );
		}
	} );

	/* ---------------------------------------------------- repeaters */

	document.querySelectorAll( '[data-sv-repeater]' ).forEach( function ( repeater ) {
		var rows = repeater.querySelector( '[data-sv-rows]' );
		var add = repeater.querySelector( '[data-sv-add]' );
		var template = repeater.querySelector( '[data-sv-template]' );
		var said = repeater.querySelector( '[data-sv-said]' );
		var max = parseInt( repeater.getAttribute( 'data-max' ), 10 ) || 20;

		function list() {
			return Array.prototype.slice.call( rows.querySelectorAll( '[data-sv-row]' ) );
		}

		function renumber() {
			var all = list();
			all.forEach( function ( row, i ) {
				var num = row.querySelector( '[data-sv-num]' );
				if ( num ) {
					num.textContent = String( i + 1 );
				}
				row.querySelectorAll( '[name]' ).forEach( function ( field ) {
					field.name = field.name.replace( /\[(\d+|__index__)\]/, '[' + i + ']' );
				} );
				row.querySelectorAll( '[id]' ).forEach( function ( el ) {
					el.id = el.id.replace( /-(\d+|__index__)-/, '-' + i + '-' );
				} );
				row.querySelectorAll( 'label[for]' ).forEach( function ( el ) {
					el.htmlFor = el.htmlFor.replace( /-(\d+|__index__)-/, '-' + i + '-' );
				} );
				var up = row.querySelector( '[data-sv-move="up"]' );
				var down = row.querySelector( '[data-sv-move="down"]' );
				if ( up ) {
					up.disabled = i === 0;
				}
				if ( down ) {
					down.disabled = i === all.length - 1;
				}
			} );
			if ( add ) {
				add.disabled = all.length >= max;
			}
		}

		rows.addEventListener( 'click', function ( e ) {
			var remove = e.target.closest( '[data-sv-remove]' );
			var move = e.target.closest( '[data-sv-move]' );
			if ( remove ) {
				var row = remove.closest( '[data-sv-row]' );
				if ( row ) {
					row.remove();
					renumber();
				}
				return;
			}
			if ( move ) {
				var current = move.closest( '[data-sv-row]' );
				var sibling = move.getAttribute( 'data-sv-move' ) === 'up' ? current.previousElementSibling : current.nextElementSibling;
				if ( ! sibling ) {
					return;
				}
				if ( move.getAttribute( 'data-sv-move' ) === 'up' ) {
					rows.insertBefore( current, sibling );
				} else {
					rows.insertBefore( sibling, current );
				}
				renumber();
				move.focus();
				var all = list();
				if ( said && i18n.movedTo ) {
					said.textContent = i18n.movedTo.replace( '%1$d', all.indexOf( current ) + 1 ).replace( '%2$d', all.length );
				}
			}
		} );

		if ( add && template ) {
			add.addEventListener( 'click', function () {
				if ( list().length >= max ) {
					if ( said ) {
						said.textContent = i18n.maxRows || '';
					}
					return;
				}
				var holder = document.createElement( 'div' );
				holder.innerHTML = template.innerHTML.trim();
				var row = holder.firstElementChild;
				rows.appendChild( row );
				renumber();
				var first = row.querySelector( 'input:not([type="hidden"]), textarea' );
				if ( first ) {
					first.focus();
				}
			} );
		}

		renumber();
	} );
} )();
