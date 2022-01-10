var DataTable = function(parent, id_suffix, fields, insert_fields, row_options) {
	this.parent = parent;
	this.id_suffix = id_suffix;
	this.fields = fields;
	this.insert_fields = insert_fields;
	this.row_options = row_options;

	this.get_inserted_values = function(row_suffix = null) {
		var p_sub = {};
		if (row_suffix != null) {
			p_sub["Id"] = row_suffix;
		}
		for (var insert in this.insert_fields) {
			if (!this.insert_fields.hasOwnProperty(insert)) continue;
                      	if (insert === "add_button" || insert === "total_button" || insert === "clear_button") continue;
			var id = this.parent.widget.name + "_" + this.id_suffix + "_" + insert;
			if (row_suffix != null) id += "_" + row_suffix;
                        if (this.insert_fields[insert].hasOwnProperty("join")) {
				if (this.fields[insert]["join"]["autocomplete"] != null) {
					var input_elem = document.getElementById(id + "_input");
					p_sub[insert] = input_elem.selected_value;
				} else {
		                	var select_elem = document.getElementById(id);
        	                        p_sub[insert] = select_elem.options[select_elem.selectedIndex].value;
				}
                        } else {
				if (this.insert_fields[insert]["type"] != null) {
					if (this.insert_fields[insert]["type"] == "checkbox") {
						p_sub[insert] = document.getElementById(id).checked;
					}
				} else {
	                                p_sub[insert] = document.getElementById(id).value;
				}
                        }
		}
		return p_sub;
	}

	this.get_header_row = function() {
		var row_elem = document.createElement("tr");

		for (var field in this.fields) {
                        if (!this.fields.hasOwnProperty(field)) continue;
			if (this.fields[field]["assoc_list"] != null) continue;
			var col = document.createElement("td");
			if (this.fields[field]["header"]["type"] == "img") {
				var img = document.createElement("img");
				img.src = this.fields[field]["header"]["img_src"];
				img.className = this.fields[field]["header"]["img_class"];
				img.title = this.fields[field]["title"];
				col.appendChild(img);
			} else {
				col.appendChild(document.createTextNode(this.fields[field]["header"]["text"]));
			}
			row_elem.appendChild(col);
		}

		var col = document.createElement("td");
		row_elem.appendChild(col);

		return row_elem;
	}

	this.get_insert_row = function(data, join_opts = null, row_suffix = null) {
		var row_elem = document.createElement("tr");

		for (var field in this.fields) {
			if (!this.fields.hasOwnProperty(field)) continue;
			var col = document.createElement("td");

			if (this.insert_fields[field] != null) {
				if (this.fields[field]["join"] == null) {
					var input = document.createElement("input");
					if (this.insert_fields[field]["type"] != null) {
						input.type = this.insert_fields[field]["type"];
					}
					input.id = parent.widget.name + "_" + this.id_suffix + "_" + field;
					if (row_suffix != null) input.id += "_" + row_suffix;
					if (data != null) {
						input.value = data[field];
					}
					input.placeholder = this.insert_fields[field]["placeholder"];
	                                if (this.insert_fields[field]["oninput"]) {
        	                                input.oninput = this.insert_fields[field]["oninput"];
                	                }
                        	        col.appendChild(input);
				} else {
					if (this.fields[field]["join"]["autocomplete"] != null) {
						var a_id = parent.widget.name + "_" + this.id_suffix + "_" + field;
						if (row_suffix != null) a_id += "_" + row_suffix;
						var autocomplete = new AutocompleteTextfield(a_id, join_opts[this.fields[field]["join"]["model"]], this.fields[field]["join"]["field"]);
						if (data != null) {
							autocomplete.textfield.value = join_opts[this.fields[field]["join"]["model"]][data[field]][this.fields[field]["join"]["field"]];
							autocomplete.textfield.selected_value = data[field];
						}
						col.appendChild(autocomplete.elem);
					} else {
						var select = document.createElement("select");
						select.id = parent.widget.name + "_" + this.id_suffix + "_" + field;
						if (row_suffix != null) select.id += "_" + row_suffix;
						if (this.insert_fields[field]["onchange"]) {
                        	                        select.onchange = this.insert_fields[field]["onchange"];
                	                        }


						for (var opt in join_opts[this.fields[field]["join"]["model"]]) {
							var option = document.createElement("option");
							option.value = join_opts[this.fields[field]["join"]["model"]][opt]["Id"];
							option.appendChild(document.createTextNode(join_opts[this.fields[field]["join"]["model"]][opt][this.fields[field]["join"]["field"]]));
							select.appendChild(option);
						}

						if (data != null) {
							for (var o = 0; o < select.options.length; o++) {
								if (select.options[o].value == data[field]) {
									select.selectedIndex = o;
									break;
								}
							}
						}

						col.appendChild(select);
					}
				}
				if (this.insert_fields[field]["button"]) {
					var button = document.createElement("button");
					button.id = parent.widget.name + "_" + this.id_suffix + "_" + field + "_button";
					if (row_suffix != null) button.id += "_" + row_suffix;
					button.title = this.insert_fields[field]["button"]["title"];
					if (this.insert_fields[field]["button"]["type"] == "text") {
						button.appendChild(document.createTextNode(this.insert_fields[field]["button"]["text"]));
					}
					button.onclick = this.insert_fields[field]["button"]["onclick"];
					col.appendChild(button);
				}
			} else {
				var span = document.createElement("span");
				span.id = parent.widget.name + "_" + this.id_suffix + "_" + field;
				if (row_suffix != null) span.id += "_" + row_suffix;
				col.appendChild(span);
			}
			row_elem.appendChild(col);
		}

		var col = document.createElement("td");
		var add_button = document.createElement("button");
		add_button.id = parent.widget.name + "_" + this.id_suffix + "_add_button";
		if (row_suffix != null) add_button.id += "_" + row_suffix;
       	        add_button.obj = this.parent;
               	add_button.innerHTML = "&#xFF0B;";
                add_button.onclick = this.insert_fields["add_button"]["onclick"];
		col.appendChild(add_button);

		var field = "add_button";
		if (this.insert_fields[field]["button"]) {
                	var button = document.createElement("button");
                        button.id = parent.widget.name + "_" + this.id_suffix + "_" + field + "_button";
			if (row_suffix != null) button.id += "_" + row_suffix;
                        button.title = this.insert_fields[field]["button"]["title"];
                        if (this.insert_fields[field]["button"]["type"] == "text") {
                        	button.innerHTML = this.insert_fields[field]["button"]["text"];
                        }
                        button.onclick = this.insert_fields[field]["button"]["onclick"];
                        col.appendChild(button);
                }
                
                field = "total_button";
		if (this.insert_fields[field]) {
                	var button = document.createElement("button");
                        button.id = parent.widget.name + "_" + this.id_suffix + "_" + field + "_button";
                        button.obj = this.parent;
			if (row_suffix != null) button.id += "_" + row_suffix;
                        button.title = this.insert_fields[field];
                       // if (this.insert_fields[field]["button"]["type"] == "text") {
                        //	button.innerHTML = this.insert_fields[field]["button"]["text"];
                       // }
                        button.onclick = this.insert_fields[field]["onclick"];
                        col.appendChild(button);
                }

		field = "clear_button";
		if (this.insert_fields[field]) {
                	var button = document.createElement("button");
                        button.id = parent.widget.name + "_" + this.id_suffix + "_" + field + "_button";
                        button.obj = this.parent;
			if (row_suffix != null) button.id += "_" + row_suffix;
                        button.title = this.insert_fields[field];
                       // if (this.insert_fields[field]["button"]["type"] == "text") {
                        //	button.innerHTML = this.insert_fields[field]["button"]["text"];
                       // }
                        button.onclick = this.insert_fields[field]["onclick"];
                        col.appendChild(button);
                }

		row_elem.appendChild(col);

		return row_elem;
	}

	this.get_edit_row = function(data, join_opts = null, button_override = null) {
		var row_elem = this.get_insert_row(data, join_opts, data["Id"]);
		row_elem.id = parent.widget.name + "_" + this.id_suffix + "_" + data["Id"];

		if (button_override != null) {
			var button_col = row_elem.children[row_elem.children.length - 1];
			button_col.innerHTML = "";

			for (var bo in button_override) {
				var button = document.createElement("button");
				button.obj = data;
				button.title = button_override[bo]["title"];
				button.innerHTML = button_override[bo]["text"];
				button.onclick = button_override[bo]["onclick"];
				button_col.appendChild(button);
			}
		}

		return row_elem;
	}

	this.get_data_row = function(data, join_opts = null) {
                var row_elem = document.createElement("tr");
		row_elem.id = parent.widget.name + "_" + this.id_suffix + "_" + data["Id"];

		for (var field in this.fields) {
			if (!this.fields.hasOwnProperty(field)) continue;
			var col = document.createElement("td");
			col.id = parent.widget.name + "_" + this.id_suffix + "_" + data["Id"] + "_" + field;

			var data_field = null;
			if (this.fields[field]["join"] == null && this.fields[field]["join_list"] == null && this.fields[field]["assoc_list"] == null) {
				data_field = data[field];
			} else {
				if (this.fields[field]["join"] != null) {
					data_field = join_opts[this.fields[field]["join"]["model"]][data[field]][this.fields[field]["join"]["field"]];
				} else if (this.fields[field]["join_list"] != null) {
					var data_arr = data[field].split(";");
					data_field = "";
					for (var idx in data_arr) {
						var df_div = document.createElement("div");
						df_div.innerHTML = join_opts[this.fields[field]["join_list"]["model"]][data_arr[idx]][this.fields[field]["join_list"]["field"]];
						if (this.fields[field]["join_list"]["classname_callback"] != null) {
							df_div.className = this.fields[field]["join_list"]["classname_callback"](data_arr[idx]);
						}
						col.appendChild(df_div);
					}
				} else if (this.fields[field]["assoc_list"] != null) {
					data_field = "";
					var data_arr = data[field].split(";");
					for (var idx in data_arr) {
						row_elem.children[this.fields[field]["assoc_list"]["id"]].children[idx].innerHTML += "<span style='font-style: italic; color: #0000ff;'>" + data_arr[idx] + "</span>";
					}
				}
			}
			if (data_field != null) {
				if (this.fields[field]["join_list"] == null && this.fields[field]["assoc_list"] == null) {
					col.innerHTML = data_field;
				}
			} else {
				col.appendChild(document.createTextNode("n/A"));
			}
			if (this.fields[field]["assoc_list"] == null) {
				row_elem.appendChild(col);
			}
		}

		var col = document.createElement("td");
		for (var option in this.row_options) {
			if (!this.row_options.hasOwnProperty(option)) continue;
			var button = document.createElement("button");
			button.obj = data;
			if (this.row_options[option]["type"] == "text") {
				button.innerHTML = this.row_options[option]["text"];
			} else if (this.row_options[option]["type"] == "img") {
				var img = document.createElement("img");
				img.src = this.row_options[option]["img_src"];
				img.style.width = "25px";
				button.appendChild(img);
			}
			button.onclick = this.row_options[option]["onclick"];
			col.appendChild(button);
		}
		row_elem.appendChild(col);

		return row_elem;
	}
}
