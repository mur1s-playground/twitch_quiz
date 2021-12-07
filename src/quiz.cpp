#include "quiz.h"

#include "main.h"
#include "util.h"
#include "logger.h"

#include <SDL.h>
#include <SDL_ttf.h>

#include <sstream>
#include <map>

#include "mutex.h"


SDL_Window *window_quiz_question;
SDL_Renderer *renderer_quiz_question;
SDL_Surface *quiz_question_overlay;
SDL_Texture *quiz_question_texture;
map<string, struct quiz_cfg>	quiz_question_overlay_cfg;

SDL_Window* window_quiz_question_distribution;
SDL_Renderer *renderer_quiz_question_distribution;
SDL_Surface* quiz_question_distribution_overlay;
SDL_Texture* quiz_question_distribution_texture;
map<string, struct quiz_cfg>	quiz_question_distribution_overlay_cfg;

SDL_Window* window_quiz_question_result;
SDL_Renderer* renderer_quiz_question_result;
SDL_Surface* quiz_question_result_overlay;
SDL_Texture* quiz_question_result_texture;
map<string, struct quiz_cfg>	quiz_question_result_overlay_cfg;

SDL_Window* window_quiz_result;
SDL_Renderer* renderer_quiz_result;
SDL_Surface* quiz_result_overlay;
SDL_Texture* quiz_result_texture;
map<string, struct quiz_cfg>	quiz_result_overlay_cfg;

map<string, struct quiz_participant_data *>	quiz_participants;
mutex quiz_participants_lock;

map<unsigned int, vector<struct question_result>*>	question_results;

vector<struct question_result>* quiz_result = nullptr;

unsigned int participants = 0;
unsigned int a_count[4] = { 0, 0, 0, 0 };

void quiz_question_result_calculate(struct quiz* q, unsigned int question_nr);
void quiz_result_calculate();

void quiz_render(struct quiz* q) {
	mutex_wait_for(&q->state_lock);
	if (q->quiz_state == 0) {
		quiz_question_distribution_render(q->question_nr);
	}
	mutex_release(&q->state_lock);
}

void quiz_state_next(struct quiz* q) {
	mutex_wait_for(&q->state_lock);
	if (q->quiz_state == -1) {
		q->quiz_state = 0;
		q->question_nr = 0;
		quiz_question_window_render(q, q->question_nr);

		SYSTEMTIME time;
		GetSystemTime(&time);
		q->questions[q->question_nr].time_ms = time.wDay * 24 * 60 * 60 * 1000 + time.wHour * 60 * 60 * 1000 + time.wMinute * 60 * 1000 + time.wSecond * 1000 + time.wMilliseconds;

		memset(&a_count, 0, 4 * sizeof(unsigned int));
		quiz_question_distribution_init();
	} else if (q->quiz_state == 0) {
		q->quiz_state = 1;
		quiz_question_window_destroy();
		quiz_question_distribution_destroy();
		quiz_question_result_calculate(q, q->question_nr);
		quiz_question_result_init();
		quiz_question_result_render(q->question_nr);
	} else if (q->quiz_state == 1) {
		if (q->question_nr + 1 < q->questions.size()) {
			q->question_nr++;
			q->quiz_state = 0;
			quiz_question_result_destroy();
			quiz_question_window_init();
			quiz_question_window_render(q, q->question_nr);

			SYSTEMTIME time;
			GetSystemTime(&time);
			q->questions[q->question_nr].time_ms = time.wDay * 24 * 60 * 60 * 1000 + time.wHour * 60 * 60 * 1000 + time.wMinute * 60 * 1000 + time.wSecond * 1000 + time.wMilliseconds;

			memset(&a_count, 0, 4 * sizeof(unsigned int));
			quiz_question_distribution_init();
		} else {
			q->quiz_state = 2;
			quiz_question_result_destroy();
			quiz_result_calculate();
			quiz_result_init();
			quiz_result_render();
		}
	} else if (q->quiz_state == 2) {
		exit(0);
	}
	mutex_release(&q->state_lock);
}

void quiz_state_previous(struct quiz* q) {
	if (q->quiz_state == -1) {

	}
}

