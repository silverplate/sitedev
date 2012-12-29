function documentDataUpdateBranch(_eleId, _documentId, _dataBlockId)
{
	showLoadingBar();

	var postBody = "id=" + _documentId;
	if (_dataBlockId) {
	    postBody += "&data_id=" + _dataBlockId;
	}

    $.post(
        "http_request_data.php",
        postBody,
        function (_response) {
            $("#" + _eleId).html(_response);

            $("#document_data_blocks").sortable({
                delay: 500,
                items: "div.document_data",
                update: itemSort
            });

            replaceTextareaCdata();
			hideLoadingBar();
        }
    );
}
