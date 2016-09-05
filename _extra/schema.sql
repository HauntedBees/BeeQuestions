CREATE TABLE 'bq_answers' (
	'cID' BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	'xUser' BIGINT NOT NULL,
	'sAnswer' TEXT NOT NULL,
	'iStatus' INT NOT NULL,
	'dtStatusChanged' DATETIME NOT NULL,
	'dtOpened' DATETIME NOT NULL,
	'dtClosed' DATETIME NULL,
	'iViews' INT NOT NULL, 
	'iScore' INT NOT NULL, 
	'xBestQuestion' BIGINT NULL
) ENGINE = INNODB;

CREATE TABLE 'bq_tags' (
	'cID' BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	'sTag' VARCHAR(50) NOT NULL
) ENGINE = INNODB;

CREATE TABLE 'bq_errors' (
	'cID' BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	'dtDate' DATETIME NOT NULL,
	'sType' VARCHAR(30) NOT NULL,
	'sMessage' TEXT NOT NULL
) ENGINE = INNODB;

CREATE TABLE 'bq_users' (
	'cID' BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	'iFBID' BIGINT NULL,
	'sName' VARCHAR(100) NOT NULL,
	'sDisplayName' VARCHAR(100) NOT NULL,
	'dtJoined' DATETIME NOT NULL, 
	'dtLastLoad' DATETIME NOT NULL,
	'iScore' INT NOT NULL DEFAULT '100',
	'iLevel' TINYINT NOT NULL DEFAULT '2', 
	'iModeratorTier' TINY INT NOT NULL DEFAULT '0'
) ENGINE = INNODB;
ALTER TABLE 'bq_users' ADD 'dtLastLoad' DATETIME NOT NULL;

CREATE TABLE 'bq_answers_tags_xref' (
	'xAnswer' BIGINT NOT NULL,
	'xTag' BIGINT NOT NULL,
INDEX ('xAnswer', 'xTag')
) ENGINE = INNODB;

CREATE TABLE 'bq_answers_likes_xref' (
	'xAnswer' BIGINT NOT NULL,
	'xUser' BIGINT NOT NULL,
INDEX ('xAnswer', 'xUser')
) ENGINE = INNODB;

CREATE TABLE 'bq_questions_likes_xref' (
	'xQuestion' BIGINT NOT NULL,
	'xUser' BIGINT NOT NULL,
INDEX ('xQuestion', 'xUser')
) ENGINE = INNODB;

CREATE VIEW FrontPageAnswers AS
	SELECT a.cID AS answerId, a.cID64, a.sAnswer AS answertext, u.sDisplayName AS username, u.cID64 AS uID64, a.dtOpened AS postdate, GROUP_CONCAT(DISTINCT t.sTag) AS tagName, COUNT(DISTINCT q.cID) AS questions, 
		a.dtStatusChanged AS changed, a.iScore AS score, a.iStatus AS status, a.iViews AS views
	FROM bq_answers a
		INNER JOIN bq_users u ON a.xUser = u.cID
		INNER JOIN bq_answers_tags_xref x ON a.cID = x.xAnswer
		INNER JOIN bq_tags t ON x.xTag = t.cID
		LEFT JOIN bq_questions q ON q.xAnswer = a.cID
	GROUP BY a.cID;
	

CREATE TABLE 'bq_questions' (
	'cID' BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	'xAnswer' BIGINT NOT NULL,
	'xUser' BIGINT NOT NULL,
	'sQuestion' TEXT NOT NULL,
	'dtPosted' DATETIME NOT NULL,
	'iScore' INT NOT NULL
) ENGINE = INNODB;

CREATE TABLE 'bq_levels' (
	'iLevel' INT NOT NULL,
	'sTitle' VARCHAR(40) NOT NULL,
	'sDesc' VARCHAR(400) NOT NULL,
	'iScoreRequired' INT NOT NULL,
	'iAnswersPerDay' INT NOT NULL, 
	'iQuestionsPerDay' INT NOT NULL
PRIMARY KEY ('iLevel')
) ENGINE = INNODB;

INSERT INTO  'bq_levels' ('iLevel', 'sTitle', 'sDesc', 'iScoreRequired', 'iAnswersPerDay', 'iQuestionsPerDay') VALUES
	('1', 'Little Egg', 'You are so little. What an egg.', '0', '1', '5'), 
	('2', 'Newbie', 'You are a wee child with much to learn.', '75', '3', '9'), 
	('3', 'Philosopher', 'Every question has an answer, but does every answer have a question?', '300', '7', '17'), 
	('4', 'Eris Tottle', 'All men by nature desire to get points in online games.', '800', '13', '27'),
	('5', 'Plate-O', 'Boredom is the feeling of a philosopher, and philosophy begins in boredom.', '1300', '21', '41'),
	('6', 'So Crates', 'There is only one good, knowledge, and one evil, Bee Colony Collapse Disorder.', '2000', '31', '57'),
	('7', 'Anti-Philosopher', 'Who needs rational inquiry when you can have irrational yelling?', '4000', '43', '77'),
	('8', 'Queen Bee', 'A bee!', '10000', '57', '99');


CREATE TABLE 'bq_notifications' (
	'cID' BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	'xUser' BIGINT NOT NULL,
	'sTemplate' VARCHAR(50) NOT NULL,
	'sIconClass' VARCHAR(50) NOT NULL, 
	'sToken1' VARCHAR(100) NULL,
	'sToken2' VARCHAR(100) NULL,
	'sToken3' VARCHAR(100) NULL,
	'sToken4' VARCHAR(100) NULL,
	'sToken5' VARCHAR(100) NULL,
	'sToken6' VARCHAR(100) NULL,
	'dtPosted' DATETIME NOT NULL,
	'bDismissed' BOOL NOT NULL DEFAULT '0'
) ENGINE = INNODB;

CREATE TABLE 'bq_users_reports_xref' (
	'xUser' BIGINT NOT NULL,
	'xAnswer' BIGINT NULL,
	'xQuestion' BIGINT NULL,
	'xReportedBy' BIGINT NOT NULL,
	'dtReported' DATETIME NOT NULL,
	'bDismissed' TINYINT NOT NULL DEFAULT '0', 
INDEX ('xUser', 'xAnswer', 'xQuestion', 'xReportedBy')
) ENGINE = INNODB;