void quiz_message_parse(struct quiz* q, struct irc_message* message) {
	struct quiz_participant_data* qp_d = nullptr;

	mutex_wait_for(&q->state_lock);
	unsigned int question_nr = 0;
	if (q->quiz_state != 0) {
		return;
	}
	question_nr = q->question_nr;
	mutex_release(&q->state_lock);

	string name(message->name);
	string msg(message->msg);
	if (msg.length() == 3) {
		int answer_id = -1;
		if (strstr(msg.c_str(), ":a") != nullptr || strstr(msg.c_str(), ":A") != nullptr) {
			answer_id = 0;
		} else if (strstr(msg.c_str(), ":b") != nullptr || strstr(msg.c_str(), ":B") != nullptr) {
			answer_id = 1;
		} else if (strstr(msg.c_str(), ":c") != nullptr || strstr(msg.c_str(), ":C") != nullptr) {
			answer_id = 2;
		} else if (strstr(msg.c_str(), ":d") != nullptr || strstr(msg.c_str(), ":D") != nullptr) {
			answer_id = 3;
		}

		if (answer_id > -1) {
			mutex_wait_for(&quiz_participants_lock);
			map<string, struct quiz_participant_data*>::iterator qp_it = quiz_participants.find(name);
			if (qp_it == quiz_participants.end()) {
				participants++;
				qp_d = new struct quiz_participant_data;
				mutex_init(&qp_d->lock);
				qp_d->correct_answers = 0;
				qp_d->time_s = 0;
				quiz_participants.insert(pair<string, struct quiz_participant_data*>(name, qp_d));
			} else {
				qp_d = qp_it->second;
			}

			struct quiz_answer qa;
			qa.answer_id = answer_id;

			SYSTEMTIME time;
			GetSystemTime(&time);
			qa.time_ms = time.wDay * 24 * 60 * 60 * 1000 + time.wHour * 60 * 60 * 1000 + time.wMinute * 60 * 1000 + time.wSecond * 1000 + time.wMilliseconds;

			mutex_wait_for(&qp_d->lock);
			map<unsigned int, struct quiz_answer>::iterator answer_it = qp_d->answers.find(question_nr);
			if (answer_it == qp_d->answers.end()) {
				qp_d->answers.insert(pair<unsigned int, struct quiz_answer>(question_nr, qa));
			} else {
				a_count[answer_it->second.answer_id]--;
				answer_it->second.answer_id = answer_id;
				answer_it->second.time_ms = qa.time_ms;
			}
			a_count[answer_id]++;

			mutex_release(&qp_d->lock);

			mutex_release(&quiz_participants_lock);
		}
	}
}

void quiz_init(struct quiz* q, const char* filename) {
	mutex_init(&quiz_participants_lock);
	mutex_init(&q->state_lock);

	vector<string> quiz_file = util_file_read(filename);
	for (int l = 0; l < quiz_file.size(); l++) {
		if (quiz_file[l].length() > 0) {
			vector<string> line_splt = util_split(quiz_file[l], ";");
			if (line_splt.size() == 6) {
				struct quiz_question q_;
				util_chararray_from_const(line_splt[0].c_str(), &q_.question);
				logger_write(&main_logger, LOG_LEVEL_VERBOSE, "QUIZ question", q_.question);
				util_chararray_from_const(line_splt[1].c_str(), &q_.answer_a);
				logger_write(&main_logger, LOG_LEVEL_VERBOSE, "QUIZ answer a", q_.answer_a);
				util_chararray_from_const(line_splt[2].c_str(), &q_.answer_b);
				logger_write(&main_logger, LOG_LEVEL_VERBOSE, "QUIZ answer b", q_.answer_b);
				util_chararray_from_const(line_splt[3].c_str(), &q_.answer_c);
				logger_write(&main_logger, LOG_LEVEL_VERBOSE, "QUIZ answer c", q_.answer_c);
				util_chararray_from_const(line_splt[4].c_str(), &q_.answer_d);
				logger_write(&main_logger, LOG_LEVEL_VERBOSE, "QUIZ answer d", q_.answer_d);

				const char* answer = line_splt[5].c_str();
				if (strstr(answer, "A") != nullptr) {
					q_.correct_id = 0;
				} else if (strstr(answer, "B") != nullptr) {
					q_.correct_id = 1;
				} else if (strstr(answer, "C") != nullptr) {
					q_.correct_id = 2;
				} else if (strstr(answer, "D") != nullptr) {
					q_.correct_id = 3;
				}

				q->questions.push_back(q_);
			} else {
				printf("Error parsing quiz.csv on line %i, found %i columns, 6 needed\n", l, (int)line_splt.size());
				exit(1);
			}
		}
	}

	q->question_nr = -1;
	q->quiz_state = -1;
}

