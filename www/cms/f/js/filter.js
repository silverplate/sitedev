function date_filter_from_date(string_date_from, string_date_till) {
	if (string_date_from) {
		var from_date = string_date_from.split('-');
		calendar_set('filter_from', from_date[0], from_date[1] - 1, from_date[2]);
	}

	if (string_date_till) {
		var till_date = string_date_till.split('-');
		calendar_set('filter_till', till_date[0], till_date[1] - 1, till_date[2]);
	} else {
		var now = new Date();
		calendar_set('filter_till', now.getFullYear(), now.getMonth(), now.getDate());
	}
}

function date_filter_get_date(name) {
	return get_calendar(name).get_date();
}

function hide_filter() {
	change_element_visibility('filter', false);
	change_element_visibility('filter_link', true);
	setCookie('filter_is_open', '');
}

function show_filter() {
	change_element_visibility('filter', true);
	change_element_visibility('filter_link', false);
	setCookie('filter_is_open', 1);
}

function filter_update(ele_name, is_form_submit, is_sortable, is_date) {
	var form_ele = document.getElementById('filter');
	var ele = document.getElementById(ele_name);

	if (is_date) {
		var from = date_filter_get_date('filter_from');
		var till = date_filter_get_date('filter_till');
	}

	if (form_ele && ele && (!is_date || (from && till))) {
		show_loading_bar();

		if (is_date) {
			setCookie('filter_from', from.toGMTString());
			setCookie('filter_till', till.toGMTString());
		}

		if (is_form_submit) {
			setCookie('filter_page', '');
		}

		var inputs = new Array('title', 'name', 'email');
		for (var i = 0; i < inputs.length; i++) {
			var input = document.getElementById('filter_' + inputs[i]);
			setCookie('filter_' + inputs[i], input ? input.value : '');
		}

		var inputs = new Array('users', 'sections', 'actions');
		for (var i = 0; i < inputs.length; i++) {
			var check_ele = document.getElementById('is_filter_' + inputs[i]);
			if (check_ele) {
				var value = '';
				if (check_ele.checked) {
					var input = form_ele.elements['filter_' + inputs[i]];
					if (!input) input = form_ele.elements['filter_' + inputs[i] + '[]'];

					if (input && input.length > 0) {
						for (var j = 0; j < input.length; j++) {
							if (input[j].checked) {
								value += (value != '' ? '|' : '') + input[j].value;
							}
						}
					}
				}

				setCookie('is_filter_' + inputs[i], check_ele.checked ? 1 : '');
				setCookie('filter_' + inputs[i], value);
			}
		}

		new Ajax.Request('ajax_filter.php', {
			asynchronous: true,
			method: 'post',
			postBody: Form.serialize(form_ele),
			onSuccess: function (r) {
				ele.innerHTML = r.responseText;

				if (r.responseText.indexOf('Нет') != 0 && is_sortable) {
					Sortable.create(ele_name, {onUpdate: item_sort});
				}

				hide_loading_bar();
			}
		});
	}
}

function filter_update_nav(page, is_sortable) {
	show_loading_bar();

	var form_ele = document.getElementById('filter');
	var ele = document.getElementById('filter_content');
	var selected_ele = form_ele.elements['filter_selected_id'];

	setCookie('filter_page', page);

	new Ajax.Request('ajax_filter.php', {
		asynchronous: true,
		method: 'post',
		postBody: Form.serialize(form_ele) + '&page=' + page + (selected_ele ? '&filter_selected_id=' + selected_ele.value : ''),
		onSuccess: function (r) {
			ele.innerHTML = r.responseText;

			if (r.responseText.indexOf('Нет') != 0 && is_sortable) {
				Sortable.create('filter_content', {onUpdate: item_sort});
			}

			hide_loading_bar();
		}
	});
}