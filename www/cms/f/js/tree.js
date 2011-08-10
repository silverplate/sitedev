var treeIconPlus = new Image(9, 9);
treeIconPlus.src = '/cms/f/icon_plus.gif';

var treeIconMinus = new Image(9, 9);
treeIconMinus.src = '/cms/f/icon_minus.gif';

function treeLoad(updateEleId, moduleName, fieldName, parentId, type) {
	show_loading_bar();

	var postBody = 'module_name=' + moduleName + '&field_name=' + fieldName + '&type=' + type;
	if (parentId) postBody += '&parent_id=' + parentId;

	if (type != 'list') {
		var currentObjectEle = document.getElementById('current_object_id');
		if (currentObjectEle && currentObjectEle.value) postBody += '&current_object_id=' + currentObjectEle.value;
	}

	var selectedIds = eval('formTreeValues_' + fieldName);
	if (selectedIds) {
		for (var i = 0; i < selectedIds.length; i++) {
			postBody += '&selected_ids[]=' + selectedIds[i];
		}
	}

	new Ajax.Updater(updateEleId, 'ajax_tree.php', {
		asynchronous: true,
		method: 'post',
		postBody: postBody,
		onComplete: function () {
			if (type == 'list') {
				Sortable.create('tree_list', {
					tag: 'div',
					only: 'sort_item',
					tree: true,
					treeTag: 'div',
					delay: 500,
					onUpdate: function(ele) {
						updateTree(ele, fieldName);
					}
				});
			}

			hide_loading_bar();
		}
	});
}

function updateTree(ele, fieldName) {
	treeBranches = new Array();
	getTreeBranchIds('tree_list', ele, fieldName);

	if (treeBranches.length > 0) {
		show_loading_bar();
		var postBody = '';
		var j;

		for (var i = 0; i < treeBranches.length; i++) {
			postBody += '&branches[]=' + treeBranches[i][0];
			for (j = 0; j < treeBranches[i][1].length; j++) {
				postBody += '&branch_' + treeBranches[i][0] + '[]=' + treeBranches[i][1][j];
			}
		}

		new Ajax.Request('ajax_tree_sort.php', {
			asynchronous: true,
			method: 'post',
			postBody: postBody.substr(1),
			onComplete: hide_loading_bar
		});
	}
}

function getTreeBranchIds(parentEleId, ele, fieldName) {
	var parentId = parentEleId == 'tree_list' ? '' : parentEleId;
	var child;
	var idEle;
	var subItems;

	for (var i = 0; i < ele.childNodes.length; i++) {
		child = ele.childNodes[i];
		if (child.nodeName == 'DIV' && child.className == 'sort_item') {
			idEle = document.getElementById(child.getAttribute('id') + '_id');
			if (idEle) {
				addTreeBranchId(parentId, idEle.getAttribute('value'));
				subItems = document.getElementById(fieldName + '_' + idEle.getAttribute('value'));
				if (subItems) {
					getTreeBranchIds(idEle.getAttribute('value'), subItems, fieldName);
				}
			}
		}
	}
}

function addTreeBranchId(branchId, id) {
	var isBranch = false;
	for (var i = 0; i < treeBranches.length; i++) {
		if (treeBranches[i][0] == branchId) {
			treeBranches[i][1][treeBranches[i][1].length] = id;
			isBranch = true;
			break;
		}
	}

	if (!isBranch) {
		treeBranches[treeBranches.length] = new Array(branchId, new Array(id));
	}
}

function treeCollapse(aObj, moduleName, fieldName, parentId, type) {
	var updateEleId = fieldName + '_' + parentId;
	var ele = document.getElementById(updateEleId);

	if (ele.innerHTML == '') {
		ele.style.display = 'block';
		treeLoad(updateEleId, moduleName, fieldName, parentId, type);
	} else {
		ele.style.display = ele.style.display == 'block' ? 'none' : 'block';
	}

	treeImageRoll(aObj.getElementsByTagName('img')[0], ele);

	var cookieName = 'bo_tree_' + moduleName + '_' + fieldName;
	if (ele.style.display == 'block') saveIntoCookieList(cookieName, parentId, null);
	else removeFromCookieList(cookieName, parentId, null);
}

function treeImageRoll(img, ele) {
	img.src = ele.style.display == 'block' ? treeIconMinus.src : treeIconPlus.src;
}

function treeSwitcher(name) {
	var eleOpenBtn = document.getElementById(name + '_tree_open_btn');
	var eleContainer = document.getElementById(name + '_tree_container');
	eleOpenBtn.style.display = eleOpenBtn.style.display == 'none' ? 'block' : 'none';
	eleContainer.style.display = eleOpenBtn.style.display == 'none' ? 'block' : 'none';
}