void quiz_question_window_init() {
	vector<string> qq_cfg = util_file_read("./custom/quiz_question_overlay.cfg");
	for (int l = 0; l < qq_cfg.size(); l++) {
		if (qq_cfg[l].length() > 0) {
			vector<string> qq_cfg_splt = util_split(qq_cfg[l], ";");
			if (qq_cfg_splt.size() == 5) {
				string identifier(qq_cfg_splt[0].c_str());
				struct quiz_cfg qc;
				qc.pos_x = stoi(qq_cfg_splt[1].c_str());
				qc.pos_y = stoi(qq_cfg_splt[2].c_str());
				qc.width = stoi(qq_cfg_splt[3].c_str());
				qc.height = stoi(qq_cfg_splt[4].c_str());
				quiz_question_overlay_cfg.insert(pair<string, struct quiz_cfg>(identifier, qc));
			} else {
				printf("Error parsing quiz_question_overlay.cfg on line %i, found %i columns, 5 needed\n", l, (int)qq_cfg_splt.size());
				exit(1);
			}
		}
	}

	map<string, struct quiz_cfg>::iterator q_cfg_it = quiz_question_overlay_cfg.find(string("size"));
	unsigned int width = 1920;
	unsigned int height = 540;
	if (q_cfg_it != quiz_question_overlay_cfg.end()) {
		struct quiz_cfg qc = q_cfg_it->second;
		width = qc.pos_x;
		height = qc.pos_y;
	}

	window_quiz_question = SDL_CreateWindow("Twitch Quiz - Question & Answers", SDL_WINDOWPOS_UNDEFINED, SDL_WINDOWPOS_UNDEFINED, width, height, 0);
	renderer_quiz_question = SDL_CreateRenderer(window_quiz_question, -1, 0);
	quiz_question_overlay = SDL_LoadBMP("./custom/quiz_question_overlay.bmp");
}

void quiz_question_window_destroy() {
	SDL_DestroyWindow(window_quiz_question);
}

void quiz_question_window_draw_text(int w_target, int h_target, const char *text, int pos_x, int pos_y, SDL_Color color, SDL_Renderer *renderer) {
	TTF_Font* Sans = nullptr;

	int w_t = 0, h_t = 0;
	int font_size = 2;
	do {
		if (Sans != nullptr) TTF_CloseFont(Sans);
		font_size++;
		Sans = TTF_OpenFont("./custom/font.ttf", font_size);
		if (!Sans) {
			logger_write(&main_logger, LOG_LEVEL_ERROR, "QUIZ font", "unable to open font font.ttf");
			logger_write(&main_logger, LOG_LEVEL_ERROR, "QUIZ font", TTF_GetError());
			exit(1);
		}
		TTF_SizeText(Sans, text, &w_t, &h_t);

		stringstream font_dims;
		font_dims << "w x h: " << w_t << " x " << h_t << ", size: " << font_size;

		//logger_write(&main_logger, LOG_LEVEL_VERBOSE, "QUIZ font dimensions", font_dims.str().c_str());
	} while (w_t < w_target && h_t < h_target);

	font_size--;
	TTF_CloseFont(Sans);
	Sans = TTF_OpenFont("./custom/font.ttf", font_size);

	SDL_Surface* surfaceMessage = TTF_RenderText_Solid(Sans, text, color);
	SDL_Texture* Message = SDL_CreateTextureFromSurface(renderer, surfaceMessage);

	SDL_Rect Message_rect;
	Message_rect.x = pos_x + ((w_target - w_t) / 2);
	Message_rect.y = pos_y + ((h_target - h_t) / 2);
	Message_rect.w = w_t;
	Message_rect.h = h_t;

	SDL_RenderCopy(renderer, Message, NULL, &Message_rect);
	SDL_RenderPresent(renderer);

	SDL_FreeSurface(surfaceMessage);
	SDL_DestroyTexture(Message);
}

