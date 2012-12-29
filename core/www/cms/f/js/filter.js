function dateFilterFromDate(_stringDateFrom, _stringDateTill)
{
	if (_stringDateFrom) {
		var fromDate = _stringDateFrom.split("-");
		calendarSet("filter_from", fromDate[0], fromDate[1] - 1, fromDate[2]);
	}

	if (_stringDateTill) {
		var tillDate = _stringDateTill.split("-");
		calendarSet("filter_till", tillDate[0], tillDate[1] - 1, tillDate[2]);

	} else {
		var now = new Date();
		calendarSet("filter_till", now.getFullYear(), now.getMonth(), now.getDate());
	}
}

function dateFilterGetDate(_name)
{
	return getCalendar(_name).getDate();
}

function hideFilter()
{
	changeElementVisibility("filter", false);
	changeElementVisibility("filter_link", true);
	setCookie("filter_is_open", "");
}

function showFilter()
{
	changeElementVisibility("filter", true);
	changeElementVisibility("filter_link", false);
	setCookie("filter_is_open", 1);
}

function filterUpdate(_eleName, _isFormSubmit, _isSortable, _isDate)
{
	var formEle = document.getElementById("filter");
	var ele = document.getElementById(_eleName);

	if (_isDate) {
		var from = dateFilterGetDate("filter_from");
		var till = dateFilterGetDate("filter_till");
	}

	if (formEle && ele && (!_isDate || (from && till))) {
		showLoadingBar();

		if (_isDate) {
			setCookie("filter_from", from.toGMTString());
			setCookie("filter_till", till.toGMTString());
		}

		if (_isFormSubmit) {
			setCookie("filter_page", "");
		}

		var inputs = new Array("title", "name", "email");

		for (var i = 0; i < inputs.length; i++) {
			var input = document.getElementById("filter_" + inputs[i]);
			setCookie("filter_" + inputs[i], input ? input.value : "");
		}

		var inputs = new Array("users", "sections", "actions", "type", "group");

		for (var i = 0; i < inputs.length; i++) {
			var checkEle = document.getElementById("is_filter_" + inputs[i]);
			if (checkEle) {
				var value = "";
				if (checkEle.checked) {
					var input = formEle.elements["filter_" + inputs[i]];
					if (!input) input = formEle.elements["filter_" + inputs[i] + "[]"];

					if (input && input.length > 0) {
						for (var j = 0; j < input.length; j++) {
							if (input[j].checked) {
								value += (value != "" ? "|" : "") + input[j].value;
							}
						}
					}
				}

				setCookie("is_filter_" + inputs[i], checkEle.checked ? 1 : "");
				setCookie("filter_" + inputs[i], value);
			}
		}

        $.post(
            "ajax-filter.php",
            $(formEle).serialize(),
            function(_response) {
                ele.innerHTML = _response;

                if (_response.indexOf("Нет") != 0 && _isSortable) {
                    $(ele).sortable({update: itemSort});
                }

                hideLoadingBar();
            }
        );
	}
}

function filterUpdateNav(_page, _isSortable)
{
	showLoadingBar();

	var formEle = document.getElementById("filter");
	var ele = document.getElementById("filter_content");
	var selectedEle = formEle.elements["filter_selected_id"];

	setCookie("filter_page", _page);

    var postBody = $(formEle).serialize() + "&page=" + _page;

    if (selectedEle) {
        postBody += "&filter_selected_id=" + selectedEle.value;
    }

    $.post(
        "ajax-filter.php",
        postBody,
        function(_response) {
            ele.innerHTML = _response;

            if (_response.indexOf("Нет") != 0 && _isSortable) {
                $(ele).sortable({update: itemSort});
            }

            hideLoadingBar();
        }
    );
}
