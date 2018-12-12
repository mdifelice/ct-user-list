/**
 * Codeable Test User List frontend functionality.
 *
 * @package CT_User_List
 */

if ( window.ct_user_list_options ) {
	jQuery( function( $ ) {
		var options    = ct_user_list_options,
			data       = options.data,
			$container = $( '#ct-user-list-container' ),
			$list      = $container.find( '.ct-user-list' ),
			$search    = $container.find( '.ct-user-list-search' );
			
		var updateTable = function() {
			var total  = data.total,
				labels = options.labels;

			$list.empty();

			if ( ! total ) {
				$list.text( labels.notFound );
			} else {
				var $table  = $( '<table/>' ),
					$row    = $( '<tr/>' );
					headers = options.headers,
					users   = data.users;

				for ( var i in headers ) {
					var header = headers[ i ],
						$cell   = $( '<th/>' );

					if ( header.sortable ) {
						$cell
							.append( $( '<a/>' )
								.attr( 'href', '#' )
								.data( 'field', i )
								.text( header.label )
								.click( function( e ) {
									e.preventDefault();
									// @todo

									$search.submit();
								} )
							);
					} else {
						$cell.text( header.label );
					}

					$row.append( $cell );
				}

				$table.append( $row );

				for ( var i in users ) {
					var user = users[ i ];

					$row = $( '<tr/>' );

					for ( var j in headers ) {
						var header = headers[ j ],
							$cell  = $( '<td/>' );

						$cell.text( user[ j ] );

						$row.append( $cell );
					}

					$table.append( $row );
				}

				$list.append( $table );

				if ( total > users.length ) {
					// @todo
					var $pagination = $( '<div/>' );

					$list.append( $pagination );
				}
			}
		};

		$( $search.get(0).role ).change( function() {
			$search.submit();
		} );

		$search.submit( function( e ) {
			e.preventDefault();

			$search.find( ':input' ).attr( 'disabled', 'disabled' );
			$list.find( 'a' ).css( 'pointer-events', 'none' );

			$.post( $search.attr( 'action' ), $search.serialize(), function( response ) {
				$search.find( ':input' ).removeAttr( 'disabled' );
				$list.find( 'a' ).css( 'pointer-events', 'all' )

				data = response;

				updateTable();
			} );
		} );

		updateTable();
	} );
}