void quiz_question_window_render(struct quiz *q, unsigned int question_nr) {
	if (question_nr < 0 || question_nr >= q->questions.size()) return;

	quiz_question_texture = SDL_CreateTextureFromSurface(renderer_quiz_question, quiz_question_overlay);
	SDL_RenderCopy(renderer_quiz_question, quiz_question_texture, NULL, NULL);

	SDL_Color font_color = { 255, 255, 255 };
	map<string, struct quiz_cfg>::iterator q_cfg_it = quiz_question_overlay_cfg.find(string("color"));
	if (q_cfg_it != quiz_question_overlay_cfg.end()) {
		struct quiz_cfg qc = q_cfg_it->second;
		font_color.r = qc.pos_x;
		font_color.g = qc.pos_y;
		font_color.b = qc.width;
		font_color.a = qc.height;
	}

	q_cfg_it = quiz_question_overlay_cfg.find(string("question"));
	if (q_cfg_it != quiz_question_overlay_cfg.end()) {
		struct quiz_cfg qc = q_cfg_it->second;
		quiz_question_window_draw_text(qc.width, qc.height, q->questions[question_nr].question, qc.pos_x, qc.pos_y, font_color, renderer_quiz_question);
	}

	q_cfg_it = quiz_question_overlay_cfg.find(string("answer_a"));
	if (q_cfg_it != quiz_question_overlay_cfg.end()) {
		struct quiz_cfg qc = q_cfg_it->second;
		quiz_question_window_draw_text(qc.width, qc.height, q->questions[question_nr].answer_a, qc.pos_x, qc.pos_y, font_color, renderer_quiz_question);
	}

	q_cfg_it = quiz_question_overlay_cfg.find(string("answer_b"));
	if (q_cfg_it != quiz_question_overlay_cfg.end()) {
		struct quiz_cfg qc = q_cfg_it->second;
		quiz_question_window_draw_text(qc.width, qc.height, q->questions[question_nr].answer_b, qc.pos_x, qc.pos_y, font_color, renderer_quiz_question);
	}

	q_cfg_it = quiz_question_overlay_cfg.find(string("answer_c"));
	if (q_cfg_it != quiz_question_overlay_cfg.end()) {
		struct quiz_cfg qc = q_cfg_it->second;
		quiz_question_window_draw_text(qc.width, qc.height, q->questions[question_nr].answer_c, qc.pos_x, qc.pos_y, font_color, renderer_quiz_question);
	}

	q_cfg_it = quiz_question_overlay_cfg.find(string("answer_d"));
	if (q_cfg_it != quiz_question_overlay_cfg.end()) {
		struct quiz_cfg qc = q_cfg_it->second;
		quiz_question_window_draw_text(qc.width, qc.height, q->questions[question_nr].answer_d, qc.pos_x, qc.pos_y, font_color, renderer_quiz_question);
	}

	SDL_DestroyTexture(quiz_question_texture);
}

void quiz_question_distribution_init() {
	vector<string> qq_cfg = util_file_read("./custom/quiz_question_distribution_overlay.cfg");
	for (int l = 0; l < qq_cfg.size(); l++) {
		if (qq_cfg[l].length() > 0) {
			vector<string> qq_cfg_splt = util_split(qq_cfg[l], ";");
			if (qq_cfg_splt.size() == 5) {
				string identifier(qq_cfg_splt[0].c_str());
				struct quiz_cfg qc;
				qc.pos_x = stoi(qq_cfg_splt[1].c_str());
				qc.pos_y = stoi(qq_cfg_splt[2].c_str());
				qc.width = stoi(qq_cfg_splt[3].c_str());
				qc.height = stoi(qq_cfg_splt[4].c_str());
				quiz_question_distribution_overlay_cfg.insert(pair<string, struct quiz_cfg>(identifier, qc));
			} else {
				printf("Error parsing quiz_question_distribution_overlay.cfg on line %i, found %i columns, 5 needed\n", l, (int)qq_cfg_splt.size());
				exit(1);
			}
		}
	}

	map<string, struct quiz_cfg>::iterator q_cfg_it = quiz_question_distribution_overlay_cfg.find(string("size"));
	unsigned int width = 640;
	unsigned int height = 540;
	if (q_cfg_it != quiz_question_distribution_overlay_cfg.end()) {
		struct quiz_cfg qc = q_cfg_it->second;
		width = qc.pos_x;
		height = qc.pos_y;
	}

	window_quiz_question_distribution = SDL_CreateWindow("Twitch Quiz - Distribution", SDL_WINDOWPOS_UNDEFINED, SDL_WINDOWPOS_UNDEFINED, width, height, 0);
	renderer_quiz_question_distribution = SDL_CreateRenderer(window_quiz_question_distribution, -1, 0);
	quiz_question_distribution_overlay = SDL_LoadBMP("./custom/quiz_question_distribution_overlay.bmp");
}

void quiz_question_distribution_destroy() {
	SDL_DestroyWindow(window_quiz_question_distribution);
}

unsigned int quiz_participants_count_get() {
	return 100;
}

float quiz_question_distribution_get(unsigned int question_nr, unsigned int answer_id) {
	float dist = 0;
	mutex_wait_for(&quiz_participants_lock);
	if (participants > 0) {
		dist = a_count[answer_id] / (float)participants;
	}
	mutex_release(&quiz_participants_lock);
	return dist;
}

