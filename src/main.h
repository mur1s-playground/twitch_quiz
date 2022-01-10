#pragma once

#include "logger.h"
#include "thread.h"
#include "quiz.h"
#include "api_interface.h"

extern struct logger main_logger;
extern struct ThreadPool main_thread_pool;
extern struct quiz q;
extern struct api_interface api_i;