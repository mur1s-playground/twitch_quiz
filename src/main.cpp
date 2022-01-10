#include "main.h"

#include "irc_client.h"
#include "util.h"

#include <cstring>
#include <vector>

using namespace std;

struct logger main_logger;
struct ThreadPool main_thread_pool;
struct quiz q;
struct api_interface api_i;

int main(int argc, char *argv[]) {
        thread_pool_init(&main_thread_pool, 10);

	logger_init(&main_logger);
	logger_level_set(&main_logger, LOG_LEVEL_VERBOSE);
	logger_write(&main_logger, LOG_LEVEL_VERBOSE, "MAIN", "start");

        quiz_static_init();

	vector<string> cfg = util_file_read("./quiz.cfg");
	string channel;
        string api_url;
        string api_email;
        string api_password;
        int api_cmd_poll_freq = 2000;
	for (int c = 0; c < cfg.size(); c++) {
		if (cfg[c].length() > 0) {
			vector<string> k_v = util_split(cfg[c], ";");
			if (k_v.size() == 2) {
                                if (strstr(k_v[0].c_str(), "api_url") != nullptr) {
                                        api_url = string(k_v[1].c_str());
				} else if (strstr(k_v[0].c_str(), "api_email") != nullptr) {
                                        api_email = string(k_v[1].c_str());
                                } else if (strstr(k_v[0].c_str(), "api_password") != nullptr) {
                                        api_password = string(k_v[1].c_str());
                                } else if (strstr(k_v[0].c_str(), "api_cmd_poll_freq") != nullptr) {
                                        api_cmd_poll_freq = stoi(k_v[1].c_str());
                                }
			}
		}
	}       

        api_interface_init(&api_i, api_url.c_str());
        if (api_interface_login(&api_i, api_email.c_str(), api_password.c_str())) {
            while (true) {
                char *cmd_poll = api_interface_cmd_poll(&api_i);
                if (strlen(cmd_poll) > 0) {
                    vector<string> cmds = util_split(string(cmd_poll), "\n");
                    for (int l = 0; l < cmds.size(); l++) {
                        vector<string> cmd = util_split(cmds[l], ";");
                        if (cmd.size() == 5) {
                            quiz_cmd_enqueue(stoi(cmd[0].c_str()), stoi(cmd[1].c_str()), cmd[2].c_str(), cmd[3].c_str(), cmd[4].c_str());
                        }
                    }
                }
                logger_write(&main_logger, LOG_LEVEL_VERBOSE, "API_INTERFACE POLL", cmd_poll);
                free(cmd_poll);

                quiz_distribution_data_send();
                util_sleep(api_cmd_poll_freq);
            }
        } else {
            logger_write(&main_logger, LOG_LEVEL_ERROR, "API_INTERFACE LOGIN", "FAILED!");
        }
      
        exit(0);

	logger_write(&main_logger, LOG_LEVEL_VERBOSE, "MAIN", "end");
}