void quiz_question_result_calculate(struct quiz *q, unsigned int question_nr) {
	unsigned int correct_id = q->questions[question_nr].correct_id;
	long		q_time = q->questions[question_nr].time_ms;

	vector<struct question_result>* q_result = new vector<struct question_result>();

	mutex_wait_for(&quiz_participants_lock);
	map<string, struct quiz_participant_data *>::iterator qpd = quiz_participants.begin();
	while (qpd != quiz_participants.end()) {
		struct quiz_participant_data* d = qpd->second;
		map<unsigned int, struct quiz_answer>::iterator ans_it = d->answers.find(question_nr);
		if (ans_it != d->answers.end()) {
			if (ans_it->second.answer_id == correct_id) {
				struct question_result qr;
				string name = string(qpd->first.c_str());
				vector<string> n_splt = util_split(name, "!");
				string ns = string(n_splt[0].c_str());
				ns = util_trim(ns, " :");

				qr.name = string(ns);
				qr.time_s = (ans_it->second.time_ms - q_time) / 1000.0f;

				d->correct_answers++;
				d->time_s += qr.time_s;

				bool inserted = false;
				for (int q = 0; q < q_result->size(); q++) {
					if ((*q_result)[q].time_s > qr.time_s) {
						q_result->insert(q_result->begin() + q, qr);
						inserted = true;
						break;
					}
				}
				if (!inserted) q_result->push_back(qr);
			}
		}
		qpd++;
	}

	question_results.insert(pair<unsigned int, vector<struct question_result> *>(question_nr, q_result));
	mutex_release(&quiz_participants_lock);
}

float quiz_question_result_get(unsigned int question_nr, unsigned int position_id, char **name) {
	bool have_result = false;

	map<unsigned int, vector<struct question_result>*>::iterator result = question_results.find(question_nr);
	if (result != question_results.end()) {
		vector<struct question_result>* q_result = result->second;
		if (position_id < q_result->size()) {
			util_chararray_from_const((*q_result)[position_id].name.c_str(), name);
			return (*q_result)[position_id].time_s;
		}
	}

	util_chararray_from_const("", name);
	return -1.0f;
}

void quiz_result_calculate() {
	quiz_result = new vector<struct question_result>();

	map<string, struct quiz_participant_data*>::iterator qpd_it = quiz_participants.begin();
	while (qpd_it != quiz_participants.end()) {

		struct question_result qr;

		string name = string(qpd_it->first.c_str());
		vector<string> n_splt = util_split(name, "!");
		string ns = string(n_splt[0].c_str());
		ns = util_trim(ns, " :");

		qr.name = string(ns);
		qr.count = qpd_it->second->correct_answers;
		qr.time_s = qpd_it->second->time_s;

		bool inserted = false;
		for (int q = 0; q < quiz_result->size(); q++) {
			if ((*quiz_result)[q].count < qr.count || ((*quiz_result)[q].count == qr.count && (*quiz_result)[q].time_s > qr.time_s)) {
				quiz_result->insert(quiz_result->begin() + q, qr);
				inserted = true;
				break;
			}
		}
		if (!inserted) quiz_result->push_back(qr);
		qpd_it++;
	}
}

float quiz_result_get(unsigned int position_id, char **name, unsigned int *correct) {
	if (position_id < quiz_result->size()) {
		util_chararray_from_const((*quiz_result)[position_id].name.c_str(), name);
		*correct = (*quiz_result)[position_id].count;
		return (*quiz_result)[position_id].time_s;
	}
	util_chararray_from_const("", name);
	*correct = 0;
	return -1.0f;
}

