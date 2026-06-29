CREATE DATABASE IF NOT EXISTS quiz_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE quiz_db;

DROP TABLE IF EXISTS answers;
DROP TABLE IF EXISTS test_sessions;
DROP TABLE IF EXISTS questions;

CREATE TABLE questions (
                           id INT AUTO_INCREMENT PRIMARY KEY,
                           question_text TEXT NOT NULL,
                           option_a TEXT NOT NULL, option_b TEXT NOT NULL,
                           option_c TEXT NOT NULL, option_d TEXT NOT NULL,
                           correct_answer CHAR(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE test_sessions (
                               id INT AUTO_INCREMENT PRIMARY KEY,
                               ip_address VARCHAR(45) NOT NULL,
                               first_name VARCHAR(100) NOT NULL,
                               last_name VARCHAR(100) NOT NULL,
                               session_token VARCHAR(64) NOT NULL,
                               question_order TEXT NOT NULL,
                               current_question_index TINYINT UNSIGNED DEFAULT 0,
                               is_completed TINYINT(1) DEFAULT 0,
                               score TINYINT UNSIGNED DEFAULT 0,
                               started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                               completed_at DATETIME DEFAULT NULL,
                               UNIQUE KEY uq_ip (ip_address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE answers (
                         id INT AUTO_INCREMENT PRIMARY KEY,
                         session_id INT NOT NULL,
                         question_id INT NOT NULL,
                         selected_answer CHAR(1) NOT NULL,
                         is_correct TINYINT(1) NOT NULL DEFAULT 0,
                         answered_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                         FOREIGN KEY (session_id) REFERENCES test_sessions(id) ON DELETE CASCADE,
                         FOREIGN KEY (question_id) REFERENCES questions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO questions (question_text, option_a, option_b, option_c, option_d, correct_answer) VALUES
                                                                                                  ('What does HTML stand for?','HyperText Markup Language','HighText Machine Language','Hyperlink and Text Markup Language','Home Tool Markup Language','A'),
                                                                                                  ('Which language runs natively in the browser?','PHP','Python','JavaScript','Ruby','C'),
                                                                                                  ('What does SQL stand for?','Sequential Query Logic','Strong Question Language','Structured Query Language','Standard Query Library','C'),
                                                                                                  ('Which HTTP method retrieves data without a body?','POST','PUT','DELETE','GET','D'),
                                                                                                  ('What does PHP stand for?','Personal Home Page','PHP: Hypertext Preprocessor','Professional Hypertext Protocol','Private Host Platform','B'),
                                                                                                  ('What is the default HTTP port?','21','443','80','8080','C'),
                                                                                                  ('Which data structure follows LIFO?','Queue','Linked List','Array','Stack','D'),
                                                                                                  ('What does API stand for?','Application Protocol Interface','Applied Programming Integration','Application Programming Interface','Automated Process Interface','C'),
                                                                                                  ('Which is a NoSQL database?','PostgreSQL','MySQL','SQLite','MongoDB','D'),
                                                                                                  ('Time complexity of binary search?','O(1)','O(n)','O(log n)','O(n²)','C'),
                                                                                                  ('What does encapsulation mean in OOP?','Inheriting from parent','Bundling data and methods in a class','Creating multiple instances','Overloading operators','B'),
                                                                                                  ('Which CSS property changes text colour?','font-color','text-color','foreground','color','D'),
                                                                                                  ('What is a foreign key?','Encrypted primary key','Key from another DB','Field referencing another table primary key','Second unique identifier','C'),
                                                                                                  ('What does CRUD stand for?','Create, Read, Update, Delete','Copy, Retrieve, Upload, Download','Clone, Read, Update, Deploy','Create, Retrieve, Upload, Delete','A'),
                                                                                                  ('Which PHP function starts a session?','start_session()','begin_session()','session_start()','init_session()','C');