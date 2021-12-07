The (experimental) Quiz is controlled using the right arrow key, while one of the opened windows is active.

Everything inside the custom folder can be customized.

- font.ttf can be replaced with another ttf-font.
- quiz.cfg contains the twitch channel to join for the quiz.
- quiz.csv contains all questions and answers QUESTION;ANSWER_A;ANSWER_B;ANSWER_C;ANSWER_D;CORRECT_ANSWER_LETTER
- all overlay BMP-images can be replaced, the position and sizes of the textareas are defined inside the respective cfg-files

	- window size 			-> size;width;height;0;0

	- (text/line)_color	 	-> color;r;g;b;alpha

	- amount of displayed fields	-> display_amount;number;0;0;0

	- field positions and sizes -> variable_name;position_x;position_y;width;height
		for fields:
			//variable_name		//usecase
			- question 		-> questions
			- answer_x 		-> answers
			- x_line		-> distribution line
			- x_percent		-> distribution percent
			- display_x		-> name place x
			- display_x_c		-> correct answers from x
			- display_x_time 	-> (sum of) time till give answer


The code could use some cleanup.