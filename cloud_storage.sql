-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Хост: database:3306
-- Время создания: Сен 20 2022 г., 12:59
-- Версия сервера: 10.6.9-MariaDB-1:10.6.9+maria~ubu2004
-- Версия PHP: 8.0.21

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `cloud_storage`
--

-- --------------------------------------------------------

--
-- Структура таблицы `file_user`
--

CREATE TABLE `file_user` (
                             `id` int(11) NOT NULL,
                             `file_name` text NOT NULL,
                             `cipher_name` text NOT NULL,
                             `storage` text NOT NULL,
                             `user_id` int(11) NOT NULL,
                             `access` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `file_user`
--

INSERT INTO `file_user` (`id`, `file_name`, `cipher_name`, `storage`, `user_id`, `access`) VALUES
                                                                                               (24, 'lego.png', 'file1663678432_lego.png', './fileSave/', 38, NULL),
                                                                                               (25, 'lego.png', 'file1663678438_lego.png', './fileSave/', 38, NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `user`
--

CREATE TABLE `user` (
                        `id` int(11) NOT NULL,
                        `name` char(50) NOT NULL,
                        `email` text NOT NULL,
                        `password` text NOT NULL,
                        `token` text DEFAULT NULL,
                        `role` char(20) NOT NULL,
                        `age` int(11) NOT NULL,
                        `sex` char(20) NOT NULL,
                        `create_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `user`
--

INSERT INTO `user` (`id`, `name`, `email`, `password`, `token`, `role`, `age`, `sex`, `create_date`) VALUES
                                                                                                         (31, 'Vasya', '$2y$10$X6LAUClzpkp5/ZcaSdQ9w.dVwFpmWLJ9Vg/S1AylZobxfCPNZIXda', '$2y$10$n1xHig2oFa2Tk9YY5vwJd.938JsLuO0LQPPylP4nyxAPz5QsjsP/C', '$2y$10$iTL88ezFNWiO2c.LZUth0ugtZ0xCfdNAvZCCqhtrvRYDjGwb/l0IS', 'user', 55, 'male', '2022-09-07 08:32:16'),
                                                                                                         (32, 'Lila', '$2y$10$DtvrB1fXB9cA1LRMWx.wmOpL8Us1ErzmVeH2V.VYsN21tAeoJXRtq', '$2y$10$y3mTXv0isQXq4UHZhZ0EGeVGQw9MMT25.4T999wbnsf8aKRJrBc0S', '$2y$10$0IfhDHKu7OxSIjpQkjmys.krTpxLenD3oQspAge/LS0py1583x87e', 'user', 18, 'female', '2022-09-07 08:32:30'),
                                                                                                         (38, 'Sergey', '$2y$10$iI8jzGGLmD.JpXX/kGeLYe7erXi0lF6m2JZGBQkNfSXPFSkV13ITW', '$2y$10$.e8pFHD9Xycz0RzdkdZMMOTITJN9VJaayVjQcXpD99aitrGii5N4a', '$2y$10$dTCXiMuBsgF0mFT/mvKu0.SMoC.T7/fVcV.P68uM7IUPy6f3wGn8u', 'admin', 34, 'male', '2022-09-20 12:53:30');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `file_user`
--
ALTER TABLE `file_user`
    ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `user`
--
ALTER TABLE `user`
    ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `file_user`
--
ALTER TABLE `file_user`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT для таблицы `user`
--
ALTER TABLE `user`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `file_user`
--
ALTER TABLE `file_user`
    ADD CONSTRAINT `file_user_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
