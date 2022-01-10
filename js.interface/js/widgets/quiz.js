var Quiz = function(db, change_dependencies) {
	this.db = db;
	this.change_dependencies = change_dependencies;

	this.data_table = new DataTable(this, "quiz",
                                {       "QuestionNr": { "title": "QuestionNr", "header": { "type": "text", "text": "QuestionNr", "text_class": "datatable_header" } },
					"Question": { "title": "Question", "header": { "type": "text", "text": "Question", "text_class": "datatable_header" } },
					"AnswerA": { "title": "AnswerA", "header": { "type": "text", "text": "AnswerA", "text_class": "datatable_header" } },
					"AnswerB": { "title": "AnswerB", "header": { "type": "text", "text": "AnswerB", "text_class": "datatable_header" } },
					"AnswerC": { "title": "AnswerC", "header": { "type": "text", "text": "AnswerC", "text_class": "datatable_header" } },
					"AnswerD": { "title": "AnswerD", "header": { "type": "text", "text": "AnswerD", "text_class": "datatable_header" } },
					"CorrectId": { "title": "CorrectId", "header": { "type": "text", "text": "CorrectId", "text_class": "datatable_header" } }
                                },
                                {
					"QuestionNr": { "placeholder": "QuestionNr" },
                                       "Question": { "placeholder": "Question" },
                                       "AnswerA": { "placeholder": "AnswerA" },
					"AnswerB": { "placeholder": "AnswerB" },
					"AnswerC": { "placeholder": "AnswerC" },
					"AnswerD": { "placeholder": "AnswerD" },
                                       "CorrectId": { "placeholder": "CorrectId" },
                                        "add_button": { "onclick":  function() {
										var p = {
							                                "quiz_item" : this.obj.data_table.get_inserted_values()
						                                };
							                        this.obj.db.query_post("quiz/index/insert", p, this.obj.on_add_response);
									}
                                                       },
                                        "total_button": { "onclick": function() {
										var p = {
							                                "cmd_item" : { "cmd": "total", "param": "" }
						                                };
										var r = confirm("Finish quiz and get result ?");
						                                if (r == 1) {
								                        this.obj.db.query_post("cmd/poll/insert", p, this.obj.on_total_response);
								                 }
									}
							},
					"clear_button": { "onclick": function() {
										var p = {
							                                "cmd_item" : { "cmd": "clear", "param": "" }
						                                };
						                                var r = confirm("Reset quiz process ?");
						                                if (r == 1) {
								                        this.obj.db.query_post("cmd/poll/insert", p, this.obj.on_clear_response);
								                 }
									}
							}
                                },
                                {
                                        "Delete": { "title": "Delete", "type": "text", "text": "&#128465;", "onclick":
									function() {
							                        var p = {
							                                "quiz_question_id": this.obj["Id"]
						        	                };
						                	        var r = confirm("Delete question" + this.obj["Question"] + "?");
						                        	if (r == 1) {
						                                	quiz.db.query_post("quiz/index/delete", p, quiz.on_remove_response);
							                        }
							                }
                                                },
                                        "Ask": { "title": "Ask", "type": "text", "text": "Ask", "onclick":
                                        				function() {
                                        					var p = {
                                        						"cmd_item": { "cmd": "ask", "param": "" + this.obj["Id"] }
                                        					};
                                        					var r = confirm("Ask question " + this.obj["Question"] + "?");
                                        					if (r == 1) {
                                        						quiz.db.query_post("cmd/poll/insert", p, quiz.on_ask_response);
                                        					}
                                        				}
                                        	},
                                        "End": { "title": "End", "type": "text", "text": "End", "onclick":
                                        				function() {
                                        					var p = {
                                        						"cmd_item": { "cmd": "end", "param": "" + this.obj["Id"] + "_" + this.obj["CorrectId"] }
                                        					};
                                        					var r = confirm("Ask question " + this.obj["Question"] + "?");
                                        					if (r == 1) {
                                        						quiz.db.query_post("cmd/poll/insert", p, quiz.on_end_response);
                                        					}
                                        				}
                                        	}
                                }
                        );
	this.quiz_data = null;

	this.widget = new Widget("Quiz");

	this.elem = this.widget.elem;
	this.elem.style.display = "none";

	this.q_elem = document.createElement("div");
	this.q_elem.style.textAlign = "center";

	this.changed = true;

	this.changed_f = function() {
		this.changed = true;
		if (this.change_dependencies != null) {
			for (var i = 0; i < this.change_dependencies.length; i++) {
				this.change_dependencies[i].changed_f();
			}
		}
	}
	
	this.on_ask_response = function() {
		var resp = JSON.parse(this.responseText);
		if (resp["status"] == true) {
			var ask_line = document.getElementById("Quiz_quiz_" + resp["cmd_item"]["Param"]);
			ask_line.children[7].children[1].disabled = true;
		}
	}
	
	this.on_end_response = function() {
		var resp = JSON.parse(this.responseText);
		if (resp["status"] == true) {
			var id_param = (resp["cmd_item"]["Param"].split("_"))[0];
			var ask_line = document.getElementById("Quiz_quiz_" + id_param);
			ask_line.children[7].children[1].disabled = false;
			ask_line.children[7].children[2].disabled = true;
		}
	}
	
	this.on_add_response = function() {
		quiz.changed = true;
	}

	this.on_remove_response = function() {
		quiz.changed = true;
	}
	
	this.on_quiz_response = function() {
		var resp = JSON.parse(this.responseText);
		if (resp["status"] == true) {
			quiz.quiz_data = resp["quiz"];
			quiz.q_elem.innerHTML = "";

			quiz.q_elem.appendChild(quiz.data_table.get_header_row());

			var idx = [];
			for (var question in quiz.quiz_data) {
				if (quiz.quiz_data.hasOwnProperty(question)) {
					idx.push(parseInt(question));
				}
			}
                	quiz.q_elem.appendChild(quiz.data_table.get_insert_row(null, null));
			if (idx.length > 0) {
				idx.sort(function(a,b) {return b-a});
				for (var i = 0; i < idx.length; i++) {
	                               quiz.q_elem.appendChild(quiz.data_table.get_data_row(quiz.quiz_data[idx[i]], null));
				}
			}
			quiz.changed_f();
			quiz.changed = false;
		}
	}

	this.update = function() {
		if (this.changed) {
			this.changed = false;

			this.widget.content.innerHTML = "";
			this.widget.content.appendChild(this.q_elem);

			if (user.login_data != null) {		/* LOGGED IN */
				this.elem.style.display = "block";
				this.q_elem.innerHTML = "";
			
				var p = {};
				quiz.db.query_post("quiz/index", p, quiz.on_quiz_response);
		
			} else {
				this.elem.style.display = "none";
			}
		}
	}
}
