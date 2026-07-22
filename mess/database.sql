CREATE TABLE users(id INT AUTO_INCREMENT PRIMARY KEY,username VARCHAR(50),password VARCHAR(255),created TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE chats(id INT AUTO_INCREMENT PRIMARY KEY,type ENUM('private','group','channel','public'),title VARCHAR(100));
CREATE TABLE members(chat_id INT,user_id INT);
CREATE TABLE messages(id INT AUTO_INCREMENT PRIMARY KEY,chat_id INT,user_id INT,text TEXT,created TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
INSERT INTO chats(type,title) VALUES('group','🌍 گروه همگانی کاداد');
