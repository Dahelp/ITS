/*

* Rutube Embed Plugin

*

* @author 

* @version 2.0.9

*/

( function() {

	CKEDITOR.plugins.add( 'rutube',

	{

		lang: [ 'en', 'ru'],

		init: function( editor )

		{

			editor.addCommand( 'rutube', new CKEDITOR.dialogCommand( 'rutube', {

				allowedContent: 'div{*}; iframe{*}[!width,!height,!src,!frameborder,!allowfullscreen]'

			}));



			editor.ui.addButton( 'Rutube',

			{

				label : editor.lang.rutube.button,

				toolbar : 'insert',

				command : 'rutube',

				icon : this.path + 'images/icon.png'

			});



			CKEDITOR.dialog.add( 'rutube', function ( instance )

			{

				var video;



				return {

					title : editor.lang.rutube.title,

					minWidth : 500,

					minHeight : 200,

					contents :

						[{

							id : 'rutubePlugin',

							expand : true,

							elements :

								[{

									id : 'txtEmbed',

									type : 'textarea',

									label : editor.lang.rutube.txtEmbed,

									autofocus : 'autofocus',

									onChange : function ( api )

									{

										handleEmbedChangeRT( this, api );

									},

									onKeyUp : function ( api )

									{

										handleEmbedChangeRT( this, api );

									},

									validate : function ()

									{

										if ( this.isEnabled() )

										{

											if ( !this.getValue() )

											{

												alert( editor.lang.rutube.noCode );

												return false;

											}

											else

											if ( this.getValue().length === 0 || this.getValue().indexOf( '//' ) === -1 )

											{

												alert( editor.lang.rutube.invalidEmbed );

												return false;

											}

										}

									}

								},

								{

									type : 'html',

									html : editor.lang.rutube.or + '<hr>'

								},

								{

									type : 'hbox',

									widths : [ '70%', '15%', '15%' ],

									children :

									[

										{

											id : 'txtUrl',

											type : 'text',

											label : editor.lang.rutube.txtUrl,

											onChange : function ( api )

											{

												handleLinkChangeRT( this, api );

											},

											onKeyUp : function ( api )

											{

												handleLinkChangeRT( this, api );

											},

											validate : function ()

											{

												if ( this.isEnabled() )

												{

													if ( !this.getValue() )

													{

														alert( editor.lang.rutube.noCode );

														return false;

													}

													else{

														video = rtVidId(this.getValue());



														if ( this.getValue().length === 0 ||  video === false)

														{

															alert( editor.lang.rutube.invalidUrl );

															return false;

														}

													}

												}

											}

										},

										{

											type : 'text',

											id : 'txtWidth',

											width : '60px',

											label : editor.lang.rutube.txtWidth,

											'default' : editor.config.rutube_width != null ? editor.config.rutube_width : '1280',

											validate : function ()

											{

												if ( this.getValue() )

												{

													var width = parseInt ( this.getValue() ) || 0;



													if ( width === 0 )

													{

														alert( editor.lang.rutube.invalidWidth );

														return false;

													}

												}

												else {

													alert( editor.lang.rutube.noWidth );

													return false;

												}

											}

										},

										{

											type : 'text',

											id : 'txtHeight',

											width : '60px',

											label : editor.lang.rutube.txtHeight,

											'default' : editor.config.rutube_height != null ? editor.config.rutube_height : '720',

											validate : function ()

											{

												if ( this.getValue() )

												{

													var height = parseInt ( this.getValue() ) || 0;



													if ( height === 0 )

													{

														alert( editor.lang.rutube.invalidHeight );

														return false;

													}

												}

												else {

													alert( editor.lang.rutube.noHeight );

													return false;

												}

											}

										}

									]

								},

								{

									type : 'hbox',

									widths : [ '100%'],

									children :

										[
											{

												id : 'chkResponsive',

												type : 'checkbox',

												label : editor.lang.rutube.txtResponsive,

												'default' : editor.config.rutube_responsive != null ? editor.config.rutube_responsive : false

											}

										]

								},

								{

									type : 'hbox',

									widths : [ '55%', '45%' ],

									children :

									[

										{

											id : 'chkSkinColor',

											type : 'text',

											'default' : editor.config.rutube_skin_color != null ? editor.config.skin_color : '',

											label : editor.lang.rutube.chkSkinColor

										}

									]

								},

								{

									type : 'hbox',

									widths : [ '55%', '45%'],

									children :

									[

										{

											id : 'txtStartAt',

											type : 'text',

											label : editor.lang.rutube.txtStartAt,

											validate : function ()

											{

												if ( this.getValue() )

												{

													var str = this.getValue();



													if ( !/^(?:(?:([01]?\d|2[0-3]):)?([0-5]?\d):)?([0-5]?\d)$/i.test( str ) )

													{

														alert( editor.lang.rutube.invalidTime );

														return false;

													}

												}

											}

										},

										{

											id: 'empty',

											type: 'html',

											html: ''

										}

									]

								}

							]

						}

					],

					onOk: function()

					{

						var content = '';

						var responsiveStyle='';



						if ( this.getContentElement( 'rutubePlugin', 'txtEmbed' ).isEnabled() )

						{

							content = this.getValueOf( 'rutubePlugin', 'txtEmbed' );

						}

						else {

							var url = 'https://', params = [], startSecs;

							var width = this.getValueOf( 'rutubePlugin', 'txtWidth' );

							var height = this.getValueOf( 'rutubePlugin', 'txtHeight' );

							var skinColor = this.getValueOf( 'rutubePlugin', 'chkSkinColor' );

							url += 'rutube.ru/play/embed/' + video;

							startSecs = this.getValueOf( 'rutubePlugin', 'txtStartAt' );

							if ( startSecs ){

								var seconds = hmsToSecondsRT( startSecs );
								params.push('t=' + seconds);

							}

							if ( skinColor !== '' ){
								params.push('skinColor='+skinColor);
							}

							if ( params.length > 0 )

							{

								url = url + '?' + params.join( '&' );

							}



							if ( this.getContentElement( 'rutubePlugin', 'chkResponsive').getValue() === true ) {

								content += '<div class="embed-responsive embed-responsive-16by9">';

								responsiveStyle = 'class="embed-responsive-item"';

							}



							

							content += '<iframe width="' + width + '" height="' + height + '" src="' + url + '" ' + responsiveStyle;

							content += 'frameborder="0" allowfullscreen uk-responsive uk-video loading="lazy" allow="clipboard-write; autoplay" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>';

							
							if ( this.getContentElement( 'rutubePlugin', 'chkResponsive').getValue() === true ) {

								content += '</div>';

							}

						}

						

						var element = CKEDITOR.dom.element.createFromHtml( content );

						var instance = this.getParentEditor();

						instance.insertElement(element);

					}

				};

			});

		}

	});

})();



function handleLinkChangeRT( el, api )

{

	if ( el.getValue().length > 0 )

	{

		el.getDialog().getContentElement( 'rutubePlugin', 'txtEmbed' ).disable();

	}

	else {

		el.getDialog().getContentElement( 'rutubePlugin', 'txtEmbed' ).enable();

	}

}



function handleEmbedChangeRT( el, api )

{

	if ( el.getValue().length > 0 )

	{

		el.getDialog().getContentElement( 'rutubePlugin', 'txtUrl' ).disable();

	}

	else {

		el.getDialog().getContentElement( 'rutubePlugin', 'txtUrl' ).enable();

	}

}


function rtVidId( url )

{

	var p = /^(?:https?:\/\/)?(?:www\.)?(?:rutube\.ru\/(?:play\/embed\/|tracks\/.*?v=|video\/|shorts\/))((\w+))(?:\S+)?$/;

	return ( url.match( p ) ) ? RegExp.$1 : false;

}



/**

 * Converts time in hms format to seconds only

 */

function hmsToSecondsRT( time )

{

	var arr = time.split(':'), s = 0, m = 1;



	while (arr.length > 0)

	{

		s += m * parseInt(arr.pop(), 10);

		m *= 60;

	}



	return s;

}

