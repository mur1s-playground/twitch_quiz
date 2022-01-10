var Graph = function(wh) {
	this.width = wh[0];
	this.height = wh[1];

	this.elem = document.createElement("div");
	this.elem.style.width = this.width + "px";
	this.elem.style.height = this.height + "px";
	this.elem.style.position = "relative";

	this.fields = {};
	this.value_scales = {};
	this.graph_t_min = null;
	this.graph_t_max = null;

	this.add_value_scale = function(id) {
		this.value_scales[id] = { "min": null, "max": null };
	}

	this.add_field = function(id, scale_id) {
		this.fields[id] = { "scale_id": scale_id, "min": null, "max": null, "values": [], "color": "black" };
	}

	this.add_field_value = function(id, t, value) {
		this.fields[id]["values"].push([t, value]);
		if (this.graph_t_min == null || this.graph_t_min > t) this.graph_t_min = t;
		if (this.graph_t_max == null || this.graph_t_max < t) this.graph_t_max = t;
		if (this.fields[id]["min"] == null || this.fields[id]["min"] > value) this.fields[id]["min"] = value;
		if (this.fields[id]["max"] == null || this.fields[id]["max"] < value) this.fields[id]["max"] = value;
		if (this.value_scales[this.fields[id]["scale_id"]]["min"] == null || this.value_scales[this.fields[id]["scale_id"]]["min"] > value) this.value_scales[this.fields[id]["scale_id"]]["min"] = value;
		if (this.value_scales[this.fields[id]["scale_id"]]["max"] == null || this.value_scales[this.fields[id]["scale_id"]]["max"] < value) this.value_scales[this.fields[id]["scale_id"]]["max"] = value;
	}

	this.draw_graph = function() {
		var t_scale = wh[0]/(this.graph_t_max - this.graph_t_min);
		for (var f in this.fields) {
			var v_scale = wh[1]/(this.value_scales[this.fields[f]["scale_id"]]["max"] - this.value_scales[this.fields[f]["scale_id"]]["min"]);
			for (var tv = 0; tv < this.fields[f]["values"].length - 1; tv++) {
				var vec1 = [(this.fields[f]["values"][tv][0] 	- this.graph_t_min)*t_scale, (this.fields[f]["values"][tv][1] 	- this.value_scales[this.fields[f]["scale_id"]]["min"])*v_scale];
				var vec2 = [(this.fields[f]["values"][tv+1][0]	- this.graph_t_min)*t_scale, (this.fields[f]["values"][tv+1][1] - this.value_scales[this.fields[f]["scale_id"]]["min"])*v_scale];
				this.draw_line(vec1, vec2, 4, this.fields[f]["color"]);
			}
		}
	}

	this.draw_text = function(vec, text, className) {
		var text_elem = document.createElement("span");
		text_elem.style.position = "absolute";
		text_elem.style.left = vec[0] + "px";
		text_elem.style.bottom = vec[1] + "px";
		text_elem.style.margin = 0;	//TMP
		text_elem.className = className;
		text_elem.innerHTML = text;
		this.elem.appendChild(text_elem);
	}

	this.draw_scale = function(scale_id, position, scale_margin = 0) {
		var min_val = "n/A";
		var max_val = "n/A";
		if (this.value_scales[scale_id]["min"] != null) {
			min_val = this.value_scales[scale_id]["min"];
		}
		if (this.value_scales[scale_id]["max"] != null) {
			max_val = this.value_scales[scale_id]["max"];
		}
		if (position == "left") {
			this.draw_text([scale_margin, -10]			, min_val + " " + scale_id, "graph_scale_text");
			this.draw_text([scale_margin, -10 + this.height]	, max_val + " " + scale_id, "graph_scale_text");
		} else if (position == "right") {
			this.draw_text([this.width-scale_margin, -10]                 , min_val + " " + scale_id, "graph_scale_text");
                        this.draw_text([this.width-scale_margin, -10 + this.height]   , max_val + " " + scale_id, "graph_scale_text");
		}
	}


	this.draw_line = function(vec1, vec2, stroke, color) {
		var b_len = this.length(this.diff(vec2, vec1));

		var line_elem = document.createElement("div");
		line_elem.style.position = "absolute";

		var b_len = this.length(this.diff(vec2, vec1));
		var deg = 0;
		if (vec2[0] == vec1[0]) {
			if (vec1[1] > vec2[1]) {
				deg = 90;
			} else {
				deg = -90;
			}
		} else {
			deg = -Math.asin((vec2[1] - vec1[1])/b_len) * 180.0/Math.PI;
		}
		line_elem.style.bottom = (vec1[1] + ((vec2[1] - vec1[1])/2)) + "px";
		line_elem.style.left = (vec1[0] - ((b_len - Math.abs(vec2[0] - vec1[0]))/2)) + "px";

		line_elem.style.transform = "rotate(" + deg + "deg)";
		line_elem.style.width = b_len + "px";
		line_elem.style.height = stroke;
		line_elem.style.background = color;
		this.elem.appendChild(line_elem);
	}

	this.add = function(vec1, vec2) {
		return [ vec1[0] + vec2[0], vec1[1] + vec2[1] ];
	}

	this.diff = function(vec1, vec2) {
		return [ vec1[0] - vec2[0], vec1[1] - vec2[1] ];
	}

	this.length = function(vec) {
		return Math.sqrt(vec[0] * vec[0] + vec[1] * vec[1]);
	}
}
