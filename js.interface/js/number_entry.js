var NumberEntry = function(wh, callback, callback_arg, desc = null, number = null) {
	this.width = wh[0];
	this.height = wh[1];

	this.callback = callback;
	this.callback_arg = callback_arg;
	this.desc = desc;

	this.elem = document.createElement("table");
	this.elem.className = "number_entry";
	this.elem.style.width = wh[0] + "px";
	this.elem.style.height = wh[1] + "px";
	this.elem.style.borderSpacing = 0;

	this.field = document.createElement("div");
	this.field.style.background = "#ffffff";
	this.field.style.borderTopLeftRadius = "15px";
	this.field.style.border = "1px groove grey";
	this.field.style.height = "100%";

	var spn = document.createElement("div");
	spn.style.fontSize = "12px";
	spn.innerHTML = ">";
	if (this.desc != null) {
		spn.innerHTML = desc;
	}

	this.field.appendChild(spn);
	this.number = document.createElement("div");
	if (number != null) this.number.innerHTML = number;
	this.field.appendChild(this.number);

	this.add_number = function(value) {
		this.number.innerHTML += "" + value;
	}

	var row_0 = document.createElement("tr");
	var td_0 = document.createElement("td");
	td_0.colSpan = 2;
	td_0.appendChild(this.field);
	row_0.appendChild(td_0);

	var td_2 = document.createElement("td");
	var del = document.createElement("button");
	del.innerHTML = "x";
	del.style.borderTopRightRadius = "15px";
	del.obj = this;
	del.onclick = function() {
		var len = this.obj.number.innerHTML.length;
		if (len > 0) {
			this.obj.number.innerHTML = this.obj.number.innerHTML.substr(0, len - 1);
		}
	}
	td_2.appendChild(del);
	row_0.appendChild(td_2);
	this.elem.appendChild(row_0);

	this.get_number_row = function(start) {
		var row = document.createElement("tr");
		for (var i = start; i < start + 3; i++) {
			var td = document.createElement("td");
			var btn = document.createElement("button");
			btn.innerHTML = i;
			btn.obj = this;
			btn.n = i;
			btn.onclick = function() {
				this.obj.add_number(this.n);
			}
			td.appendChild(btn);
			row.appendChild(td);
		}
		return row;
	}

	this.elem.appendChild(this.get_number_row(7));
	this.elem.appendChild(this.get_number_row(4));
	this.elem.appendChild(this.get_number_row(1));

	var row_4 = document.createElement("tr");
	var td_40 = document.createElement("td");
	var btn_0 = document.createElement("button");
	btn_0.style.borderBottomLeftRadius = "15px";
	btn_0.innerHTML = "0";
	btn_0.obj = this;
	btn_0.n = 0;
	btn_0.onclick = function() {
		this.obj.add_number(this.n);
	}
	td_40.appendChild(btn_0);
	row_4.appendChild(td_40);

	var td_41 = document.createElement("td");
        var btn_c = document.createElement("button");
	btn_c.innerHTML = ".";
        btn_c.obj = this;
        btn_c.n = '.';
        btn_c.onclick = function() {
                this.obj.add_number(this.n);
        }
        td_41.appendChild(btn_c);
        row_4.appendChild(td_41);

	var td_42 = document.createElement("td");
        var btn_cb = document.createElement("button");
	btn_cb.style.borderBottomRightRadius = "15px";
	btn_cb.innerHTML = "&#10003;";
        btn_cb.obj = this;
        btn_cb.onclick = function() {
                this.obj.callback(this.obj.callback_arg, parseFloat(this.obj.number.innerHTML));
        }
        td_42.appendChild(btn_cb);
        row_4.appendChild(td_42);

	this.elem.appendChild(row_4);
}
