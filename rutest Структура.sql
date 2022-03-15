-- phpMyAdmin SQL Dump
-- version 5.0.4
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1:3306
-- Время создания: Мар 16 2022 г., 02:36
-- Версия сервера: 10.1.44-MariaDB
-- Версия PHP: 7.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `rutest`
--

-- --------------------------------------------------------

--
-- Структура таблицы `def_users`
--

CREATE TABLE `def_users` (
  `def_usr_id` int(11) NOT NULL,
  `login` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `groups`
--

CREATE TABLE `groups` (
  `gr_id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(400) COLLATE utf8mb4_unicode_ci NOT NULL,
  `img_id` int(11) DEFAULT NULL COMMENT 'Иконка группы из images',
  `usr_id` int(11) NOT NULL,
  `count_users` int(11) NOT NULL DEFAULT '0',
  `join_key` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `closed` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Пометка на удаление группы'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `gtests`
--

CREATE TABLE `gtests` (
  `gt_id` int(11) NOT NULL,
  `gr_id` int(11) NOT NULL,
  `ref_test_id` int(11) NOT NULL,
  `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `comment` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `count_responses` int(11) NOT NULL DEFAULT '0',
  `attempts` int(11) DEFAULT NULL,
  `date_start` timestamp NULL DEFAULT NULL COMMENT 'Дата и время начала теста',
  `date_end` timestamp NULL DEFAULT NULL COMMENT 'Дата и время окончания теста',
  `duration_time` int(11) DEFAULT NULL COMMENT 'Отведенное время на тест (в секундах)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `images`
--

CREATE TABLE `images` (
  `img_id` int(11) NOT NULL,
  `usr_id` int(11) NOT NULL COMMENT 'Владелец изображения',
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `size` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `requests`
--

CREATE TABLE `requests` (
  `req_id` int(11) NOT NULL,
  `usr_id` int(11) NOT NULL,
  `gr_id` int(11) NOT NULL,
  `name` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `accepted` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Триггеры `requests`
--
DELIMITER $$
CREATE TRIGGER `update_count_users` AFTER INSERT ON `requests` FOR EACH ROW UPDATE groups set 
count_users = (SELECT COUNT(*) from requests where gr_id = NEW.gr_id and requests.accepted = 1 ) where gr_id = NEW.gr_id
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_count_users2` BEFORE DELETE ON `requests` FOR EACH ROW UPDATE groups set 
count_users = (SELECT COUNT(*) from requests where gr_id = OLD.gr_id and requests.accepted = 1 ) where gr_id = OLD.gr_id
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_count_users3` AFTER UPDATE ON `requests` FOR EACH ROW UPDATE groups set 
count_users = (SELECT COUNT(*) from requests where gr_id = NEW.gr_id and requests.accepted = 1 ) where gr_id = NEW.gr_id
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `results`
--

CREATE TABLE `results` (
  `res_id` int(11) NOT NULL,
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(400) COLLATE utf8mb4_unicode_ci NOT NULL,
  `usr_id_auditor` int(11) NOT NULL,
  `ref_test_id` int(11) NOT NULL,
  `usr_id` int(11) NOT NULL,
  `score` int(11) NOT NULL,
  `max_score` int(11) NOT NULL,
  `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `time_end` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата и время завершения теста',
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `gr_id` int(11) DEFAULT NULL,
  `ready` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Триггеры `results`
--
DELIMITER $$
CREATE TRIGGER `counter_responses` AFTER INSERT ON `results` FOR EACH ROW BEGIN

IF (NEW.gr_id IS NULL) THEN

UPDATE tests set count_responses = count_responses + 1 where test_id = NEW.ref_test_id;

ELSE

UPDATE tests set count_responses = count_responses + 1 where tests.test_id in (SELECT ref_test_id from gtests where gtests.gt_id = NEW.ref_test_id);

UPDATE gtests set count_responses = count_responses + 1 WHERE gr_id = NEW.gr_id AND gt_id = NEW.ref_test_id;

END IF;

END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `tests`
--

CREATE TABLE `tests` (
  `test_id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `usr_id` int(11) NOT NULL,
  `ico` varchar(400) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `count_responses` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `usr_id` int(11) NOT NULL,
  `social_network` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `social_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sn_access_token` varchar(400) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `avatar` varchar(400) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mykey` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `def_users`
--
ALTER TABLE `def_users`
  ADD PRIMARY KEY (`def_usr_id`),
  ADD UNIQUE KEY `login` (`login`);

--
-- Индексы таблицы `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`gr_id`),
  ADD KEY `usr_id` (`usr_id`);

--
-- Индексы таблицы `gtests`
--
ALTER TABLE `gtests`
  ADD PRIMARY KEY (`gt_id`),
  ADD KEY `gtests_ibfk_1` (`gr_id`);

--
-- Индексы таблицы `images`
--
ALTER TABLE `images`
  ADD PRIMARY KEY (`img_id`);

--
-- Индексы таблицы `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`req_id`),
  ADD UNIQUE KEY `usr_id` (`usr_id`,`gr_id`),
  ADD KEY `requests_ibfk_1` (`gr_id`);

--
-- Индексы таблицы `results`
--
ALTER TABLE `results`
  ADD PRIMARY KEY (`res_id`),
  ADD KEY `usr_id` (`usr_id`),
  ADD KEY `auditor_usr_id` (`usr_id_auditor`);

--
-- Индексы таблицы `tests`
--
ALTER TABLE `tests`
  ADD PRIMARY KEY (`test_id`),
  ADD KEY `usr_id` (`usr_id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD UNIQUE KEY `usr_id_2` (`usr_id`),
  ADD KEY `usr_id` (`usr_id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `def_users`
--
ALTER TABLE `def_users`
  MODIFY `def_usr_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `groups`
--
ALTER TABLE `groups`
  MODIFY `gr_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `gtests`
--
ALTER TABLE `gtests`
  MODIFY `gt_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `images`
--
ALTER TABLE `images`
  MODIFY `img_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `requests`
--
ALTER TABLE `requests`
  MODIFY `req_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `results`
--
ALTER TABLE `results`
  MODIFY `res_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `tests`
--
ALTER TABLE `tests`
  MODIFY `test_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `usr_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `groups`
--
ALTER TABLE `groups`
  ADD CONSTRAINT `groups_ibfk_1` FOREIGN KEY (`usr_id`) REFERENCES `users` (`usr_id`);

--
-- Ограничения внешнего ключа таблицы `gtests`
--
ALTER TABLE `gtests`
  ADD CONSTRAINT `gtests_ibfk_1` FOREIGN KEY (`gr_id`) REFERENCES `groups` (`gr_id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `requests`
--
ALTER TABLE `requests`
  ADD CONSTRAINT `requests_ibfk_1` FOREIGN KEY (`gr_id`) REFERENCES `groups` (`gr_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `requests_ibfk_2` FOREIGN KEY (`usr_id`) REFERENCES `users` (`usr_id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `results`
--
ALTER TABLE `results`
  ADD CONSTRAINT `results_ibfk_1` FOREIGN KEY (`usr_id`) REFERENCES `users` (`usr_id`),
  ADD CONSTRAINT `results_ibfk_2` FOREIGN KEY (`usr_id_auditor`) REFERENCES `users` (`usr_id`);

--
-- Ограничения внешнего ключа таблицы `tests`
--
ALTER TABLE `tests`
  ADD CONSTRAINT `tests_ibfk_1` FOREIGN KEY (`usr_id`) REFERENCES `users` (`usr_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
