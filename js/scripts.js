(function( $ ) {
	
	var template = $('#jwcuf-template');
	var list = $('#jwcuf-extension-list');
	var count = 0;
	var array_user_data = select2_user_data;
	var array_used_mime_types = select2_used_mime_types;
	var array_allowed_mime_types = select2_allowed_mime_types;	
	var select_by = $('#jwcuf-select');
	var select_allowed_mime_types = $('#jwcuf-by-file-type-allowed-mime-types');
	var select_user = $('#jwcuf-user-select');
	var text_folder_name = $('#jwcuf-user-folder-name');
	var btn_add_folder = $('#jwcuf-add-folder-btn');
	var btn_delete = $('.jwcuf-delete-btn');
	var input_by_file_type = $('#jwcuf-by-file-type-input input');
	var input_default_folder_name = $('#jwcuf-default-folder-input input');
	var input_by_user = $('#jwcuf-by-user-input input');
	
	function init(){

		errors();
		
		array_allowed_mime_types = remove_from_array(array_allowed_mime_types, array_used_mime_types);
	
		// SELECT 
		select_by.change(show_hide_sections);

		select_allowed_mime_types.select2({
			placeholder: "",
			width: '100%',
			dropdownAutoWidth: true,
			multiple: true,

			data: function() {
				return {
					results: array_allowed_mime_types.sort(sortlist)
				};
			},
		});


		select_user.select2({
			placeholder: "",
			width: '100%',
			dropdownAutoWidth: true,
			multiple: true,

			data: function() {
				return {
					results: array_user_data.sort(sortlist)
				};
			},
		})

		.on("select2-selecting", function(e) {
			var search_dash = e.val.search("dash");
			var search_underscore = e.val.search("underscore");

			if (search_dash > -1) {
				array_user_data[0]['children'].push({
					id: "dash_" + count++,
					text: "dash : -",
					preview: "-"
				});
			} else if (search_underscore > -1) {
				array_user_data[0]['children'].push({
					id: "underscore_" + count++,
					text: "underscore : _",
					preview: "_"
				});
			};

		})

		.on("select2-close", function(e) {
			set_display_path();
		})

		.on("select2-removed", function(e) {
			array_user_data[0]['children'] = [];
			array_user_data[0]['children'].push({
				id: "underscore",
				text: "underscore : _",
				preview: "_"
			}, {
				id: "dash",
				text: "dash : -",
				preview: "-"
			});

			set_display_path();
		});

		//BTNS

		btn_add_folder.click(function(e) {
			e.preventDefault();

			var data_val = select_allowed_mime_types.select2('val');
			var folder_name = input_by_file_type.val();
			var clone = template.clone();

			if (folder_name.length !== 0 && $(data_val).length !== 0) {

				//$(this).parents('p').addClass('warning');
				clone.find('.jwcuf-user-folder-name').find('span').text(folder_name);
				clone.find('.jwcuf-folder-extentions').text(data_val);
				clone.find('input').attr('name', 'jwcuf_file_types[' + folder_name + ']');
				clone.find('input').val(data_val);
				
				clone.find('.jwcuf-delete-btn').click(function() {
					var target = $(this).attr('data-delete');
					var input_data = $(this).attr('data-values');
					var obj = create_select2_obj_from_array($(input_data));

					array_allowed_mime_types = add_to_array(array_allowed_mime_types, obj);

					$(target).hide("slow", function() {
						$(this).remove();
					});
				
				});

				clone.removeAttr('template');
				clone.attr('id', 'jwcuf-' + folder_name);
				clone.find('.jwcuf-delete-btn').attr('data-delete', '#jwcuf-' + folder_name);
				clone.find('.jwcuf-delete-btn').attr('data-values', data_val);

				list.append(clone);

				//CLEARS FIELDS
				select_allowed_mime_types.select2('val', '');
				input_by_file_type.val('');

				var obj = create_select2_obj_from_array($(data_val));
				array_allowed_mime_types = remove_from_array(array_allowed_mime_types, obj);
			

			} else {
				alert('Fill out "Folder Name" and "Select Extentions" fields.');
			}

		});

		btn_delete.on("click", function() {

			var target = $(this).attr('data-delete');
			var input_data = $(this).attr('data-values').split(",");			
			var obj = create_select2_obj_from_array(input_data);

			array_allowed_mime_types = add_to_array(array_allowed_mime_types, obj);
			
			$(target).hide("slow", function() {
				$(this).remove()
			});
		});

		// INPUT CHECK
		$('#jwcuf-default-folder-input input, #jwcuf-by-file-type-input input').bind('keypress', function(e) {
			var regex = new RegExp("^[a-z0-9]+$");
			var key = String.fromCharCode(!e.charCode ? e.which : e.charCode);

			if (!regex.test(key)) {
				e.preventDefault();
				return false;
			}
		});
	}

	function set_display_path() {

		var path_string = '';
		var data_data = select_user.select2('data');
		var data_value = select_user.select2('val');		

		for (var h = 0; h < $(data_data).length; h++) {
			path_string += data_data[h].preview;
			console.log(data_data[h].preview);
		};

		text_folder_name.text(path_string.toLowerCase());
		input_by_user.val(data_value);

	}

	function sortlist(a, b) {
		if (a.text < b.text) return -1;
		if (a.text > b.text) return 1;
		return 0;
	}

	function show_hide_sections() {
		var the_value = $("#jwcuf-select").val();

		if (the_value == 'by_user') {
			$('#jwcuf-by-user-group').slideDown();
		} else {
			$('#jwcuf-by-user-group').slideUp();
		}

		if (the_value == 'by_file_type') {
			$('#jwcuf-by-file-type-group').slideDown();
		} else {
			$('#jwcuf-by-file-type-group').slideUp();
		}
	}

	function remove_from_array(array1, array2){

		for (var i = 0; i < array1.length; i++) {
			
			for (var k = 0; k < array2.length; k++) {

				if (array1[i]['id'] == array2[k]['id']) {
					array1.splice(i, 1);
				};

			};
			
		};

		return array1;
	}

	function add_to_array(array1, array2){

		for (var i = 0; i < array2.length; i++) {
			array1.push({id:array2[i]['id'], text:array2[i]['text']});
		};

		return array1;
		
	}

	function create_select2_obj_from_array(array1){

		var array2 = [];

		for (var i = 0; i < array1.length; i++) {
			array2.push({id:array1[i], text:array1[i].toUpperCase()});
		};

		return array2;
	}

	function errors(){

		if($('#setting-error-jwcuf_validate_file_types').length){
			$('#jwcuf-by-file-type-input').addClass('jwcuf-error');
			$('#jwcuf-extension-list').addClass('jwcuf-error');
		}

		if($('#setting-error-jwcuf_default_folder_name').length){
			$('#jwcuf-default-folder-input').addClass('jwcuf-error');
		}

		if($('#setting-error-jwcuf_folder_name_default').length){
			$('#jwcuf-default-folder-input').addClass('jwcuf-error');
		}

		if($('#setting-error-jwcuf_validate_folder_builder').length){
			$('#jwcuf-by-user-input').addClass('jwcuf-error');
		}

	}

	init();


})( jQuery );