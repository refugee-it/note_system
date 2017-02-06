<?php
/* Copyright (C) 2012-2017  Stephan Kreutzer
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
 * @file $/web/note_file_download.php
 * @brief Download a file which is attached to a note.
 * @author Stephan Kreutzer
 * @since 2017-02-05
 */



require_once("./libraries/https.inc.php");

session_start();

if (isset($_SESSION['user_id']) !== true)
{
    header("HTTP/1.1 403 Forbidden");
    exit(-1);
}

if (isset($_SESSION['user_role']) !== true)
{
    header("HTTP/1.1 403 Forbidden");
    exit(-1);
}

require_once("./libraries/user_defines.inc.php");

if ((int)$_SESSION['user_role'] !== USER_ROLE_ADMIN &&
    (int)$_SESSION['user_role'] !== USER_ROLE_USER)
{
    header("HTTP/1.1 403 Forbidden");
    exit(-1);
}

$fileId = null;

if (isset($_GET['id']) === true)
{
    if (is_numeric($_GET['id']) === true)
    {
        $fileId = (int)$_GET['id'];
    }
    else
    {
        header("HTTP/1.1 400 Bad Request");
        exit(-1);
    }
}
else
{
    header("HTTP/1.1 400 Bad Request");
    exit(-1);
}

require_once(dirname(__FILE__)."/libraries/note_management.inc.php");

$fileInfo = GetNoteFileById($fileId);

if (is_array($fileInfo) !== true)
{
    header("HTTP/1.1 404 Not Found");
    exit(1);
}

if ((int)$_SESSION['user_role'] !== USER_ROLE_ADMIN &&
    (int)$fileInfo['status'] !== NOTE_UPLOAD_STATUS_PUBLIC)
{
    header("HTTP/1.1 403 Forbidden");
    exit(1);
}

if (file_exists("./notes_files/".$fileInfo['internal_name']) !== true)
{
    header("HTTP/1.1 500 Internal Server Error");
    exit(-1);
}


require_once("./libraries/logging.inc.php");

if (Database::Get()->BeginTransaction() !== true)
{
    header("HTTP/1.1 500 Internal Server Error");
    exit(-1);
}

if (logEvent("note_file_download.php?id=".$fileId." ('".$fileInfo['display_name']."', '".$fileInfo['internal_name']."').") != 0)
{
    header("HTTP/1.1 500 Internal Server Error");
    exit(-1);
}

if (Database::Get()->CommitTransaction() !== true)
{
    header("HTTP/1.1 500 Internal Server Error");
    exit(-1);
}


header("Content-Type: application/octet-stream");
header("Content-Disposition:attachment;filename=".$fileInfo['display_name']);

readfile("./notes_files/".$fileInfo['internal_name']);


?>
