<?php
/* Copyright (C) 2016-2017  Stephan Kreutzer
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



require_once(dirname(__FILE__)."/event_type_defines.inc.php");



function logEvent($crud, $name, array $args)
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

    $text = "";

    if ($crud !== EVENT_INFO)
    {
        if (is_array($args) === true)
        {
            if (!empty($args))
            {
                foreach ($args as $arg)
                {
                    if (!empty($text))
                    {
                        $text .= ", ";
                    }

                    $text .= $arg;
                }
            }
        }

        $text = $name."(".$text.")";
    }
    else
    {
        $text = $name;
    }

    $id = Database::Get()->Insert("INSERT INTO `".Database::Get()->GetPrefix()."logs` (`id`,\n".
                                  "    `datetime`,\n".
                                  "    `type`,\n".
                                  "    `text`,\n".
                                  "    `id_user`)\n".
                                  "VALUES (?, NOW(), ?, ?, ?)\n",
                                  array(NULL, $crud, $text, $userId),
                                  array(Database::TYPE_NULL, Database::TYPE_INT, Database::TYPE_STRING, Database::TYPE_INT));

    if ($id <= 0)
    {
        return -2;
    }

    if (file_exists(dirname(__FILE__)."/../custom/notify.inc.php") === true)
    {
        require_once(dirname(__FILE__)."/../custom/notify.inc.php");

        $notify = GetNotificationDetails();

        if (is_array($notify) === true)
        {
            if (!empty($notify))
            {
                foreach ($notify as $eMailAddress => $filter)
                {
                    if (is_array($filter) === true)
                    {
                        if (!empty($filter))
                        {
                            if (!in_array($name, $filter))
                            {
                                continue;
                            }
                        }
                    }

                    $message = "Time: ".date("c")."\n";
                    $message .= "Event: ".htmlspecialchars($name, ENT_COMPAT | ENT_HTML401, "UTF-8")."\n";

                    @mail($eMailAddress,
                          GetNotificationSubject($crud, $name),
                          $message,
                          "From: ".GetNotificationSender()."\n".
                          "MIME-Version: 1.0\n".
                          "Content-type: text/plain; charset=UTF-8\n");
                }
            }
        }
    }

    return 0;
}



?>
