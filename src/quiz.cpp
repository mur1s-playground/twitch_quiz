#include "quiz.h"

#include "main.h"
#include "util.h"
#include "logger.h"
#include "thread.h"
#include "crypto.h"

#include <cstring>
#include <sstream>
#include <map>

#include "mutex.h"

struct ThreadPool               quizzes_pool;
map<unsigned int, struct quiz *> quizzes = map<unsigned int, struct quiz *>();

struct mutex    quiz_dd_lock;
vector<struct quiz_distribution_data> quiz_dd = vector<struct quiz_distribution_data>();

void quiz_static_init() {
    thread_pool_init(&quizzes_pool, 10);
    mutex_init(&quiz_dd_lock);
}

void quiz_distribution_data_enqueue(unsigned int user_id, unsigned int param, unsigned int participants, unsigned int count[4]) {
    struct quiz_distribution_data qdd;
    qdd.user_id = user_id;
    qdd.param = param;
    qdd.participants = participants;
    memcpy(qdd.count, count, 4 * sizeof(unsigned int));

    mutex_wait_for(&quiz_dd_lock);
    quiz_dd.push_back(qdd);
    mutex_release(&quiz_dd_lock);
}

void quiz_distribution_data_send() {
    unsigned int ct = 0;

    mutex_wait_for(&quiz_dd_lock);

    ct = quiz_dd.size();

    stringstream msg;
    msg << "{";
    for (int c = 0; c < quiz_dd.size(); c++) {
        msg << "\"" << c << "\":{\"user_id\":" << quiz_dd[c].user_id << ",\"param\":" << quiz_dd[c].param << ",\"participants\":" << quiz_dd[c].participants << ",";
        for (int a = 0; a < 4; a++) {
            msg << "\"answer_" << a << "\":" << quiz_dd[c].count[a];
            if (a + 1 < 4) msg << ",";
        }
        msg << "}";
        if (c + 1 < quiz_dd.size()) msg << ",";
    }
    msg << "}\n";

    quiz_dd.clear();
    mutex_release(&quiz_dd_lock);

    if (ct > 0) {
        api_interface_distribution_update(&api_i, msg.str().c_str());
    }
}

void quiz_message_parse(struct quiz* q, struct irc_message* message) {
	struct quiz_participant_data* qp_d = nullptr;

	if (q->state != 0) {
		return;
	}

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
                        struct quiz_participant_data qp_d;
                        qp_d.answer_id = answer_id;
                        qp_d.time_ms = util_get_time_ms();
                        qp_d.active = true;

			map<string, unsigned int>::iterator qp_it = q->participants_data_link.find(name);
			if (qp_it == q->participants_data_link.end()) {
                                q->participants++;
                                q->distribution[answer_id]++;
                                pair<map<string, unsigned int>::iterator, bool> ins_res = q->participants_data_link.insert(pair<string, unsigned int>(name, q->participants_data.size()));
                                qp_d.name_link = ins_res.first;
                                q->participants_data.push_back(qp_d);
			} else {
				unsigned int idx = qp_it->second;
                                q->distribution[q->participants_data[idx].answer_id]--;
                                q->distribution[answer_id]++;
                                q->participants_data[idx].active = false;
                                qp_d.name_link = qp_it;
                                q->participants_data.push_back(qp_d);
                                qp_it->second = q->participants_data.size() - 1;
			}
		}
	}
}

