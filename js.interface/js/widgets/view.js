var View = function(db, change_dependencies) {
	this.db = db;
	this.change_dependencies = change_dependencies;

	this.widget = new Widget("View");

	this.elem = this.widget.elem;
	this.elem.style.display = "block";
	
	this.status = -1;

	this.q_elem = document.createElement("div");
	this.q_elem.style.textAlign = "center";
	
	this.question_div = document.createElement("div");
	this.question_div.id = this.widget.name + "_question_div";
	this.question_div.style.display = "none";
	this.q_elem.appendChild(this.question_div);
	
	this.question_div_question = document.createElement("div");
	this.question_div_question.id = this.widget.name + "_question";
	this.question_div.appendChild(this.question_div_question);
	
	this.question_div_answer_a = document.createElement("div");
	this.question_div_answer_a.id = this.widget.name + "_answer_a";
	this.question_div.appendChild(this.question_div_answer_a);
	
	this.question_div_answer_b = document.createElement("div");
	this.question_div_answer_b.id = this.widget.name + "_answer_b";
	this.question_div.appendChild(this.question_div_answer_b);
	
	this.question_div_answer_c = document.createElement("div");
	this.question_div_answer_c.id = this.widget.name + "_answer_c";
	this.question_div.appendChild(this.question_div_answer_c);
	
	this.question_div_answer_d = document.createElement("div");
	this.question_div_answer_d.id = this.widget.name + "_answer_d";
	this.question_div.appendChild(this.question_div_answer_d);
	
	this.distribution_div = document.createElement("div");
	this.distribution_div.id = this.widget.name + "_distribution";
	this.q_elem.appendChild(this.distribution_div);
	
	this.distribution_div_percentage_bar_a = document.createElement("div");
	this.distribution_div_percentage_bar_a.id = this.widget.name + "_percentage_bar_a";
	this.distribution_div.appendChild(this.distribution_div_percentage_bar_a);

	this.distribution_div_percentage_a = document.createElement("div");
	this.distribution_div_percentage_a.id = this.widget.name + "_percentage_a";
	this.distribution_div.appendChild(this.distribution_div_percentage_a);

	this.distribution_div_percentage_bar_b = document.createElement("div");
	this.distribution_div_percentage_bar_b.id = this.widget.name + "_percentage_bar_b";
	this.distribution_div.appendChild(this.distribution_div_percentage_bar_b);

	this.distribution_div_percentage_b = document.createElement("div");
	this.distribution_div_percentage_b.id = this.widget.name + "_percentage_b";
	this.distribution_div.appendChild(this.distribution_div_percentage_b);

	this.distribution_div_percentage_bar_c = document.createElement("div");
	this.distribution_div_percentage_bar_c.id = this.widget.name + "_percentage_bar_c";
	this.distribution_div.appendChild(this.distribution_div_percentage_bar_c);

	this.distribution_div_percentage_c = document.createElement("div");
	this.distribution_div_percentage_c.id = this.widget.name + "_percentage_c";
	this.distribution_div.appendChild(this.distribution_div_percentage_c);
	
	this.distribution_div_percentage_bar_d = document.createElement("div");
	this.distribution_div_percentage_bar_d.id = this.widget.name + "_percentage_bar_d";
	this.distribution_div.appendChild(this.distribution_div_percentage_bar_d);

	this.distribution_div_percentage_d = document.createElement("div");
	this.distribution_div_percentage_d.id = this.widget.name + "_percentage_d";
	this.distribution_div.appendChild(this.distribution_div_percentage_d);
	
	this.result_div = document.createElement("div");
	this.result_div.id = this.widget.name + "_result";
	this.q_elem.appendChild(this.result_div);
	
	this.widget.content.appendChild(this.q_elem);

	this.changed = true;

	this.changed_f = function() {
		this.changed = true;
		if (this.change_dependencies != null) {
			for (var i = 0; i < this.change_dependencies.length; i++) {
				this.change_dependencies[i].changed_f();
			}
		}
	}
	
	this.on_question_response = function() {
		var resp = JSON.parse(this.responseText);
		if (resp["status"] == true) {
			view.question_div.style.display = "block";
			view.question_div_question.innerHTML = resp["question"]["Question"];
			view.question_div_answer_a.innerHTML = resp["question"]["AnswerA"];
			view.question_div_answer_b.innerHTML = resp["question"]["AnswerB"];
			view.question_div_answer_c.innerHTML = resp["question"]["AnswerC"];
			view.question_div_answer_d.innerHTML = resp["question"]["AnswerD"];
		}
	}
	
	this.question_result = false;
	this.quiz_result = false;
	
	this.on_view_response = function() {
		var resp = JSON.parse(this.responseText);
		if (resp["status"] == true) {
			var state = resp["state"]["State"];
			if (view.state != state) {
				view.state = state;
				if (state == 0) {
					view.result_div.style.display = "none";
					view.question_result = false;
					view.quiz_result = false;
					var p = {
						"question_id": resp["state"]["Param"]
					}
					view.db.query_post("quiz/index/view", p, view.on_question_response);
				} else if (state == 1) {
					view.question_div.style.display = "none";
					view.distribution_div.style.display = "none";
					view.result_div.style.display = "none";
				} else if (state == 2) {
					view.question_div.style.display = "none";
					view.distribution_div.style.display = "none";
					view.result_div.style.display = "none";
				} else if (state == -1) {
					view.question_div.style.display = "none";
					view.distribution_div.style.display = "none";
					view.result_div.style.display = "none";
				}
			}
			if (state == 0) {
				if (resp["distribution"]) {
					var d_data = resp["distribution"]["Data"].split(";");
					var participants = parseFloat(d_data[0]);
					var answers_a = parseFloat(d_data[1]);
					var answers_b = parseFloat(d_data[2]);
					var answers_c = parseFloat(d_data[3]);
					var answers_d = parseFloat(d_data[4]);
					if (participants > 0) {
						view.distribution_div.style.display = "block";
						root = document.documentElement;
						
						root.style.setProperty('--percentage_a', answers_a / participants);
						view.distribution_div_percentage_a.innerHTML = Math.round(answers_a / participants * 100) + "%";
						root.style.setProperty('--percentage_b', answers_b / participants);
						view.distribution_div_percentage_b.innerHTML = Math.round(answers_b / participants * 100) + "%";
						root.style.setProperty('--percentage_c', answers_c / participants);
						view.distribution_div_percentage_c.innerHTML = Math.round(answers_c / participants * 100) + "%";
						root.style.setProperty('--percentage_d', answers_d / participants);
						view.distribution_div_percentage_d.innerHTML = Math.round(answers_d / participants * 100) + "%";
					}
				}
			} else if (state == 1) {
				if (resp["question_result"]) {
					var result = resp["question_result"]["Result"].split("#");
					view.result_div.style.display = "block";
					view.result_div.innerHTML = "";
					for (var r in result) {
						if (result[r].length > 0) {
							var r_arr = result[r].split(";");
							var name = r_arr[1].substring(1).split("!")[0];
							view.result_div.innerHTML += name + " -> " + r_arr[3] + "s<br>";
						}
					}
					view.question_result = true;
				}
			} else if (state == 2) {
				if (resp["quiz_result"]) {
					var result = resp["quiz_result"]["Result"].split("#");
					view.result_div.style.display = "block";
					view.result_div.innerHTML = "";
					for (var r in result) {
						if (result[r].length > 0) {
							var r_arr = result[r].split(";");
							var name = r_arr[1].substring(1).split("!")[0];
							view.result_div.innerHTML += name + " -> " + r_arr[2] +" -> " + r_arr[3] + "s<br>";
						}
					}
					view.quiz_result = true;
				}
			}
		}
	}
	
	this.update = function() {
		if (this.changed) {
			//this.changed = false;

			var p = {
				"channel": param_v,
				"question_result": view.question_result,
				"quiz_result": view.quiz_result
			}
			view.db.query_post("cmd/state/view", p, view.on_view_response);
		}
	}
}
