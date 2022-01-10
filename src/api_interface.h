#pragma once

struct api_interface {
    char *api_url;
    bool logged_in;
    char *email;
    char *token;
};

void api_interface_init(struct api_interface *ai, const char *api_url);

bool api_interface_login(struct api_interface *ai, const char *email, const char *password);

char *api_interface_cmd_poll(struct api_interface *ai);

void api_interface_distribution_update(struct api_interface *ai, const char *json);
void api_interface_question_result_update(struct api_interface *ai, const char *json);
void api_interface_result_update(struct api_interface *ai, const char *json);