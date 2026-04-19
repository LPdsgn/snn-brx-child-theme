( function ( wp ) {
	var el              = wp.element.createElement;
	var Fragment        = wp.element.Fragment;
	var useBlockProps   = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var MediaUpload     = wp.blockEditor.MediaUpload;
	var PanelBody       = wp.components.PanelBody;
	var TextControl     = wp.components.TextControl;
	var ToggleControl   = wp.components.ToggleControl;
	var Button          = wp.components.Button;
	var RangeControl    = wp.components.RangeControl;
	var registerBlockType = wp.blocks.registerBlockType;
	var __              = wp.i18n.__;

	var svgIcon = el( 'svg', {
		className: 'download-box__icon-svg',
		xmlns: 'http://www.w3.org/2000/svg',
		width: 30,
		height: 30,
		viewBox: '0 0 30 30',
		fill: 'none'
	},
		el( 'path', {
			d: 'M17.5 2.5H5V27.5H25V10L17.5 2.5Z',
			stroke: '#72333D',
			strokeWidth: '1.25',
			strokeMiterlimit: '10'
		}),
		el( 'path', {
			d: 'M17.5 2.5V10H25',
			stroke: '#72333D',
			strokeWidth: '1.25',
			strokeMiterlimit: '10'
		})
	);

	registerBlockType( 'snn/download-box', {
		edit: function ( props ) {
			var attributes    = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps    = useBlockProps( { className: 'download-box-editor' } );

			var fileId          = attributes.fileId;
			var fileUrl         = attributes.fileUrl;
			var fileName        = attributes.fileName;
			var titolo          = attributes.titolo;
			var didascalia      = attributes.didascalia;
			var mostraPulsante  = attributes.mostraPulsante;
			var testoPulsante   = attributes.testoPulsante;
			var marginTop       = attributes.marginTop;
			var marginBottom    = attributes.marginBottom;

			var buttonLabel = testoPulsante || 'Scarica qui il documento';

			// Inspector (sidebar) controls
			var inspector = el( InspectorControls, null,

				// Allegato panel
				el( PanelBody, { title: 'Allegato', initialOpen: true },
					el( MediaUpload, {
						onSelect: function ( media ) {
							setAttributes( {
								fileId: media.id,
								fileUrl: media.url,
								fileName: media.filename || media.title
							} );
						},
						allowedTypes: null,
						value: fileId,
						render: function ( obj ) {
							return el( Fragment, null,
								fileId
									? el( 'div', { className: 'snn-db-file-info' },
										el( 'p', null, fileName || 'File selezionato' ),
										el( Button, {
											variant: 'secondary',
											onClick: obj.open,
											style: { marginRight: '8px' }
										}, 'Sostituisci' ),
										el( Button, {
											variant: 'tertiary',
											isDestructive: true,
											onClick: function () {
												setAttributes( { fileId: 0, fileUrl: '', fileName: '' } );
											}
										}, 'Rimuovi' )
									)
									: el( Button, {
										variant: 'secondary',
										onClick: obj.open
									}, 'Seleziona file' )
							);
						}
					})
				),

				// Descrizione panel
				el( PanelBody, { title: 'Descrizione', initialOpen: true },
					el( TextControl, {
						label: 'Titolo',
						value: titolo,
						onChange: function ( val ) { setAttributes( { titolo: val } ); }
					}),
					el( TextControl, {
						label: 'Didascalia (opzionale)',
						value: didascalia,
						onChange: function ( val ) { setAttributes( { didascalia: val } ); }
					})
				),

				// Dimensioni panel
				el( PanelBody, { title: 'Dimensioni', initialOpen: false },
					el( RangeControl, {
						label: 'Margine superiore (px)',
						value: marginTop ? parseInt( marginTop, 10 ) : 0,
						onChange: function ( val ) {
							setAttributes( { marginTop: val ? val + 'px' : '' } );
						},
						min: 0,
						max: 120,
						step: 4
					}),
					el( RangeControl, {
						label: 'Margine inferiore (px)',
						value: marginBottom ? parseInt( marginBottom, 10 ) : 0,
						onChange: function ( val ) {
							setAttributes( { marginBottom: val ? val + 'px' : '' } );
						},
						min: 0,
						max: 120,
						step: 4
					})
				),

				// Pulsante panel
				el( PanelBody, { title: 'Pulsante', initialOpen: true },
					el( ToggleControl, {
						label: 'Mostra Pulsante',
						checked: mostraPulsante,
						onChange: function ( val ) { setAttributes( { mostraPulsante: val } ); }
					}),
					mostraPulsante
						? el( TextControl, {
							label: 'Pulsante > Testo',
							help: 'Lascia vuoto per il testo predefinito: "Scarica qui il documento"',
							value: testoPulsante,
							onChange: function ( val ) { setAttributes( { testoPulsante: val } ); }
						})
						: null
				)
			);

			// Block preview
			var hasFile = fileId && fileUrl;

			var preview = hasFile && titolo
				? el( 'a', { className: 'download-box', href: '#', onClick: function (e) { e.preventDefault(); } },
					el( 'div', { className: 'download-box__icon-wrap' }, svgIcon ),
					el( 'div', { className: 'download-box__info' },
						el( 'div', { className: 'download-box__text' },
							el( 'p', null, titolo ),
							didascalia
								? el( 'p', { className: 'download-box__caption' }, didascalia )
								: null
						),
						mostraPulsante
							? el( 'span', { className: 'download-box__button' },
								buttonLabel,
								el( 'i', { className: 'ti-download' } )
							)
							: null
					)
				)
				: el( 'div', { className: 'download-box-placeholder' },
					el( 'p', null, !hasFile ? 'Seleziona un file allegato dalla sidebar.' : 'Inserisci un titolo dalla sidebar.' )
				);

			var wrapperStyle = {};
			if ( marginTop )    wrapperStyle.marginTop    = marginTop;
			if ( marginBottom ) wrapperStyle.marginBottom  = marginBottom;
			blockProps.style = Object.assign( {}, blockProps.style || {}, wrapperStyle );

			return el( Fragment, null,
				inspector,
				el( 'div', blockProps, preview )
			);
		},

		save: function () {
			return null; // Dynamic block — rendered by PHP
		}
	});

} )( window.wp );