void quiz_question_distribution_render(unsigned int question_nr) {
	quiz_question_distribution_texture = SDL_CreateTextureFromSurface(renderer_quiz_question_distribution, quiz_question_distribution_overlay);
	SDL_RenderCopy(renderer_quiz_question_distribution, quiz_question_distribution_texture, NULL, NULL);

	float a_percent = quiz_question_distribution_get(question_nr, 0);
	float b_percent = quiz_question_distribution_get(question_nr, 1);
	float c_percent = quiz_question_distribution_get(question_nr, 2);
	float d_percent = quiz_question_distribution_get(question_nr, 3);

	map<string, struct quiz_cfg>::iterator q_cfg_it = quiz_question_distribution_overlay_cfg.find(string("line_color"));
	if (q_cfg_it != quiz_question_distribution_overlay_cfg.end()) {
		struct quiz_cfg qc = q_cfg_it->second;
		SDL_SetRenderDrawColor(renderer_quiz_question_distribution, qc.pos_x, qc.pos_y, qc.width, qc.height);
	}

	q_cfg_it = quiz_question_distribution_overlay_cfg.find(string("a_line"));
	if (q_cfg_it != quiz_question_distribution_overlay_cfg.end()) {
		struct quiz_cfg qc = q_cfg_it->second;
		SDL_Rect r;
		r.x = qc.pos_x;
		r.y = qc.pos_y;
		r.w = (int) (a_percent * qc.width);
		r.h = qc.height;
		SDL_RenderFillRect(renderer_quiz_question_distribution, &r);
	}

	q_cfg_it = quiz_question_distribution_overlay_cfg.find(string("b_line"));
	if (q_cfg_it != quiz_question_distribution_overlay_cfg.end()) {
		struct quiz_cfg qc = q_cfg_it->second;
		SDL_Rect r;
		r.x = qc.pos_x;
		r.y = qc.pos_y;
		r.w = (int)(b_percent * qc.width);
		r.h = qc.height;
		SDL_RenderFillRect(renderer_quiz_question_distribution, &r);
	}

	q_cfg_it = quiz_question_distribution_overlay_cfg.find(string("c_line"));
	if (q_cfg_it != quiz_question_distribution_overlay_cfg.end()) {
		struct quiz_cfg qc = q_cfg_it->second;
		SDL_Rect r;
		r.x = qc.pos_x;
		r.y = qc.pos_y;
		r.w = (int)(c_percent * qc.width);
		r.h = qc.height;
		SDL_RenderFillRect(renderer_quiz_question_distribution, &r);
	}

	q_cfg_it = quiz_question_distribution_overlay_cfg.find(string("d_line"));
	if (q_cfg_it != quiz_question_distribution_overlay_cfg.end()) {
		struct quiz_cfg qc = q_cfg_it->second;
		SDL_Rect r;
		r.x = qc.pos_x;
		r.y = qc.pos_y;
		r.w = (int)(d_percent * qc.width);
		r.h = qc.height;
		SDL_RenderFillRect(renderer_quiz_question_distribution, &r);
	}

	SDL_Color font_color = { 255, 255, 255 };
	q_cfg_it = quiz_question_distribution_overlay_cfg.find(string("text_color"));
	if (q_cfg_it != quiz_question_distribution_overlay_cfg.end()) {
		struct quiz_cfg qc = q_cfg_it->second;
		font_color.r = qc.pos_x;
		font_color.g = qc.pos_y;
		font_color.b = qc.width;
		font_color.a = qc.height;
	}

	q_cfg_it = quiz_question_distribution_overlay_cfg.find(string("a_percent"));
	if (q_cfg_it != quiz_question_distribution_overlay_cfg.end()) {
		struct quiz_cfg qc = q_cfg_it->second;

		char value_buffer[10];
		snprintf((char *) &value_buffer, 9, "%3.2f %%", a_percent * 100.0);
		quiz_question_window_draw_text(qc.width, qc.height, value_buffer, qc.pos_x, qc.pos_y, font_color, renderer_quiz_question_distribution);
	}

	q_cfg_it = quiz_question_distribution_overlay_cfg.find(string("b_percent"));
	if (q_cfg_it != quiz_question_distribution_overlay_cfg.end()) {
		struct quiz_cfg qc = q_cfg_it->second;

		char value_buffer[10];
		snprintf((char*)&value_buffer, 9, "%3.2f %%", b_percent * 100.0);
		quiz_question_window_draw_text(qc.width, qc.height, value_buffer, qc.pos_x, qc.pos_y, font_color, renderer_quiz_question_distribution);
	}

	q_cfg_it = quiz_question_distribution_overlay_cfg.find(string("c_percent"));
	if (q_cfg_it != quiz_question_distribution_overlay_cfg.end()) {
		struct quiz_cfg qc = q_cfg_it->second;

		char value_buffer[10];
		snprintf((char*)&value_buffer, 9, "%3.2f %%", c_percent * 100.0);
		quiz_question_window_draw_text(qc.width, qc.height, value_buffer, qc.pos_x, qc.pos_y, font_color, renderer_quiz_question_distribution);
	}

	q_cfg_it = quiz_question_distribution_overlay_cfg.find(string("d_percent"));
	if (q_cfg_it != quiz_question_distribution_overlay_cfg.end()) {
		struct quiz_cfg qc = q_cfg_it->second;

		char value_buffer[10];
		snprintf((char*)&value_buffer, 9, "%3.2f %%", d_percent * 100.0);
		quiz_question_window_draw_text(qc.width, qc.height, value_buffer, qc.pos_x, qc.pos_y, font_color, renderer_quiz_question_distribution);
	}

	SDL_RenderPresent(renderer_quiz_question_distribution);
	SDL_DestroyTexture(quiz_question_distribution_texture);
}