void quiz_process(void *args) {
    struct quiz_process_args *qpa = (struct quiz_process_args *) args;
    struct quiz *q = qpa->q;

    bool running = true;

    unsigned int sleep_ms_ct = 0;
    unsigned int sleep_ms = 4;

    unsigned int distribution_poll_ct = 0;

    while (running) {
        mutex_wait_for(&q->q_lock);
        if (q->cmd_queue.size() > 0) {
            if (strstr(q->cmd_queue[0].cmd, "ask") != nullptr) {
                if (q->state == -1 || q->state == 1) {
                    q->state = 0;
                    memcpy(&q->cmd_current, &q->cmd_queue[0], sizeof(struct quiz_cmd));
                    q->participants = 0;
                    memset(&q->distribution, 0, 4 * sizeof(unsigned int));
                    q->time_ms = util_get_time_ms();

                    q->participants_data.clear();
                    q->participants_data.reserve(5000);
                    q->participants_data_link.clear();

                    vector<string> prm_splt = util_split(q->cmd_current.param, ";");

                    stringstream file_path;
                    file_path << "./data/" << q->cmd_current.user_id << "_" << prm_splt[0] << ".csv";

                    util_file_delete(file_path.str().c_str());
                } else {
                    logger_write(&main_logger, LOG_LEVEL_ERROR, "QUIZ_PROC", "ASK wrong state");
                }
            } else if (strstr(q->cmd_queue[0].cmd, "end") != nullptr) {
                if (q->state == 0) {
                    q->state = 1;
                    memcpy(&q->cmd_current, &q->cmd_queue[0], sizeof(struct quiz_cmd));

                    vector<string> prm_splt = util_split(q->cmd_current.param, "_");
                    unsigned int correct_id = stoi(prm_splt[1].c_str());

                    stringstream top10;
                    top10 << "{\"user_id\":" << q->cmd_current.user_id << ",\"param\":" << prm_splt[0] << ",\"csv\":\"";

                    unsigned int p_count = 0;
                    stringstream result_data;
                    for (int p = 0; p < q->participants_data.size(); p++) {
                        if (q->participants_data[p].active && q->participants_data[p].answer_id == correct_id) {
                            result_data << p << ";" << q->participants_data[p].name_link->first.c_str() << ";" << q->participants_data[p].answer_id << ";" << (q->participants_data[p].time_ms - q->time_ms) / 1000.0f << "\n";
                            if (p_count < 10) {
                                top10 << p << ";" << q->participants_data[p].name_link->first.c_str() << ";" << q->participants_data[p].answer_id << ";" << (q->participants_data[p].time_ms - q->time_ms) / 1000.0f << "#";
                            }
                            p_count++;
                        }
                    }

                    top10 << "\"}";

                    api_interface_question_result_update(&api_i, top10.str().c_str());

                    stringstream file_path;
                    file_path << "./data/" << q->cmd_current.user_id << "_" << prm_splt[0] << ".csv";

                    util_file_write(file_path.str().c_str(), result_data.str().c_str());
                } else {
                    logger_write(&main_logger, LOG_LEVEL_ERROR, "QUIZ_PROC", "END wrong state");
                }
            } else if (strstr(q->cmd_queue[0].cmd, "clear") != nullptr) {
                if (q->state == -1 || q->state == 1) {
                    memcpy(&q->cmd_current, &q->cmd_queue[0], sizeof(struct quiz_cmd));
                    for (int qe = 0; qe < 30; qe++) {
                        stringstream file_path;
                        file_path << "./data/" << q->cmd_current.user_id << "_" << qe << ".csv";

                        util_file_delete(file_path.str().c_str());
                    }
                }
            } else if (strstr(q->cmd_queue[0].cmd, "total") != nullptr) {
                if (q->state == 1) {
                    memcpy(&q->cmd_current, &q->cmd_queue[0], sizeof(struct quiz_cmd));

                    map<string, struct quiz_participant_result_data> q_result = map<string, struct quiz_participant_result_data>();

                    for (int qe = 0; qe < 30; qe++) {
                        stringstream file_path;
                        file_path << "./data/" << q->cmd_current.user_id << "_" << qe << ".csv";

                        vector<string> file = util_file_read(file_path.str().c_str());

                        for (int l = 0; l < file.size(); l++) {
                            string line = file[l];
                            if (strlen(line.c_str()) > 0) {
                                vector<string> splt = util_split(line, ";");
                                if (splt.size() == 4) {
                                    map<string, struct quiz_participant_result_data>::iterator qpr = q_result.find(splt[1]);
                                    if (qpr == q_result.end()) {
                                        struct quiz_participant_result_data qprd;
                                        util_chararray_from_const(splt[1].c_str(), &qprd.name);
                                        qprd.correct_answers = 1;
                                        qprd.total_time = stof(splt[3].c_str());
                                        q_result.insert(pair<string, quiz_participant_result_data>(splt[1], qprd));
                                        logger_write(&main_logger, LOG_LEVEL_DEBUG, "QUIZ_PROC", "TOTAL inserting");
                                    } else {
                                        struct quiz_participant_result_data *qprd = &qpr->second;
                                        qprd->correct_answers++;
                                        qprd->total_time += stof(splt[3].c_str());
                                        logger_write(&main_logger, LOG_LEVEL_DEBUG, "QUIZ_PROC", "TOTAL updating");
                                    }
                                }
                            }
                        }
                    }

                    vector<struct quiz_participant_result_data *> ordered_result = vector<struct quiz_participant_result_data *>();

                    map<string, struct quiz_participant_result_data>::iterator it_f = q_result.begin();
                    while (it_f != q_result.end()) {
                        struct quiz_participant_result_data *qprd = &it_f->second;
                        if (ordered_result.size() == 0) {
                            ordered_result.push_back(qprd);
                        } else {
                            bool inserted = false;
                            for (int ord = 0; ord < ordered_result.size(); ord++) {
                                if (ordered_result[ord]->correct_answers < qprd->correct_answers || (ordered_result[ord]->correct_answers <= qprd->correct_answers && ordered_result[ord]->total_time < qprd->total_time)) {
                                    ordered_result.insert(ordered_result.begin() + ord, qprd);
                                    inserted = true;
                                    break;
                                }
                            }
                            if (!inserted) ordered_result.push_back(qprd);
                        }
                        it_f++;
                    }

                    stringstream top10;
                    top10 << "{\"user_id\":" << q->cmd_current.user_id << ",\"csv\":\"";
                    for (int p = 0; p < 10 && p < ordered_result.size(); p++) {
                        top10 << p << ";" << ordered_result[p]->name << ";" << ordered_result[p]->correct_answers << ";" << ordered_result[p]->total_time << "#";
                    }
                    top10 << "\"}";

                    api_interface_result_update(&api_i, top10.str().c_str());
                }
            }
            q->cmd_queue.erase(q->cmd_queue.begin());
        }

        struct irc_message message_current;
        if (irc_client_message_next(&q->irc_c, &message_current)) {
            quiz_message_parse(q, &message_current);
        }

        if (distribution_poll_ct >= 2000) {
            distribution_poll_ct -= 2000;

            if (q->state == 0) {
                quiz_distribution_data_enqueue(q->cmd_current.user_id, stoi(q->cmd_current.param), q->participants, q->distribution);
            }
        }

        mutex_release(&q->q_lock);
        util_sleep(sleep_ms);
        distribution_poll_ct += sleep_ms;
    }

    thread_terminated(&quizzes_pool, qpa->thread_id);
    free(qpa);
}

