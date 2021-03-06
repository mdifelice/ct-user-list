/**
 * Codeable Test User List frontend functionality.
 *
 * @package CT_User_List
 */

if ( window.ct_user_list_options ) {
	jQuery(
		function( $ ) {
			var setInput = function( name, value ) {
				var form = $search.get( 0 );

				form[ name ].value = value;
			};

			var getInput = function( name ) {
				var form = $search.get( 0 );

				return form[ name ].value;
			};

			var setOrderBy = function( orderBy ) {
				var oldOrderBy = getInput( 'order_by' ),
					oldOrder   = getInput( 'order' ),
					order;

				if ( orderBy === oldOrderBy ) {
					order = oldOrder === 'ASC' ? 'DESC' : 'ASC';
				} else {
					order = 'ASC';
				}

				setInput( 'order_by', orderBy );
				setInput( 'order', order );
			};

			var updateTable = function() {
				var total  = data.total,
					labels = options.labels;

				$list.empty();
				$actionsTop.find( '.ct-user-list-pagination' ).remove();
				$actionsBottom.find( '.ct-user-list-pagination' ).remove();

				if ( ! total ) {
					$list.append( $( '<div/>' )
						.addClass( 'ct-user-list-not-found' )
						.text( labels.noUsersFound )
					);
				} else {
					var $table  = $( '<table/>' ),
						$head   = $( '<thead/>' ),
						$foot   = $( '<tfoot/>' ),
						$body   = $( '<tbody/>' ),
						$row    = $( '<tr/>' ),
						headers = options.headers,
						users   = data.users;

					for ( var field in headers ) {
						var header = headers[ field ],
							$cell  = $( '<th/>' );

						if ( header.sortable ) {
							var orderBy = getInput( 'order_by' ),
								$anchor = $( '<a/>' )
								.attr( 'href', '#' )
								.data( 'field', field )
								.text( header.label )
								.click(
									function( e ) {
										e.preventDefault();

										setOrderBy( $( this ).data( 'field' ) );

										$search.submit();
									}
								);

							if ( orderBy === field ) {
								var order = getInput( 'order' );

								$anchor.addClass( 'ct-user-list-ordered-' + ( order === 'ASC' ? 'ascending' : 'descending' ) );
							}

							$cell.append( $anchor );
						} else {
							$cell.text( header.label );
						}

						$row.append( $cell );
					}

					$table
						.append( $head
							.append( $row )
						);

					for ( var i in users ) {
						var user = users[ i ];

						$row = $( '<tr/>' );

						for ( var j in headers ) {
							var header = headers[ j ],
								$cell  = $( '<td/>' );

							$cell.text( user[ j ] );

							$row.append( $cell );
						}

						$body.append( $row );
					}

					$head.children().each( function() {
						var $cloned = $( this ).clone( true );

						$foot.append( $cloned );
					} );

					$table
						.append( $body )
						.append( $foot );

					$list.append( $table );

					if ( total > users.length ) {
						var $pagination = $( '<div/>' ),
							page        = parseInt( getInput( 'page' ) ) || 1,
							maxPage     = Math.ceil( total / pageLength ),
							links       = [];

						if ( page > 1 ) {
							links.push( [ 1, '«' ] );
							links.push( [ page - 1, '‹' ] );
						}

						for ( var i = Math.max( 1, page - 5 ); i <= Math.min( maxPage, page + 5 ); i++ ) {
							links.push( i );
						}

						if ( page < maxPage ) {
							links.push( [ page + 1, '›' ] );
							links.push( [ maxPage, '»' ] );
						}

						for ( var i in links ) {
							var link = links[ i ],
								number,
								label,
								$page;

							if ( typeof link === 'number' ) {
								number = label = link;
							} else {
								number = link[0];
								label  = link[1];
							}

							if ( number === page ) {
								$page = $( '<span/>' );
							} else {
								$page = $( '<a/>' )
									.attr( 'href', '#' )
									.data( 'page', number )
									.click(
										function( e ) {
											e.preventDefault();

											setInput( 'page', $( this ).data( 'page' ) );

											$search.submit();
										}
									);
							}

							$page.text( label );

							$pagination
								.addClass( 'ct-user-list-pagination' )
								.append( $page );
						}

						$actionsTop.append( $pagination )
						$actionsBottom.append( $pagination.clone( true ) )
					}
				}
			};

			var options        = ct_user_list_options,
				data           = options.data,
				pageLength     = options.page_length,
				$container     = $( '#ct-user-list-container' ),
				$list          = $container.find( '.ct-user-list' ),
				$actionsTop    = $container.find( '.ct-user-list-actions-top' ),
				$actionsBottom = $container.find( '.ct-user-list-actions-bottom' ),
				$search        = $container.find( '.ct-user-list-search' );

			$( $search.get( 0 ).role ).change(
				function() {
					setInput( 'page', 1 );

					$search.submit();
				}
			);

			$search.submit(
				function( e ) {
					e.preventDefault();

					var formData = $search.serialize();

					$container
						.addClass( 'ct-user-list-loading' )
						.find( ':input' )
						.attr( 'disabled', 'disabled' );

					$.post(
						{
							url      : $search.attr( 'action' ),
							data     : formData,
							success  : function( response ) {
								data = response;

								updateTable();
							},
							complete : function( response ) {
								$container
									.removeClass( 'ct-user-list-loading' )
									.find( ':input' )
									.removeAttr( 'disabled' );
							}
						}
					);
				}
			);

			updateTable();
		}
	);
}
