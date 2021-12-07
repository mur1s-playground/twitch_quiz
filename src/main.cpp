#include "main.h"

#include "irc_client.h"
#include "util.h"

#include "quiz.h"

#include <vector>

#include <SDL.h>
#include <SDL_ttf.h>

using namespace std;

struct logger main_logger;

int main(int argc, char *argv[]) {
	SDL_Init(SDL_INIT_VIDEO);
	SDL_Event event;

	TTF_Init();

	logger_init(&main_logger);
	logger_level_set(&main_logger, LOG_LEVEL_ERROR);
	logger_write(&main_logger, LOG_LEVEL_VERBOSE, "MAIN", "start");

	struct quiz q;
	quiz_init(&q, "./custom/quiz.csv");

	quiz_question_window_init();
	/*
	quiz_question_distribution_init();
	quiz_question_result_init();
	quiz_result_init();
	*/

	vector<string> cfg = util_file_read("./custom/quiz.cfg");
	string channel;
	for (int c = 0; c < cfg.size(); c++) {
		if (cfg[c].length() > 0) {
			vector<string> k_v = util_split(cfg[c], ";");
			if (k_v.size() == 2) {
				if (strstr(k_v[0].c_str(), "channel") != nullptr) {
					channel = string(k_v[1].c_str());
				}
			}
		}
	}

	struct irc_client irc_c;
	irc_client_init(&irc_c, "justinfan1337", "", "irc.chat.twitch.tv", 6697, "8X8ThYzPyo7QDk1mEloDT/DXEVGQ88tte3iD1F67TLg=");
	irc_client_channel_add(&irc_c, channel);
	irc_client_connection_establish(&irc_c);

	int sleep_ms = 4;
	int nothing_new_c = 0;

	struct irc_message message_current;

	int sleep_ms_ct = 0;

	while (true) {
		while (SDL_PollEvent(&event) != 0) {
			switch (event.type) {
			case SDL_QUIT:
				break;
			case SDL_KEYDOWN:
				if (event.key.keysym.sym == SDLK_RIGHT) {
					quiz_state_next(&q);
				} else if (event.key.keysym.sym == SDLK_LEFT) {
					quiz_state_previous(&q);
				}
				break;
			}
		}

		if (irc_client_message_next(&irc_c, &message_current)) {
			quiz_message_parse(&q, &message_current);
			nothing_new_c = 0;
		} else {
			nothing_new_c++;
		}

		util_sleep(4);
		sleep_ms_ct += 4;
		if (sleep_ms_ct % 1000 == 0) {
			quiz_render(&q);
		}
	}

	SDL_Quit();
	
	logger_write(&main_logger, LOG_LEVEL_VERBOSE, "MAIN", "end");
}