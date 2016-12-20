<?php
/* Copyright (C) 2012-2016  Stephan Kreutzer
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
 * @file $/web/libraries/note_management.inc.php
 * @author Stephan Kreutzer
 * @since 2016-12-03
 */



require_once(dirname(__FILE__)."/database.inc.php");



define("NOTE_STATUS_UNKNOWN", 0);
define("NOTE_STATUS_ACTIVE", 1);
define("NOTE_STATUS_TRASHED", 2);



function GetNotes($personId)
{
    if (Database::Get()->IsConnected() !== true)
    {
        return -1;
    }

    require_once(dirname(__FILE__)."/logging.inc.php");

    if (Database::Get()->BeginTransaction() !== true)
    {
        return -2;
    }

    if (logEvent("GetNotes(".$personId.").") != 0)
    {
        Database::Get()->RollbackTransaction();
        return -3;
    }

    $notes = Database::Get()->Query("SELECT `".Database::Get()->GetPrefix()."notes`.`id`,\n".
                                    "    `".Database::Get()->GetPrefix()."notes`.`category`,\n".
                                    "    `".Database::Get()->GetPrefix()."notes`.`text`,\n".
                                    "    `".Database::Get()->GetPrefix()."notes`.`priority`,\n".
                                    "    `".Database::Get()->GetPrefix()."notes`.`status`,\n".
                                    "    `".Database::Get()->GetPrefix()."notes`.`datetime_created`,\n".
                                    "    `".Database::Get()->GetPrefix()."notes`.`datetime_modified`,\n".
                                    "    `".Database::Get()->GetPrefix()."users`.`name` AS `user_name`\n".
                                    "FROM `".Database::Get()->GetPrefix()."notes`\n".
                                    "INNER JOIN `".Database::Get()->GetPrefix()."users`\n".
                                    "ON `".Database::Get()->GetPrefix()."notes`.`id_user`=`".Database::Get()->GetPrefix()."users`.`id`\n".
                                    "WHERE `".Database::Get()->GetPrefix()."notes`.`id_person`=?\n".
                                    "ORDER BY `".Database::Get()->GetPrefix()."notes`.`priority` DESC,\n".
                                    "    `".Database::Get()->GetPrefix()."notes`.`datetime_created` DESC\n",
                                    array($personId),
                                    array(Database::TYPE_INT));

    if (is_array($notes) !== true)
    {
        Database::Get()->RollbackTransaction();
        return -4;
    }

    if (Database::Get()->CommitTransaction() !== true)
    {
        return -5;
    }

    return $notes;
}

function AttachNewNode($personId, $category, $priority, $text, $userId)
{
    /** @todo Check for empty parameters. */

    $personId = (int)$personId;
    $category = (int)$category;
    $priority = (int)$priority;

    if (Database::Get()->IsConnected() !== true)
    {
        return -1;
    }

    require_once(dirname(__FILE__)."/logging.inc.php");

    if (Database::Get()->BeginTransaction() !== true)
    {
        return -2;
    }

    if (logEvent("AttachNewNode(".$personId.", ".$category.", ".$priority.", '".$text."').") != 0)
    {
        Database::Get()->RollbackTransaction();
        return -3;
    }

    $id = Database::Get()->Insert("INSERT INTO `".Database::Get()->GetPrefix()."notes` (`id`,\n".
                                  "    `category`,\n".
                                  "    `text`,\n".
                                  "    `priority`,\n".
                                  "    `status`,\n".
                                  "    `datetime_created`,\n".
                                  "    `datetime_modified`,\n".
                                  "    `id_person`,\n".
                                  "    `id_user`)\n".
                                  "VALUES (?, ?, ?, ?, ".NOTE_STATUS_ACTIVE.", NOW(), NOW(), ?, ?)\n",
                                  array(NULL, $category, $text, $priority, $personId, $userId),
                                  array(Database::TYPE_NULL, Database::TYPE_INT, Database::TYPE_STRING, Database::TYPE_INT, Database::TYPE_INT, Database::TYPE_INT));

    if ($id <= 0)
    {
        Database::Get()->RollbackTransaction();
        return -4;
    }

    if (Database::Get()->CommitTransaction() !== true)
    {
        return -5;
    }

    return $id;
}


?>