void quiz_question_result_init() {
	vector<string> qq_cfg = util_file_read("./custom/quiz_question_result_overlay.cfg");
	for (int l = 0; l < qq_cfg.size(); l++) {
		if (qq_cfg[l].length() > 0) {
			vector<string> qq_cfg_splt = util_split(qq_cfg[l], ";");
			if (qq_cfg_splt.size() == 5) {
				string identifier(qq_cfg_splt[0].c_str());
				struct quiz_cfg qc;
				qc.pos_x = stoi(qq_cfg_splt[1].c_str());
				qc.pos_y = stoi(qq_cfg_splt[2].c_str());
				qc.width = stoi(qq_cfg_splt[3].c_str());
				qc.height = stoi(qq_cfg_splt[4].c_str());
				quiz_question_result_overlay_cfg.insert(pair<string, struct quiz_cfg>(identifier, qc));
			}
			else {
				printf("Error parsing quiz_question_result_overlay.cfg on line %i, found %i columns, 5 needed\n", l, (int)qq_cfg_splt.size());
				exit(1);
			}
		}
	}
	
	map<string, struct quiz_cfg>::iterator q_cfg_it = quiz_question_result_overlay_cfg.find(string("size"));
	unsigned int width = 640;
	unsigned int height = 540;
	if (q_cfg_it != quiz_question_result_overlay_cfg.end()) {
		struct quiz_cfg qc = q_cfg_it->second;
		width = qc.pos_x;
		height = qc.pos_y;
	}

	window_quiz_question_result = SDL_CreateWindow("Twitch Quiz - Question Result", SDL_WINDOWPOS_UNDEFINED, SDL_WINDOWPOS_UNDEFINED, width, height, 0);
	renderer_quiz_question_result = SDL_CreateRenderer(window_quiz_question_result, -1, 0);
	quiz_question_result_overlay = SDL_LoadBMP("./custom/quiz_question_result_overlay.bmp");
}

void quiz_question_result_destroy() {
	SDL_DestroyWindow(window_quiz_question_result);
}

void quiz_question_result_render(unsigned int question_nr) {
	quiz_question_result_texture = SDL_CreateTextureFromSurface(renderer_quiz_question_result, quiz_question_result_overlay);
	SDL_RenderCopy(renderer_quiz_question_result, quiz_question_result_texture, NULL, NULL);
	
	SDL_Color font_color = { 255, 255, 255 };
	map<string, struct quiz_cfg>::iterator q_cfg_it = quiz_question_result_overlay_cfg.find(string("color"));
	if (q_cfg_it != quiz_question_result_overlay_cfg.end()) {
		struct quiz_cfg qc = q_cfg_it->second;
		font_color.r = qc.pos_x;
		font_color.g = qc.pos_y;
		font_color.b = qc.width;
		font_color.a = qc.height;
	}

	unsigned int amount = 0;
	q_cfg_it = quiz_question_result_overlay_cfg.find(string("display_amount"));
	if (q_cfg_it != quiz_question_result_overlay_cfg.end()) {
		struct quiz_cfg qc = q_cfg_it->second;
		amount = qc.pos_x;
	}

	for (int a = 0; a < amount; a++) {
		char* name = nullptr;
		float time = quiz_question_result_get(question_nr, a, &name);

		if (strlen(name) > 0) {
			stringstream d;
			d << "display_" << a;

			q_cfg_it = quiz_question_result_overlay_cfg.find(d.str());
			if (q_cfg_it != quiz_question_result_overlay_cfg.end()) {
				struct quiz_cfg qc = q_cfg_it->second;
				quiz_question_window_draw_text(qc.width, qc.height, name, qc.pos_x, qc.pos_y, font_color, renderer_quiz_question_result);
			}

			stringstream d_;
			d_ << "display_" << a << "_time";

			q_cfg_it = quiz_question_result_overlay_cfg.find(d_.str());
			if (q_cfg_it != quiz_question_result_overlay_cfg.end()) {
				struct quiz_cfg qc = q_cfg_it->second;

				char value_buffer[10];
				snprintf((char*)&value_buffer, 9, "%3.2f s", time);

				quiz_question_window_draw_text(qc.width, qc.height, value_buffer, qc.pos_x, qc.pos_y, font_color, renderer_quiz_question_result);
			}
		}

		free(name);
	}
	
	SDL_RenderPresent(renderer_quiz_question_result);
	SDL_DestroyTexture(quiz_question_result_texture);
}

