function wysiwygInit(mode, theme, filePath, editorSelector) {
	var editorSelector = editorSelector;
	var plugins = 'table,advimage,advlink,paste,media';
	var align = 'center';
	var row1 = 'bold,italic,strikethrough,|,forecolor,backcolor,|,redo,undo,|,cut,copy,paste,pastetext,pasteword,|,cleanup,removeformat';
	var row2 = 'justifyleft,justifycenter,justifyright,justifyfull,|,bullist,numlist,|,outdent,indent,|,link,unlink,anchor,image,media,hr,charmap';
	var row3 = 'tablecontrols,|,code';
	var row4 = 'formatselect,fontselect,fontsizeselect';
	var imageListUri = '/cms/tinymce_images.js.php';

	if (filePath) imageListUri += '?dir=' + filePath;
	else {
		var filePathEle = document.getElementById('wysiwyg_file_path');
		if (filePathEle && filePathEle.value) {
			imageListUri += '?dir=' + filePathEle.value;
		}
	}

	if (theme == 'simple') {
		editorSelector = 'simple_wysiwyg';
		plugins = 'advimage,advlink,paste,media';
		row1 = 'redo,undo,|,cut,copy,paste,pastetext,pasteword,|,link,unlink,anchor,image,media,charmap,|,cleanup,removeformat';
		row2 = 'bold,italic,strikethrough,|,forecolor,backcolor,|,justifyleft,justifycenter,justifyright,justifyfull,|,bullist,numlist,|,code';
		row3 = '';
		row4 = '';
	}

	tinyMCE.init({
		mode: mode,
		theme: 'advanced',
		editor_selector: editorSelector,
		cleanup_on_startup: true,
		remove_trailing_nbsp: true,
		remove_linebreaks: false,
		fix_list_elements: true,
		fix_table_elements: true,
		verify_html: true,
		language: 'ru',
		relative_urls: false,
		document_base_url: '/',
		strict_loading_mode: false,
		apply_source_formatting: true,
		convert_fonts_to_spans: true,
		external_image_list_url: imageListUri,
		plugins: plugins,
		theme_advanced_toolbar_align: align,
		theme_advanced_buttons1: row1,
		theme_advanced_buttons2: row2,
		theme_advanced_buttons3: row3,
		theme_advanced_buttons4: row4
	});
}

function applyDataWysiwyg() {
	var elems = document.getElementsByTagName('TEXTAREA');
	for (var i = 0; i < elems.length; i++) {
		if (elems[i].className.indexOf('wysiwyg') > -1) {
			var id = elems[i].getAttribute('id');
			addWysiwyg(id);
		}
	}
}

function applyWysiwyg() {
	var elems = document.getElementsByTagName('TEXTAREA');
	for (var i = 0; i < elems.length; i++) {
		if (elems[i].className.indexOf('wysiwyg') > -1) {
			var id = elems[i].getAttribute('id');
			var theme = elems[i].className.indexOf('simple') > -1 ? 'simple' : 'advanced';
			var filePathEle = document.getElementById('wysiwyg_file_path_' + id);
			var filePath = filePathEle ? filePathEle.value : '';

			wysiwygInit('textareas', theme, filePath, 'wysiwyg_' + id)
		}
	}
}

function addWysiwyg(id) {
	tinyMCE.execCommand('mceAddControl', false, id);
}

/*
function removeWysiwyg(id) {
	tinyMCE.execCommand('mceRemoveControl', false, id);
}
*/
