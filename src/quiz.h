#pragma once

#include <vector>
#include <map>

#include "mutex.h"
#include "irc_client.h"

using namespace std;

struct quiz_distribution_data {
    unsigned int        user_id;
    unsigned int        param;
    unsigned int        participants;
    unsigned int        count[4];
};

struct quiz_participant_data {
    map<string, unsigned int>::iterator name_link;
    unsigned int                        answer_id;
    long                                time_ms;
    bool                                active;
};

struct quiz_participant_result_data {
    char                                *name;
    unsigned int                        correct_answers;
    float                               total_time;
};

struct quiz_process_args {
    unsigned int    thread_id;
    struct quiz     *q;
};

struct quiz_cmd {
    unsigned int    cmd_id;
    unsigned int    user_id;
    char            *cmd;
    char            *param;
    char            *channel;
};

struct quiz {
    struct mutex                                q_lock;

    vector<struct quiz_cmd>                     cmd_queue;

    struct irc_client                           irc_c;

    long                                        time_ms;
    unsigned int                                state;
    struct quiz_cmd                             cmd_current;

    unsigned int                                participants;
    unsigned int                                distribution[4];
    vector<struct quiz_participant_data>        participants_data;
    map<string, unsigned int>           	participants_data_link;
};

void quiz_static_init();

void quiz_distribution_data_send();

void quiz_cmd_enqueue(unsigned int cmd_id, unsigned int user_id, const char *cmd, const char *param, const char *channel);