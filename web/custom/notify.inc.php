<?php
/* Copyright (C) 2017  Stephan Kreutzer
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
 * @file $/web/custom/notify.inc.php
 * @brief Define a list of e-mail addresses who should be notified in case of
 *     certain events.
 * @author Stephan Kreutzer
 * @since 2017-05-19
 */



function GetNotificationDetails()
{
    return array( /* "somebody@example.org" => array("AddNote", "UpdateNote", "NoteAssignUser", "NoteDeAssignUser", "NoteCompleted", "NoteAddUpload", "NoteRemoveUpload", "DeleteNote", "DeleteAllNotes", "InsertNewPerson", "DeletePerson"),
                     "admin@example.org" => array() */ );
}

function GetNotificationSender()
{
    return "noreply@example.org";
}

function GetNotificationSubject($crud, $name)
{
    $subject = "[note system]";

    switch ($crud)
    {
    case EVENT_CREATE:
        $subject .= " CREATE (".$name.")";
        break;
    case EVENT_UPDATE:
        $subject .= " UPDATE (".$name.")";
        break;
    case EVENT_DELETE:
        $subject .= " DELETE (".$name.")";
        break;
    default:
        $subject .= " ".$name;
        break;
    }

    return $subject;
}

?>
