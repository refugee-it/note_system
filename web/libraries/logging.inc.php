<?php
/* Copyright (C) 2016  Stephan Kreutzer
 *
 * This file is part of note system for refugee-it.de.
 *
 * note system for refugee-it.de is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License version 3 or any later version,
 * as published by the Free Software Foundation.
 *
 * note system for refugee-it.de is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License 3 for more details.
 *
 * You should have received a copy of the GNU Affero General Public License 3
 * along with note system for refugee-it.de. If not, see <http://www.gnu.org/licenses/>.
 */
/**
 * @file $/web/libraries/logging.inc.php
 * @author Stephan Kreutzer
 * @since 2016-11-22
 */



function logEvent($text)
{
    $userId = 0;

    if (isset($_SESSION['user_id']) === true)
    {
        $userId = (int)$_SESSION['user_id'];
    }

    require_once(dirname(__FILE__)."/database.inc.php");

    if (Database::Get()->IsConnected() !== true)
    {
        return -1;
    }

    $id = Database::Get()->Insert("INSERT INTO `".Database::Get()->GetPrefix()."logs` (`id`,\n".
                                  "    `datetime`,\n".
                                  "    `text`,\n".
                                  "    `id_user`)\n".
                                  "VALUES (?, NOW(), ?, ?)\n",
                                  array(NULL, $text, $userId),
                                  array(Database::TYPE_NULL, Database::TYPE_STRING, Database::TYPE_INT));

    if ($id <= 0)
    {
        return -2;
    }

    return 0;
}



?>