void quiz_result_init() {
	vector<string> qq_cfg = util_file_read("./custom/quiz_result_overlay.cfg");
	for (int l = 0; l < qq_cfg.size(); l++) {
		if (qq_cfg[l].length() > 0) {
			vector<string> qq_cfg_splt = util_split(qq_cfg[l], ";");
			if (qq_cfg_splt.size() == 5) {
				string identifier(qq_cfg_splt[0].c_str());
				struct quiz_cfg qc;
				qc.pos_x = stoi(qq_cfg_splt[1].c_str());
				qc.pos_y = stoi(qq_cfg_splt[2].c_str());
				qc.width = stoi(qq_cfg_splt[3].c_str());
				qc.height = stoi(qq_cfg_splt[4].c_str());
				quiz_result_overlay_cfg.insert(pair<string, struct quiz_cfg>(identifier, qc));
			}
			else {
				printf("Error parsing quiz_result_overlay.cfg on line %i, found %i columns, 5 needed\n", l, (int)qq_cfg_splt.size());
				exit(1);
			}
		}
	}

	map<string, struct quiz_cfg>::iterator q_cfg_it = quiz_result_overlay_cfg.find(string("size"));
	unsigned int width = 640;
	unsigned int height = 1080;
	if (q_cfg_it != quiz_result_overlay_cfg.end()) {
		struct quiz_cfg qc = q_cfg_it->second;
		width = qc.pos_x;
		height = qc.pos_y;
	}

	window_quiz_result = SDL_CreateWindow("Twitch Quiz - Result", SDL_WINDOWPOS_UNDEFINED, SDL_WINDOWPOS_UNDEFINED, width, height, 0);
	renderer_quiz_result = SDL_CreateRenderer(window_quiz_result, -1, 0);
	quiz_result_overlay = SDL_LoadBMP("./custom/quiz_result_overlay.bmp");
}

void quiz_result_destroy() {
	SDL_DestroyWindow(window_quiz_result);
}

void quiz_result_render() {
	quiz_result_texture = SDL_CreateTextureFromSurface(renderer_quiz_result, quiz_result_overlay);
	SDL_RenderCopy(renderer_quiz_result, quiz_result_texture, NULL, NULL);

	SDL_Color font_color = { 255, 255, 255 };
	map<string, struct quiz_cfg>::iterator q_cfg_it = quiz_result_overlay_cfg.find(string("color"));
	if (q_cfg_it != quiz_result_overlay_cfg.end()) {
		struct quiz_cfg qc = q_cfg_it->second;
		font_color.r = qc.pos_x;
		font_color.g = qc.pos_y;
		font_color.b = qc.width;
		font_color.a = qc.height;
	}

	unsigned int amount = 0;
	q_cfg_it = quiz_result_overlay_cfg.find(string("display_amount"));
	if (q_cfg_it != quiz_result_overlay_cfg.end()) {
		struct quiz_cfg qc = q_cfg_it->second;
		amount = qc.pos_x;
	}

	for (int a = 0; a < amount; a++) {
		char* name = nullptr;
		unsigned int correct = 0;
		float time = quiz_result_get(a, &name, &correct);

		if (strlen(name) > 0) {
			stringstream d;
			d << "display_" << a;

			q_cfg_it = quiz_result_overlay_cfg.find(d.str());
			if (q_cfg_it != quiz_result_overlay_cfg.end()) {
				struct quiz_cfg qc = q_cfg_it->second;
				quiz_question_window_draw_text(qc.width, qc.height, name, qc.pos_x, qc.pos_y, font_color, renderer_quiz_result);
			}


			stringstream d_c;
			d_c << "display_" << a << "_c";

			q_cfg_it = quiz_result_overlay_cfg.find(d_c.str());
			if (q_cfg_it != quiz_result_overlay_cfg.end()) {
				struct quiz_cfg qc = q_cfg_it->second;

				char value_buffer[10];
				snprintf((char*)&value_buffer, 9, "%i", correct);

				quiz_question_window_draw_text(qc.width, qc.height, value_buffer, qc.pos_x, qc.pos_y, font_color, renderer_quiz_result);
			}


			stringstream d_;
			d_ << "display_" << a << "_time";

			q_cfg_it = quiz_result_overlay_cfg.find(d_.str());
			if (q_cfg_it != quiz_result_overlay_cfg.end()) {
				struct quiz_cfg qc = q_cfg_it->second;

				char value_buffer[10];
				snprintf((char*)&value_buffer, 9, "%3.2f s", time);

				quiz_question_window_draw_text(qc.width, qc.height, value_buffer, qc.pos_x, qc.pos_y, font_color, renderer_quiz_result);
			}
		}

		free(name);
	}

	SDL_RenderPresent(renderer_quiz_result);
	SDL_DestroyTexture(quiz_result_texture);
}