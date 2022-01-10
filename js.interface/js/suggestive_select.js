var SuggestiveSelect = function(id, values, field, suggestions) {
	this.elem = document.createElement("div");
	this.elem.id = id;
	this.elem.style.position = "relative";

	this.values = values;
	this.field = field;

	this.suggestions = suggestions;

	this.get_selection = function() {
		if (this.select.selectedIndex == 0) {
			var ac_elem = document.getElementById(this.elem.id + "_autocomplete_input");
			return ac_elem.selected_value;
		} else {
			return this.select.options[this.select.selectedIndex].value;
		}
	}

	this.select = document.createElement("select");
	this.select.obj = this;
	this.select.id = this.elem.id + "_select";
	this.select.onchange = function() {
		if (this.selectedIndex == 0) {
			this.style.display = "none";
			if (this.obj.autocomplete == null) {
				this.obj.autocomplete = new AutocompleteTextfield(this.obj.elem.id + "_autocomplete", this.obj.values, this.obj.field);
				this.obj.elem.appendChild(this.obj.autocomplete.elem);
				this.obj.elem.appendChild(this.obj.cancel_autocomplete);
			} else {
				this.obj.autocomplete.textfield.style.display = "inline";
				this.obj.cancel_autocomplete.style.display = "inline";
			}
		}
	}

	this.init_suggestions = function() {
		var values = [];
                for (var c in this.suggestions) {
                        values.push(this.suggestions[c]);
                }
                values.sort(function(a, b) {
                        var _field = field;
                        var af = a[_field].toUpperCase();
                        var bf = b[_field].toUpperCase();
                        if (a[_field] < b[_field]) return -1;
                        if (a[_field] > b[_field]) return 1;
                        return 0;
                });
		var option = document.createElement("option");
		option.id = this.elem.id + "_option_other";
		option.innerHTML = "other";
		this.select.appendChild(option);

		var first = true;
                for (var c in values) {
			var option = document.createElement("option");
			option.id = this.elem.id + "_option_" + values[c]["Id"];
			option.value = values[c]["Id"];
			option.innerHTML = values[c][field];
			if (first) {
				option.selected = true;
				first = false;
				this.selection = values[c]["Id"];
			}
                        this.select.appendChild(option);
                }

		if (first) {
			this.select.style.display = "none";
			this.autocomplete = new AutocompleteTextfield(this.elem.id + "_autocomplete", this.values, this.field);
                        this.elem.appendChild(this.autocomplete.elem);
		}
	}

	this.elem.appendChild(this.select);

	this.autocomplete = null;

	this.cancel_autocomplete = document.createElement("button");
	this.cancel_autocomplete.obj = this;
	this.cancel_autocomplete.id = this.elem.id + "_autocomplete_cancel";
	this.cancel_autocomplete.innerHTML = "x";
	this.cancel_autocomplete.onclick = function() {
		var ac_elem = document.getElementById(this.obj.elem.id + "_autocomplete_input");
		ac_elem.style.display = "none";
		this.style.display = "none";
		this.obj.select.style.display = "inline";
		this.obj.select.selectedIndex = 1;
	}

	this.init_suggestions();
}
