/**
 * @license Copyright (c) 2003-2017, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function( config ) {
    config.allowedContent = true;
	config.extraPlugins = 'colorbutton,youtube,rutube,justify';
	config.colorButton_enableMore = true;
	
    // The toolbar groups arrangement, optimized for two toolbar rows.
	config.toolbarGroups = [
		{ name: 'clipboard',   groups: [ 'clipboard', 'undo' ] },
		{ name: 'editing',     groups: [ 'find', 'selection', 'spellchecker' ] },
		{ name: 'justify', 	   groups: [ 'justifyleft', 'justifycenter', 'justifyright', 'justifyblock' ] },
		{ name: 'links' },
		{ name: 'insert' },
		{ name: 'forms' },
		{ name: 'tools' },
		{ name: 'document',	   groups: [ 'mode', 'document', 'doctools' ] },
		{ name: 'others' },
		'/',
		{ name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ] },
		{ name: 'paragraph',   groups: [ 'list', 'indent', 'blocks', 'align', 'bidi' ] },
		{ name: 'styles' },
		{ name: 'colors' },
		{ name: 'about' }
	];
	
    config.filebrowserBrowseUrl = path + '/adminlte/bower_components/kcfinder/browse.php?opener=ckeditor&type=files';
    config.filebrowserImageBrowseUrl = path + '/adminlte/bower_components/kcfinder/browse.php?opener=ckeditor&type=images';
    config.filebrowserFlashBrowseUrl = path + '/adminlte/bower_components/kcfinder/browse.php?opener=ckeditor&type=flash';
    config.filebrowserUploadUrl = path + '/adminlte/bower_components/kcfinder/upload.php?opener=ckeditor&type=files';
    config.filebrowserImageUploadUrl = path + '/adminlte/bower_components/kcfinder/upload.php?opener=ckeditor&type=images';
    config.filebrowserFlashUploadUrl = path + '/adminlte/bower_components/kcfinder/upload.php?opener=ckeditor&type=flash';
};
