var MultiSlider = function(id, thumb_count, values, range, sum_elem, lock_sum, colors = null, titles = null, imgs = null) {
	this.thumb_count = thumb_count;

	this.elem = document.createElement("div");

	this.elem_bar = document.createElement("div");
	this.elem_bar.obj = this;
	this.elem_bar.id = id;
	this.elem_bar.style.position = "relative";
	this.elem_bar.style.width = "300px";
	this.elem_bar.style.height = "6px";
	this.elem_bar.style.backgroundColor = "#000000";
	this.elem_bar.style.marginTop = "5px";
	this.elem_bar.active_thumb = null;
	this.elem_bar.mouse_x = 0;
	this.elem_bar.range = range;
	this.elem_bar.sum_elem = sum_elem;
	this.elem_bar.lock_sum = lock_sum;
	this.elem_bar.thumb_count = thumb_count;
	this.elem_bar.onchange_callback = null;
	this.elem_bar.onmousemove = function(e) {
		var x = e.pageX - this.offsetLeft;
		this.mouse_x = x;
	}

	this.set_lock_sum = function(lock_sum) {
		this.elem_bar.lock_sum = lock_sum;
	}

	this.elem_bar.mouse_is_down = -1;

	this.get_values = function() {
		var vals = [];
		for (var c = 0; c < this.elem_bar.children.length; c += 3) {
			vals.push(parseFloat(this.elem_bar.children[c].innerHTML));
                }
		return vals;
	}

	this.elem_bar.get_values = function() {
		return this.obj.get_values();
	}

	this.get_values_for_config = function() {
		var vals = [];
		for (var c = 2; c < this.elem_bar.children.length; c+= 3) {
			vals.push(parseFloat(this.elem_bar.children[c].value));
		}
		return vals;
	}

	this.elem_bar.adjust_thumbs_to_sum = function() {
		var current_sum = 0;
		for (var c = 0; c < this.children.length; c += 3) {
			current_sum = Math.round((parseFloat(current_sum) + parseFloat(this.children[c].innerHTML)) * 100) / 100;
		}

		var target_sum = parseFloat(this.sum_elem.value);

		var diff = Math.round((this.range[0] + (target_sum - current_sum)/this.range[1]) * 100) / 100;
		var overlap = 0;

		for (var c = 2; c < this.children.length; c += 3) {
			var adjust = diff / this.children.length + overlap;
			var thumb = this.children[c];
			if (thumb == this.active_thumb) continue;
			if (adjust > 0) {
				if (thumb.value + adjust <= 1) {
					this.set_thumb_position_by_value(thumb, thumb.value + adjust, false);
					current_sum += Math.round((thumb.parent.range[0] + adjust * (thumb.parent.range[1] - thumb.parent.range[0])) * 100) / 100;
				} else {
					var tmp = thumb.value;
					this.set_thumb_position_by_value(thumb, 1, false);
					current_sum += Math.round((thumb.parent.range[0] + tmp * (thumb.parent.range[1] - thumb.parent.range[0])) * 100) / 100;
				}
			}
			if (adjust < 0) {
				if (thumb.value + adjust >= 0) {
					this.set_thumb_position_by_value(thumb, thumb.value + adjust, false);
					current_sum -= Math.round((thumb.parent.range[0] + adjust * (thumb.parent.range[1] - thumb.parent.range[0])) * 100) / 100;
				} else {
					var tmp = thumb.value;
					this.set_thumb_position_by_value(thumb, 0, false);
					current_sum -= Math.round((thumb.parent.range[0] + tmp * (thumb.parent.range[1] - thumb.parent.range[0])) * 100) / 100;
				}
			}
		}
	}

	this.elem_bar.set_thumb_position_by_value = function(thumb, value, calculate_sum = true) {
		thumb.value = value;
		if (thumb.value < 0) thumb.value = 0;
		if (thumb.value > 1) thumb.value = 1;
		thumb.style.left = thumb.value * parseInt(thumb.parent.style.width);
		thumb.value_elem.innerHTML = Math.round((thumb.parent.range[0] + thumb.value * (thumb.parent.range[1] - thumb.parent.range[0])) * 100) / 100;
		thumb.value_elem.style.left = parseInt(thumb.style.left) - parseInt(thumb.style.width)/2;
		thumb.top_elem.style.left = parseInt(thumb.style.left) - parseInt(thumb.style.width)/2;
		if (calculate_sum) {
			thumb.parent.sum_elem.value = 0;
			for (var c = 0; c < thumb.parent.children.length; c += 3) {
				thumb.parent.sum_elem.value = Math.round((parseFloat(thumb.parent.sum_elem.value) + parseFloat(thumb.parent.children[c].innerHTML)) * 100) / 100;
			}
		}
	}

	this.elem_bar.onmousedown = function(e) {
		if (e.target.parent.thumb_count > 1) {
		if (this.mouse_is_down == -1) {
			this.mouse_is_down = setInterval(
				function() {
					if (e.target.parent != null && e.target.parent.active_thumb != null) {
						var value = (e.target.parent.mouse_x-parseInt(e.target.parent.active_thumb.style.width)/2) / parseInt(e.target.parent.style.width);

						e.target.parent.set_thumb_position_by_value(e.target.parent.active_thumb, value, !e.target.parent.lock_sum);

						e.target.parent.adjust_thumbs_to_sum(e.target.parent.active_thumb);

						if (e.target.parent.onchange_callback != null) {
							e.target.parent.onchange_callback();
						}
					}
				},
				10
			);
		}
		}
	}

	this.elem_bar.onmouseup = function() {
		if (this.mouse_is_down != -1) {
			clearInterval(this.mouse_is_down);
			this.mouse_is_down = -1;
		}
	}

	this.elem_bar.onmouseout = function() {
                if (this.mouse_is_down != -1) {
                        clearInterval(this.mouse_is_down);
			this.mouse_is_down = -1;
                }
        }


	this.set_thumb_count = function(thumb_count, values, colors = null, titles = null, imgs = null) {
		this.elem_bar.thumb_count = thumb_count;
		this.thumb_count = thumb_count;
		this.elem_bar.innerHTML = "";

		if (colors == null) {
			colors = [
				"#343434",
				"#9a7c56",
				"#ab8243",
				"#123456",
				"#654321",
				"#acacca",
				"#abcbca"
			];
		}

		for (var t = 0; t < thumb_count; t++) {
			var thumb = document.createElement("div");
			if (titles != null) thumb.title = titles[t];
			thumb.is_thumb = true;
			thumb.parent = this.elem_bar;
			thumb.style.height = "25px";
			thumb.style.width = "20px";
			thumb.style.backgroundColor = colors[t];
			thumb.style.borderRadius = "5px";

			thumb.value = values[t]
			thumb.style.position = "absolute";
			thumb.style.top = "-9px";
			thumb.style.left = parseFloat(values[t]) * parseInt(this.elem_bar.style.width);
			thumb.style.zIndex = 1;
			thumb.onmouseover = function() {
				this.style.border = "1px solid black";
				this.parent.active_thumb = this;
				this.style.zIndex = 3;
				this.value_elem.style.zIndex = 2;
				this.value_elem.style.backgroundColor = '#eeeeee';
				if (this.top_elem.children.length > 0) {
					this.top_elem.style.zIndex = 2;
					this.top_elem.style.backgroundColor = '#eeeeee';
				}
			}

			thumb.onmouseout = function() {
				this.style.border = 0;
				this.parent.active_thumb = null;
				this.style.zIndex = 1;
				this.value_elem.style.zIndex = 0;
				this.value_elem.style.backgroundColor = '#ffffff';
				this.top_elem.style.zIndex = 0;
				this.top_elem.style.backgroundColor = '#ffffff';
			}

			var value_elem = document.createElement("span");
			value_elem.style.position = "absolute";
			value_elem.style.top = "12px";
			value_elem.style.padding = "5px";
			value_elem.style.borderRadius = "5px";
			value_elem.style.left = parseFloat(thumb.value) * parseInt(this.elem_bar.style.width) - (parseInt(thumb.style.width)/2);
			value_elem.innerHTML = Math.round((this.elem_bar.range[0] + values[t] * (this.elem_bar.range[1] - this.elem_bar.range[0])) * 100) / 100;
			this.elem_bar.appendChild(value_elem);
			thumb.value_elem = value_elem;

			var top_elem = document.createElement("span");
			top_elem.style.position = "absolute";
			top_elem.style.top = "-45px";
			top_elem.style.padding = "5px";
			top_elem.style.borderRadius = "5px";
			top_elem.style.left = parseFloat(thumb.value) * parseInt(this.elem_bar.style.width) - (parseInt(thumb.style.width)/2);
			if (imgs != null) {
				var img = document.createElement("img");
                                img.src = imgs[t];
				img.style.width = "20px";
				top_elem.appendChild(img);
			}
			this.elem_bar.appendChild(top_elem);
			thumb.top_elem = top_elem;

			this.elem_bar.appendChild(thumb);
		}
	}

	this.elem.appendChild(this.elem_bar);

	this.set_thumb_count(thumb_count, values, colors, titles, imgs);
}
