#pragma once

#include <vector>
#include <map>

#include "irc_client.h"

using namespace std;

struct quiz_cfg {
	unsigned int pos_x;
	unsigned int pos_y;
	unsigned int width;
	unsigned int height;
};

struct quiz_answer {
	unsigned int answer_id;
	LONG		 time_ms;
};

struct question_result {
	string		name;
	float		time_s;
	unsigned int count;
};

struct quiz_participant_data {
	map<unsigned int, struct quiz_answer> answers;

	struct mutex lock;

	unsigned int correct_answers;
	float		 time_s;
};

struct quiz_question {
	char* question;
	char* answer_a;
	char* answer_b;
	char* answer_c;
	char* answer_d;

	unsigned int correct_id;

	LONG time_ms;
};

struct quiz {
	vector<struct quiz_question> questions;

	int question_nr;

	int quiz_state;
	struct mutex state_lock;
};

void quiz_render(struct quiz* q);

void quiz_state_next(struct quiz* q);
void quiz_state_previous(struct quiz* q);

void quiz_message_parse(struct quiz* q, struct irc_message *message);

void quiz_init(struct quiz *q, const char *filename);

void quiz_question_window_init();
void quiz_question_window_render(struct quiz* q, unsigned int question_nr);
void quiz_question_window_destroy();

void quiz_question_distribution_init();
void quiz_question_distribution_render(unsigned int question_nr);
void quiz_question_distribution_destroy();

void quiz_question_result_init();
void quiz_question_result_render(unsigned int question_nr);
void quiz_question_result_destroy();

void quiz_result_init();
void quiz_result_render();
void quiz_result_destroy();