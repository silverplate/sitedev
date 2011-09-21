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
	remove_ele.onclick = function () {
	    remove_form_file_input(name, remove_ele);
	}
	remove_ele.innerHTML = '&times;';

	if (navigator.userAgent.indexOf('MSIE') != -1) {
		var input_ele = tr.insertCell();
	} else {
		var input_ele = document.createElement('td');
		tr.appendChild(input_ele);
	}

	input_ele.innerHTML = '<input type="file" name="' + name + '[]" class="file" multiple="true" />';
}

function remove_form_file_input(name, element) {
	element.parentNode.parentNode.removeChild(element.parentNode);

	if (get_form_file_count(name) == 0) {
		get_form_file_container(name).parentNode.removeChild(get_form_file_container(name));
	}
}

function delete_file(_ele, _path) {
	if (confirm("Удалить файл немедленно?")) {
		showLoadingBar();

        $.post(
            "/cms/ajax_delete_file.php",
            "f=" + _path,
            function (_response) {
                if (_response == "1") {
                    var file = _ele.parentNode;
                    var parent = file.parentNode;
                    parent.removeChild(file);

                    var hasChild = false;
                    for (var i = 0; i < parent.childNodes.length; i++) {
                        if (parent.childNodes[i].nodeType == 1) {
                            hasChild = true;
                            break;
                        }
                    }

                    if (!hasChild) {
                        parent.parentNode.removeChild(parent);
                    }
                }

                hideLoadingBar();
            }
        );
	}
}

function itemSort(_event, _ui)
{
	var inputs = _ui.item.parent().find("input[type = 'hidden']");
	var postBody = "";

	for (var i = 0; i < inputs.length; i++) {
        postBody += "&items[]=" + inputs[i].value;
	}

	if (postBody) {
		showLoadingBar();
        $.post("ajax_sort.php", postBody.substr(1), hideLoadingBar);
	}
}


/*** Список элементов из таблицы с тройным составным первичным ключом
*********************************************************/
var tripleLinkCounter = 0;

function addTripleLink(_name)
{
    showLoadingBar();

    var containerEle = document.getElementById(_name);
    if (containerEle) {
        var count = containerEle.getElementsByTagName("table").length;

/*
        new Ajax.Request('ajax_' + _name + '.php', {
            method: 'post',
            postBody: 'name=' + _name + '&position=' + tripleLinkCounter + '&count=' + count,
            onSuccess: function(_r) {
                if (_r.responseText) {
                    var ele = document.createElement('ins');
                    ele.innerHTML = _r.responseText;
                    containerEle.appendChild(ele);
                    tripleLinkCounter++;
                }

                hideLoadingBar();
            }
        });
 */
        $.post(
            "ajax_" + _name + ".php",
            "name=" + _name + "&position=" + tripleLinkCounter + "&count=" + count,
            function (_response) {
                if (_response) {
                    var ele = document.createElement("ins");
                    ele.innerHTML = _response;
                    containerEle.appendChild(ele);
                    tripleLinkCounter++;
                }

                hideLoadingBar();
            }
        );
    }
}

function deleteTripleLink(_button)
{
    var ele = getParentElement(_button.parentNode, 'ins');
    if (!ele) {
        ele = getParentElement(_button.parentNode, 'table');
    }

    if (ele) {
        removeElement(ele);
    }
}


/*** Формы
*********************************************************/
function replaceTextareaCdata()
{
    var elems = document.getElementsByTagName("textarea");
    for (var i = 0; i < elems.length; i++) {
        elems[i].value = elems[i].value
                                 .replace(/&lt;!\[CDATA\[/gim, "<![CDATA[")
                                 .replace(/\]\]&gt;/gim, "]]>");
    }
}
