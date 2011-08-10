function get_random() {
	return Math.round(Math.random() * Math.random() * 100000);
}

function open_window(url, width, height, name) {
	var w = (width) ? width : 600;
	var h = (height) ? height : 400;
	var window_name = (name) ? name : 'bo_' + get_random();

	window.open(url, window_name, 'width=' + w + ',height=' + h + ',location=no,status=yes,resizable=yes,scrollbars=yes,titlebar=yes');
}

function show_form_group(name) {
	if (form_groups) {
		for (var i = 0; i < form_groups.length; i++) {
			change_element_visibility('form_group_' + form_groups[i], (form_groups[i] == name));

			var tab_ele = document.getElementById('form_group_' + form_groups[i] + '_tab');
			if (tab_ele) {
				var class_name = '';

				if (i == 0) class_name += (class_name != '' ? ' ' : '') + 'first';
				if (form_groups[i] == name) class_name += (class_name != '' ? ' ' : '') + 'selected';

				tab_ele.className = class_name;
			}
		}

		setCookie('form_group', name);
	}
}

function change_element_visibility(id, is_visible) {
	var element = document.getElementById(id);
	if (element) {
		element.style.display = is_visible || (is_visible == null && element.style.display != 'block') ? 'block' : 'none';
	}
}


/*** Loading bar
*********************************************************/
var loadings = 0;

function show_loading_bar() {
	var loading_ele = document.getElementById('loading');
	if (loading_ele && loadings == 0) {
		loading_ele.style.display = 'block';
		wait_cursor(true);
	}
	loadings++;
}

function hide_loading_bar() {
	var ele_loading = document.getElementById('loading');
	loadings--;
	if (ele_loading && loadings == 0) {
		if (ele_loading) ele_loading.style.display = 'none';
		wait_cursor(false);
	}
}

function wait_cursor(is_on) {
	var body_ele = document.getElementsByTagName('body');
	if (body_ele) {
		var classes = body_ele[0].className.split(' ');
		var class_name = '';

		for (var i = 0; i < classes.length; i++) {
			if (classes[i] != 'wait') {
				class_name = (class_name != '' ? ' ' : '') + classes[i];
			}
			if (is_on) {
				class_name = (class_name != '' ? ' ' : '') + 'wait';
			}
		}

		body_ele[0].className = class_name;
	}
}


/*** Files
*********************************************************/
function get_form_file_adding_link(name) {
	return document.getElementById('add_form_files_' + name);
}

function get_form_file_container(name) {
	return document.getElementById('form_files_' + name);
}

function get_form_file_count(name) {
	return get_form_file_container(name).getElementsByTagName('tr').length;
}

function add_form_file_inputs(name) {
	var table = document.createElement('table');
	table.className = 'form_files';
	table.setAttribute('id', 'form_files_' + name);

	get_form_file_adding_link(name).parentNode.appendChild(table);
	add_form_file_input(name);
}

function add_form_file_input(name) {
	var table = get_form_file_container(name);

	if (navigator.userAgent.indexOf('MSIE') != -1) {
		var tr = table.insertRow();
	} else {
		var tr = document.createElement('tr');
		get_form_file_container(name).appendChild(tr);
	}

	if (navigator.userAgent.indexOf('MSIE') != -1) {
		var remove_ele = tr.insertCell();
	} else {
		var remove_ele = document.createElement('td');
		tr.appendChild(remove_ele);
	}

	remove_ele.className = 'system';
	remove_ele.onclick = function () { remove_form_file_input(name, remove_ele); }
	remove_ele.innerHTML = '&times;';

	if (navigator.userAgent.indexOf('MSIE') != -1) {
		var input_ele = tr.insertCell();
	} else {
		var input_ele = document.createElement('td');
		tr.appendChild(input_ele);
	}

	input_ele.innerHTML = '<input type="file" name="' + name + '[]" class="file" />';
}

function remove_form_file_input(name, element) {
	element.parentNode.parentNode.removeChild(element.parentNode);

	if (get_form_file_count(name) == 0) {
		get_form_file_container(name).parentNode.removeChild(get_form_file_container(name));
	}
}

function delete_file(ele, path) {
	if (confirm('Удалить файл немедленно?')) {
		show_loading_bar();

		new Ajax.Request('/cms/ajax_delete_file.php', {
			asynchronous: true,
			method: 'post',
			postBody: 'f=' + path,
			onSuccess: function (r) {
				if (r.responseText == 1) {
					var file = ele.parentNode;
					var parent = file.parentNode;
					parent.removeChild(file);

					var is_child = false;
					for (var i = 0; i < parent.childNodes.length; i++) {
						if (parent.childNodes[i].nodeType == 1) {
							is_child = true;
						}
					}

					if (!is_child) parent.parentNode.removeChild(parent);
				}

				hide_loading_bar();
			}
		});
	}
}

function item_sort(ele) {
	var inputs = ele.getElementsByTagName('input');
	var post_body = '';

	for (var i = 0; i < inputs.length; i++) {
		if (inputs[i].getAttribute('type') == 'hidden') {
			if (post_body != '') post_body += '&';
			post_body += 'items[]=' + inputs[i].value;
		}
	}

	if (post_body) {
		show_loading_bar();
		new Ajax.Request('ajax_sort.php', {
			asynchronous: true,
			method: 'post',
			postBody: post_body,
			onSuccess: hide_loading_bar
		});
	}
}
