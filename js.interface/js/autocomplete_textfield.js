var AutocompleteTextfield = function(id, values, field) {
	this.elem = document.createElement("div");
	this.elem.id = id;
	this.elem.style.position = "relative";

	this.textfield = document.createElement("input");
	this.textfield.id = this.elem.id + "_input";
	this.textfield.style.margin = 0;
	this.textfield.obj = this;
	this.textfield.oninput = function() {
		var ct = 0;
		for (var c in this.obj.list.children) {
			if (!this.obj.list.children.hasOwnProperty(c)) continue;
			var c_node = this.obj.list.children[c];
			if (this.value.length > 0 && c_node.innerText.toUpperCase().includes(this.value.toUpperCase())) {
				c_node.style.display = "block";
				if (ct % 2 == 1) {
					c_node.style.backgroundColor = "var(--table_row_2n_bg)";
				} else {
					c_node.style.backgroundColor = "#ffffff";
				}
				ct++;
			} else {
				c_node.style.display = "none";
			}
			if (ct > 0) {
				this.obj.list.style.display = "block";
			} else {
				this.obj.list.style.display = "none";
			}
		}
	}

	this.list = document.createElement("div");
	this.list.id = this.elem.id + "_list";
	this.list.style.position = "absolute";
	this.list.style.backgroundColor = "#ffffff";
	this.list.style.overflowY = "auto";
	this.list.style.maxHeight = "150px";
	this.list.style.top = "45px";
	this.list.style.border = "1px solid black";
	this.list.style.padding = "5px";
	this.list.style.display = "none";
	this.list.style.zIndex = 1;
	this.values = values;

	this.init_list_items = function() {
		var values = [];
		for (var c in this.values) {
			values.push(this.values[c]);
		}
		values.sort(function(a, b) {
			var _field = field;
			var af = a[_field].toUpperCase();
			var bf = b[_field].toUpperCase();
			if (a[_field] < b[_field]) return -1;
	                if (a[_field] > b[_field]) return 1;
        	        return 0;
		});
		for (var c in values) {
			var div = document.createElement("div");
			div.obj = this;
			div.id = this.elem.id + "_list_item_" + values[c]["Id"];
			div.selected_value = values[c]["Id"];
			div.innerHTML = values[c][field];
			div.style.display = "none";
			div.onclick = function() {
				this.obj.textfield.value = this.innerText;
				this.obj.textfield.selected_value = this.selected_value;
				for (var ch in this.obj.list.children) {
					if (!this.obj.list.children.hasOwnProperty(ch)) continue;
					this.obj.list.children[ch].style.display = "none";
				}
				this.obj.list.style.display = "none";
			}
			this.list.appendChild(div);
		}
	}

	this.elem.appendChild(this.textfield);
	this.elem.appendChild(this.list);

	this.init_list_items();
}
