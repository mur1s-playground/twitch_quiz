var User = function(db, change_dependencies) {
	this.db = db;
	this.change_dependencies = change_dependencies;

	this.action = "login";
	this.login_data = null;
	this.allergies = null;

	this.has_unset_allergies = false;

	this.widget = new Widget("User");

	this.elem = this.widget.elem;
	this.elem.style.display = "none";

	this.changed = true;

	this.changed_f = function() {
		this.changed = true;
		if (this.change_dependencies != null) {
			for (var i = 0; i < this.change_dependencies.length; i++) {
				this.change_dependencies[i].changed_f();
			}
		}
	}

        this.on_login_response = function() {
                var resp = JSON.parse(this.responseText);
                if (resp["status"] == true) {
                        user.login_data = resp["login_data"];
                        user.changed_f();
                } else {
			var l_elem = document.createElement("div");
                        l_elem.innerHTML = "Log in failed.";
                        l_elem.style.background = "red";

                        messagebox.message_add(l_elem, 1000, "no-class", "login_failure", true);
		}
        }

	this.login_form_create = function() {
		var login = document.createElement("div");
		login.id = this.widget.name + "_login";

		var login_email = document.createElement("input");
		login_email.id = this.widget.name + "_login_email";
		login_email.placeholder = "E-Mail";
		login.appendChild(login_email);

		var login_password = document.createElement("input");
		login_password.type = "password";
		login_password.id = this.widget.name + "_login_password";
		login_password.placeholder = "Password";
		login.appendChild(login_password);

		var login_submit = document.createElement("button");
		login_submit.obj = this;
		login_submit.innerHTML = "<img src='./img/symbol_login.svg' style='width: 30px;' />";
		login_submit.title = "Log in";
		login_submit.onclick = function() {
			var email = document.getElementById(this.obj.widget.name + "_login_email").value;
			var password = document.getElementById(this.obj.widget.name + "_login_password").value;
			var p = { "email": email, "password": password };
			this.obj.db.query_post("users/login", p, user.on_login_response);
		}
		login.appendChild(login_submit);

		var register = document.createElement("button");
		register.obj = this;
		register.innerHTML = "&#x00AE;";
		register.title = "Register";
		register.onclick = function() {
			user.action = "register";
			user.changed = true;
		}
		login.appendChild(register);

		return login;
	}

	this.on_logout_response = function() {
		var resp = JSON.parse(this.responseText);
                if (resp["status"] == true) {
                        user.login_data = null;
			user.action = "login";
                        user.changed_f();
                }
	}

	this.logged_in_create = function() {
		var logged_in = document.createElement("table");

		var logged_in_row = document.createElement("tr");
		logged_in.appendChild(logged_in_row);

		var logged_in_col_1 = document.createElement("td");
                logged_in_col_1.appendChild(document.createTextNode(this.login_data["username"]));
		logged_in_row.appendChild(logged_in_col_1);

		var logged_in_col_2 = document.createElement("td");
		logged_in_col_2.style.textAlign = "right";
		logged_in_row.appendChild(logged_in_col_2)
;
		var settings_button = document.createElement("button");
		settings_button.appendChild(document.createTextNode("\u2630"));
		settings_button.title = "Settings";
		settings_button.onclick = function() {
			user.action = "settings";
			user.changed = true;
		}
		logged_in_col_2.appendChild(settings_button);

                var logout_button = document.createElement("button");
                logout_button.innerHTML = "<img src='./img/symbol_logout.svg' style='width: 30px;' />";
		logout_button.title = "Log out";
                logout_button.onclick = function() {
                	var p = {};
	                user.db.query_post("users/login/logout", p, user.on_logout_response);
                }
                logged_in_col_2.appendChild(logout_button);

		return logged_in;
	}

	this.on_register_response = function() {
                var resp = JSON.parse(this.responseText);
                if (resp["status"] == true) {
                        user.login_data = null;
                        user.action = "login";
                        user.changed = true;
                } else {
			var reg_elem = document.createElement("div");
                        reg_elem.innerHTML = resp["error"];
                        reg_elem.style.background = "yellow";

                        messagebox.message_add(reg_elem, 1000, "no-class", "reg_failed", true);
		}
	}

	this.register_form_create = function() {
		var register_form = document.createElement("div");

		var input_username = document.createElement("input");
		input_username.id = this.widget.name + "_username";
		input_username.placeholder = "Username";
		register_form.appendChild(input_username);

		var input_email = document.createElement("input");
		input_email.id = this.widget.name + "_email";
		input_email.placeholder = "E-Mail";
		register_form.appendChild(input_email);

		var input_password = document.createElement("input");
		input_password.type = "password";
		input_password.id = this.widget.name + "_password";
		input_password.placeholder = "Password";
		register_form.appendChild(input_password);

		var input_password_2 = document.createElement("input");
		input_password_2.type = "password";
		input_password_2.id = this.widget.name + "_password2";
		input_password_2.placeholder = "Password";
		register_form.appendChild(input_password_2);

		var birthdate = document.createElement("input");
		birthdate.id = this.widget.name + "_birthdate";
		birthdate.placeholder = "Date of birth (YYYY-MM-DD)";
		register_form.appendChild(birthdate);

		var gender = document.createElement("select");
		gender.id = this.widget.name + "_gender";

		var gender_option_0 = document.createElement("option");
		gender_option_0.innerHTML = "Male";
		gender.appendChild(gender_option_0);

		var gender_option_1 = document.createElement("option");
		gender_option_1.innerHTML = "Female";
		gender.appendChild(gender_option_1);

		register_form.appendChild(gender);

		var register_submit = document.createElement("button");
		register_submit.obj = this;
		register_submit.innerHTML = "&#10003;";
		register_submit.title = "Register";
		register_submit.onclick = function() {
			var input_username = document.getElementById(this.obj.widget.name + "_username");
			var input_email = document.getElementById(this.obj.widget.name + "_email");
			var input_password = document.getElementById(this.obj.widget.name + "_password");
			var input_password2 = document.getElementById(this.obj.widget.name + "_password2");
			var input_birthdate = document.getElementById(this.obj.widget.name + "_birthdate");
			var select_gender = document.getElementById(this.obj.widget.name + "_gender");

			if (input_password.value !== input_password2.value) {
				console.log("passwords don't match");
			} else {
				var p = {
					"username": input_username.value,
					"email": input_email.value,
					"password": input_password.value,
					"birthdate": input_birthdate.value,
					"gender_id": select_gender.selectedIndex
				};
				user.db.query_post("users/login/register", p, user.on_register_response);
			}
		}
		register_form.appendChild(register_submit);

		var register_cancel = document.createElement("button");
		register_cancel.obj = this;
		register_cancel.innerHTML = "&#xd7;";
		register_cancel.title = "Cancel";
		register_cancel.onclick = function() {
			this.obj.action = "login";
			this.obj.changed = true;
		}
		register_form.appendChild(register_cancel);

		return register_form;
	}

	this.on_settings_password_response = function() {
		var resp = JSON.parse(this.responseText);
		if (resp["status"] == true) {
			user.action = "logged_in";

			var pw_elem = document.createElement("div");
                        pw_elem.innerHTML = "Password changed successfully.";
                        pw_elem.style.background = "green";

                        messagebox.message_add(pw_elem, 1000, "no-class", "password_changed", true);

			user.changed_f();
		} else {
			if (resp["error"] == "wrong password") {
				var pw_elem = document.createElement("div");
	                     	pw_elem.innerHTML = "Password change failed: Wrong password!";
        	                pw_elem.style.background = "red";

	                        messagebox.message_add(pw_elem, 1000, "no-class", "password_not_changed", true);
			}
		}
	}

	this.on_settings_response = function() {
		var resp = JSON.parse(this.responseText);
                if (resp["status"] == true) {
                        user.action = "logged_in";
			user.allergies = resp["allergies"];
                        user.changed_f();
                }
	}

	this.settings_form_create = function() {
		var settings_form = document.createElement("div");

		var settings_pw = document.createElement("div");
		settings_pw.className = "subset";
		settings_form.appendChild(settings_pw);

		var span_pw = document.createElement("span");
		span_pw.innerHTML = "Change password";
		settings_pw.appendChild(span_pw);

		var input_password_c = document.createElement("input");
		input_password_c.type = "password";
		input_password_c.id = this.widget.name + "_password_current";
		input_password_c.placeholder = "Current password";
		settings_pw.appendChild(input_password_c);

                var input_password = document.createElement("input");
                input_password.type = "password";
                input_password.id = this.widget.name + "_password";
                input_password.placeholder = "New password";
                settings_pw.appendChild(input_password);

                var input_password_2 = document.createElement("input");
                input_password_2.type = "password";
                input_password_2.id = this.widget.name + "_password2";
                input_password_2.placeholder = "New password";
                settings_pw.appendChild(input_password_2);


		var apply_btn = document.createElement("button");
                apply_btn.obj = this;
                apply_btn.innerHTML = "&#10003;";
                apply_btn.title = "Apply";
                apply_btn.onclick = function() {
			if (document.getElementById(user.widget.name + "_password").value === document.getElementById(user.widget.name + "_password2").value) {
	                        var p = { "passwords" : {
					"current_password": document.getElementById(user.widget.name + "_password_current").value,
					"password": document.getElementById(user.widget.name + "_password").value
				} };
	                        user.db.query_post("users/login/updatepassword", p, user.on_settings_password_response);
			} else {
				var pw_elem = document.createElement("div");
        	                pw_elem.innerHTML = "Password mismatch.";
                	        pw_elem.style.background = "yellow";

                        	messagebox.message_add(pw_elem, 1000, "no-class", "password_mismatch", true);
			}
                }
                settings_pw.appendChild(apply_btn);

                var cancel_btn = document.createElement("button");
                cancel_btn.obj = this;
                cancel_btn.innerHTML = "&#xd7;";
                cancel_btn.title = "Cancel";
                cancel_btn.onclick = function() {
                        this.obj.action = "logged_in";
                        this.obj.changed = true;
                }
                settings_pw.appendChild(cancel_btn);

		var settings_pa = document.createElement("div");
		settings_pa.className = "subset";
		settings_form.appendChild(settings_pa);

		return settings_form;
	}

	this.update = function() {
		if (this.changed) {
			this.elem.style.display = "block";

			this.changed = false;

			this.widget.content.innerHTML = "";

			if (this.login_data != null) {		/* LOGGED IN */
				if (this.action === "settings") {
					this.widget.content.appendChild(this.settings_form_create());
				} else {
					this.widget.content.appendChild(this.logged_in_create());
				}
			} else if (this.action === "login"){	/* LOGGED OUT */
				this.widget.content.appendChild(this.login_form_create());
			} else if (this.action === "register") { /* REGISTER */
				this.widget.content.appendChild(this.register_form_create());
			}
		}
	}
}
