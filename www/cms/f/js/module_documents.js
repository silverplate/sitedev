function documentDataUpdateBranch(eleId, documentId, dataBlockId) {
	show_loading_bar();

	var postBody = 'id=' + documentId;
	if (dataBlockId) postBody += '&data_id=' + dataBlockId;

	new Ajax.Request('http_request_data.php', {
		asynchronous: true,
		method: 'post',
		postBody: postBody,
		onComplete: function(r) {
			document.getElementById(eleId).innerHTML = r.responseText;

			Sortable.create('document_data_blocks', {
				tag: 'div',
				only: 'document_data',
				delay: 500,
				onUpdate: item_sort
			});

			window.setTimeout('hide_loading_bar();', 200);
		}
	});
}
