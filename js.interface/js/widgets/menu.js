var Menu = function(db, change_dependencies) {
	this.db = db;
	this.change_dependencies = change_dependencies;

	this.active_widget = "none";
	this.widgets_names = [ "Quiz" ];

	this.widget = new Widget("Menu");

	this.elem = this.widget.elem;
	this.elem.style.display = "none";

	this.menu_elem = document.createElement("div");
	this.menu_elem.style.textAlign = "center";

	this.changed = true;

	this.changed_f = function() {
		this.changed = true;
		if (this.change_dependencies != null) {
			for (var i = 0; i < this.change_dependencies.length; i++) {
				this.change_dependencies[i].changed_f();
			}
		}
	}

	this.switch_tab = function(wg) {
		for (var w in this.widgets_names) {
			var elem = document.getElementById("wg_" + this.widgets_names[w]);
			if (this.widgets_names[w] == wg) {
				this.active_widget = this.widgets_names[w];
				elem.style.display = "block";
			} else {
				elem.style.display = "none";
			}
		}
	}

	this.update = function() {
		if (this.changed) {
			this.changed = false;

			this.widget.content.innerHTML = "";
			this.widget.content.appendChild(this.menu_elem);

			if (user.login_data != null) {		/* LOGGED IN */
				this.elem.style.display = "block";
				this.menu_elem.innerHTML = "";
				this.switch_tab("Quiz");
			} else {
				this.elem.style.display = "none";
			}
		}
	}
}
