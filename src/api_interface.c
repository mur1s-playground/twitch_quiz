#include "api_interface.h"

#include "main.h"
#include "util.h"
#include "logger.h"

#include <sstream>
#include <cstring>

void api_interface_init(struct api_interface *ai, const char *api_url) {
    ai->logged_in = false;
    util_chararray_from_const(api_url, &ai->api_url);
};

char *api_interface_post(struct api_interface *ai, const char *module, const char *controller, const char *action, const char *json) {
    stringstream cmd;
    cmd << "curl -X POST " << ai->api_url;
    if (module != nullptr) {
        cmd << module;
        if (controller != nullptr) {
            cmd << "/" << controller;
            if (action != nullptr) {
                cmd << "/" << action;
            }
        }
    }
    cmd << " -H 'Content-Type: application/json' -d '" << json << "'";

    logger_write(&main_logger, LOG_LEVEL_VERBOSE, "API_INTERFACE cmd", cmd.str().c_str());

    char *result = nullptr;

    util_system_command(cmd.str().c_str(), &result);

    return result;
}

char *api_interface_logged_in_post(struct api_interface *ai, const char *module, const char *controller, const char *action, const char *json) {
    stringstream pl;
    pl << "{\"login_data\":{\"email\":\"" << ai->email << "\",\"token\":\"" << ai->token << "\"},\"data\":" << json << "}";
    return api_interface_post(ai, module, controller, action, pl.str().c_str());
}

bool api_interface_login(struct api_interface *ai, const char *email, const char *password) {
    stringstream pl;
    pl << "{\"email\":\"" << email << "\",\"password\":\"" << password << "\"}";

    char *login_result = api_interface_post(ai, "users", "login", nullptr, pl.str().c_str());

    char *token_pos = strstr(login_result, "token");

    bool result = false;

    if (token_pos != nullptr) {
        token_pos += 8;
        ai->logged_in = true;
        ai->token = (char *) malloc(65);
        ai->token[64] = '\0';
        memcpy(ai->token, token_pos, 64);
        util_chararray_from_const(email, &ai->email);

        result = true;

        logger_write(&main_logger, LOG_LEVEL_VERBOSE, "api_interface login token", ai->token);
    }

    free(login_result);

    return result;
}

char *api_interface_cmd_poll(struct api_interface *ai) {
    char *result = api_interface_logged_in_post(ai, "cmd", "poll", nullptr, "{}");
    return result;
}

void api_interface_distribution_update(struct api_interface *ai, const char *json) {
    char *result = api_interface_logged_in_post(ai, "cmd", "update", "distribution", json);
    free(result);
}

void api_interface_question_result_update(struct api_interface *ai, const char *json) {
    char *result = api_interface_logged_in_post(ai, "cmd", "update", "question", json);
    free(result);
}

void api_interface_result_update(struct api_interface *ai, const char *json) {
    char *result = api_interface_logged_in_post(ai, "cmd", "update", "quiz", json);
    free(result);
}