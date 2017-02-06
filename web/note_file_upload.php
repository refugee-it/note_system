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
 * @file $/web/note_file_upload.php
 * @brief Upload a file and attach it to a note.
 * @author Stephan Kreutzer
 * @since 2017-02-04
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

$noteId = null;

if (isset($_GET['id']) === true)
{
    if (is_numeric($_GET['id']) === true)
    {
        $noteId = (int)$_GET['id'];
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

$note = GetNoteById($noteId);

if (is_array($note) !== true)
{
    header("HTTP/1.1 404 Not Found");
    exit(1);
}

if ((int)$_SESSION['user_role'] !== USER_ROLE_ADMIN &&
    (int)$note['id_user'] !== (int)$_SESSION['user_id'])
{
    header("HTTP/1.1 403 Forbidden");
    exit(1);
}

if ((int)$_SESSION['user_role'] !== USER_ROLE_ADMIN &&
    (int)$note['status'] !== NOTE_STATUS_ACTIVE)
{
    header("HTTP/1.1 403 Forbidden");
    exit(1);
}

require_once("./libraries/languagelib.inc.php");
require_once(getLanguageFile("note_file_upload"));

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n".
     "<!DOCTYPE html\n".
     "    PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\"\n".
     "    \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n".
     "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"".getCurrentLanguage()."\" lang=\"".getCurrentLanguage()."\">\n".
     "  <head>\n".
     "    <meta http-equiv=\"content-type\" content=\"application/xhtml+xml; charset=UTF-8\"/>\n".
     "    <title>".LANG_PAGETITLE."</title>\n".
     "    <link rel=\"stylesheet\" type=\"text/css\" href=\"mainstyle.css\"/>\n".
     "  </head>\n".
     "  <body>\n".
     "    <div class=\"mainbox\">\n".
     "      <div class=\"mainbox_header\">\n".
     "        <h1 class=\"mainbox_header_h1\">".LANG_HEADER."</h1>\n".
     "      </div>\n".
     "      <div class=\"mainbox_body\">\n";

if (isset($_POST['upload']) === true)
{
    $success = true;

    if (isset($_FILES['file']) !== true)
    {
        echo "            <p>\n".
             "              <span class=\"error\">".LANG_UPLOAD_GENERAL_ERROR."</span>\n".
             "            </p>\n".
             "            <a href=\"note_file_upload.php?id=".htmlspecialchars($noteId, ENT_COMPAT | ENT_HTML401, "UTF-8")."\">".LANG_LINKCAPTION_BACK."</a>\n";

        $success = false;
    }

    if ($success === true)
    {
        if ($_FILES['file']['error'] != 0)
        {
            echo "            <p>\n".
                 "              <span class=\"error\">".LANG_UPLOAD_SPECIFIC_ERROR_PRE.htmlspecialchars($_FILES['file']['error'], ENT_COMPAT | ENT_HTML401, "UTF-8").LANG_UPLOAD_SPECIFIC_ERROR_POST."</span>\n".
                 "            </p>\n".
                 "            <a href=\"note_file_upload.php?id=".htmlspecialchars($noteId, ENT_COMPAT | ENT_HTML401, "UTF-8")."\">".LANG_LINKCAPTION_BACK."</a>\n";

            $success = false;
        }
    }

    if ($success === true)
    {
        if ($_FILES['file']['size'] > 5242880)
        {
            echo "            <p>\n".
                 "              <span class=\"error\">".LANG_UPLOAD_FILESIZE_ERROR_PRE."5242880".LANG_UPLOAD_FILESIZE_ERROR_POST."</span>\n".
                 "            </p>\n".
                 "            <a href=\"note_file_upload.php?id=".htmlspecialchars($noteId, ENT_COMPAT | ENT_HTML401, "UTF-8")."\">".LANG_LINKCAPTION_BACK."</a>\n";

            $success = false;
        }
    }

    if ($success === true)
    {
        $result = NoteAddUpload($noteId, $_FILES['file']['tmp_name'], $_FILES['file']['name']);

        if (is_array($result) !== true)
        {
            if ($result === -5)
            {
                echo "            <p>\n".
                     "              <span class=\"error\">".LANG_UPLOAD_CANT_SAVE."</span>\n".
                     "            </p>\n".
                     "            <a href=\"note_file_upload.php?id=".htmlspecialchars($noteId, ENT_COMPAT | ENT_HTML401, "UTF-8")."\">".LANG_LINKCAPTION_BACK."</a>\n";
            }
            else
            {
                echo "            <p>\n".
                     "              <span class=\"error\">".LANG_ATTACH_ERROR."</span>\n".
                     "            </p>\n".
                     "            <a href=\"note_file_upload.php?id=".htmlspecialchars($noteId, ENT_COMPAT | ENT_HTML401, "UTF-8")."\">".LANG_LINKCAPTION_BACK."</a>\n";
            }

            $success = false;
        }
    }

    if ($success === true)
    {
        echo "            <p>\n".
             "              <span class=\"success\">".LANG_UPLOAD_SUCCESS."</span>\n".
             "            </p>\n".
             "            <p>\n".
             "              <a href=\"note_file_upload.php?id=".htmlspecialchars($noteId, ENT_COMPAT | ENT_HTML401, "UTF-8")."\">".LANG_LINKCAPTION_ANOTHER."</a>\n".
             "              <a href=\"note_details.php?id=".htmlspecialchars($noteId, ENT_COMPAT | ENT_HTML401, "UTF-8")."\">".LANG_LINKCAPTION_DONE."</a>\n".
             "            </p>\n";
    }
}
else
{

    echo "        <p>\n".
         "          ".LANG_UPLOAD_DESCRIPTION."\n".
         "        </p>\n".
         "        <form enctype=\"multipart/form-data\" action=\"note_file_upload.php?id=".htmlspecialchars($noteId, ENT_COMPAT | ENT_HTML401, "UTF-8")."\" method=\"post\">\n".
         "          <fieldset>\n".
         "            <input type=\"file\" name=\"file\"/><br/>\n".
         "            <input type=\"submit\" name=\"upload\" value=\"".LANG_UPLOAD_SUBMIT."\"/><br/>\n".
         "          </fieldset>\n".
         "        </form>\n".
         "        <a href=\"note_details.php?id=".htmlspecialchars($noteId, ENT_COMPAT | ENT_HTML401, "UTF-8")."\">".LANG_LINKCAPTION_CANCEL."</a>\n";
}

echo "      </div>\n".
     "    </div>\n".
     "    <div class=\"footerbox\">\n".
     "      <a href=\"license.php\" class=\"footerbox_link\">".LANG_LICENSE."</a>\n".
     "    </div>\n".
     "  </body>\n".
     "</html>\n".
     "\n";


?>