void quiz_cmd_enqueue(unsigned int cmd_id, unsigned int user_id, const char *cmd, const char *param, const char *channel) {
    struct quiz_cmd qc;
    qc.cmd_id = cmd_id;
    qc.user_id = user_id;
    util_chararray_from_const(cmd, &qc.cmd);
    util_chararray_from_const(param, &qc.param);
    util_chararray_from_const(channel, &qc.channel);

    map<unsigned int, struct quiz *>::iterator q_it = quizzes.find(user_id);
    if (q_it == quizzes.end()) {
        struct quiz *q = new struct quiz;

        mutex_init(&q->q_lock);
        q->cmd_queue.push_back(qc);
        q->state = -1;

        char numbers[10] = { 0, 1, 2, 3, 4, 5, 6, 7, 8, 9 };

        stringstream irc_nick;
        irc_nick << "justinfan1337";

/*
        char *random = crypto_random(4);
        for (int n = 0; n < 4; n++) {
            irc_nick << (int)(numbers[random[n] % 10]);
        }
        free(random);
*/
        logger_write(&main_logger, LOG_LEVEL_DEBUG, "QUIZ IRC_NICK", irc_nick.str().c_str());

        irc_client_init(&q->irc_c, irc_nick.str().c_str(), "", "irc.chat.twitch.tv", 6697, "8X8ThYzPyo7QDk1mEloDT/DXEVGQ88tte3iD1F67TLg=");
	irc_client_channel_add(&q->irc_c, qc.channel);
	irc_client_connection_establish(&q->irc_c);

        struct quiz_process_args *qpa = new struct quiz_process_args;
        qpa->q = q;

        qpa->thread_id = thread_create(&quizzes_pool, (void*) quiz_process, qpa);

        quizzes.insert(pair<unsigned int, struct quiz *>(user_id, q));
    } else {
        struct quiz *q = q_it->second;
        
        mutex_wait_for(&q->q_lock);
        q->cmd_queue.push_back(qc);
        mutex_release(&q->q_lock);
    }
}